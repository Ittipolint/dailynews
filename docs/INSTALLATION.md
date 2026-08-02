# DailyNews — คู่มือการติดตั้ง (Installation Guide)

เอกสารนี้อธิบายการติดตั้งระบบ DailyNews บน Ubuntu (Production) และ Local ตามข้อกำหนดใน [Software Specification](Software-Specification.md)

## 1. ข้อกำหนดเบื้องต้น (Prerequisites)

### Server (Ubuntu 22.04+)
- PHP 8.2+ (พร้อม extensions: `pdo_mysql`, `pdo_pgsql`, `mbstring`, `curl`, `simplexml`, `redis`, `zip`)
- Composer 2.x
- Nginx
- MySQL 8.x (สำหรับ Web Application framework DB)
- PostgreSQL 14+ (พร้อม pgvector สำหรับ vector search)
- Redis
- n8n (มีอยู่แล้วที่ https://n8n38-sbu.veya.co.th)
- Metabase (optional, สำหรับ Dashboard/BI)

```bash
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-pgsql php8.3-mbstring \
    php8.3-curl php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis \
    composer nginx mysql-server postgresql postgresql-contrib redis-server
```

## 2. สร้างฐานข้อมูล

### MySQL (Web App DB)
```sql
CREATE DATABASE dailynews CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dailynews'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON dailynews.* TO 'dailynews'@'localhost';
FLUSH PRIVILEGES;
```

### PostgreSQL (Main DB) — ชื่อตาม Requirement
```sql
CREATE DATABASE ittipolint_dailynews;
CREATE USER ittipolint_dailynews WITH PASSWORD '<REDACTED>';
GRANT ALL PRIVILEGES ON DATABASE ittipolint_dailynews TO ittipolint_dailynews;
\c ittipolint_dailynews
CREATE EXTENSION IF NOT EXISTS vector;
```

> **หมายเหตุ:** ตาม Requirement ใช้ Database `ittipolint_dailynews` / User `ittipolint_dailynews`

## 3. ติดตั้ง Web Application

```bash
cd /var/www
git clone https://github.com/Ittipolint/dailynews.git dailynews
cd dailynews/webapp

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

### แก้ไข `.env`
```dotenv
APP_URL=https://ittipolint-sbu.veya.co.th/dailynews

# MySQL
DB_DATABASE=dailynews
DB_USERNAME=dailynews
DB_PASSWORD=STRONG_PASSWORD

# PostgreSQL
PGSQL_DATABASE=ittipolint_dailynews
PGSQL_USERNAME=ittipolint_dailynews
PGSQL_PASSWORD=<REDACTED>

# n8n
N8N_URL=https://n8n38-sbu.veya.co.th
N8N_API_KEY=<api-key-from-requirement>

# Translation + AI (Google Gemini)
GOOGLE_GEMINI_API_KEY=<your-gemini-key>
TRANSLATION_DRIVER=google

# LINE
LINE_CHANNEL_ID=1528339539
LINE_CHANNEL_SECRET=<from-requirement>
LINE_ACCESS_TOKEN=<from-requirement>
LINE_WEBHOOK_URL=https://n8n38-sbu.veya.co.th/webhook/line

# Mail
MAIL_FROM_NAME=DailyNews
MAIL_FROM_ADDRESS=dailynews@ittipolint-sbu.veya.co.th

# Initial admin
ADMIN_EMAIL=ittipolint@gmail.com
ADMIN_PASSWORD=<set-a-strong-password>

# Encryption key for credentials at rest (generate: php -r "echo base64_encode(random_bytes(32));")
CREDENTIAL_ENCRYPTION_KEY=
```

## 4. Migrate + Seed

```bash
php artisan migrate --force
php artisan migrate --database=pgsql --force
php artisan db:seed --force
```

Seeder จะสร้าง:
- หมวดหมู่ข่าว 9 ประเภท
- แหล่งข่าวเริ่มต้น 13 รายการ (AP, BBC, CNN, Al Jazeera, Guardian, NYT, Xinhua, China Daily, Reuters, Thai PBS, Bangkok Post, MGR, Bloomberg API)
- ประเภทสมาชิก (บุคคล/องค์กร)
- Credential เริ่มต้น (LINE, SMTP, Gemini, n8n, Neo4j)
- ผู้ดูแลระบบ (ตาม ADMIN_EMAIL)

## 5. ตั้งค่า Nginx

เพิ่ม location ใน vhost ของ `ittipolint-sbu.veya.co.th` (ดู `deploy/nginx-dailynews.conf`):

```nginx
location ^~ /dailynews {
    alias /var/www/dailynews/webapp/public;
    try_files $uri $uri/ @dailynews;

    location ~ ^/dailynews/.*\.php$ {
        alias /var/www/dailynews/webapp/public;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $request_filename;
    }
    location @dailynews {
        rewrite ^/dailynews/(.*)$ /dailynews/index.php?/$1 last;
    }
}
```

## 6. Scheduler (Background Jobs)

```bash
* * * * * cd /var/www/dailynews/webapp && php artisan schedule:run >> /dev/null 2>&1
```

Schedules ที่ตั้งไว้ (ใน `routes/console.php`):
- `dailynews:ingest` — ดึงข่าวทุก 1 ชั่วโมง
- `dailynews:translate` — แปลข่าวทุก 1 ชั่วโมง
- `dailynews:deliver` — ส่งข่าวทุกนาที (ตาม schedule ของสมาชิก)

## 7. n8n Workflows

import ไฟล์จาก `n8n/workflows/` ไปยัง n8n (https://n8n38-sbu.veya.co.th):
- `dailynews-ingest-rss.json` — ดึงข่าว RSS ทุก 1 ชม. แล้ว push ไปยัง `/api/ingest/push`
- `dailynews-ingest-api.json` — ดึงข่าว API
- `dailynews-deliver.json` — ส่งข่าวให้สมาชิกผ่าน LINE/Email ทุก 1 นาที
- `dailynews-translate.json` — trigger การแปล (health check)

ตั้ง environment variables ใน n8n:
```
DAILYNEWS_API_BASE_URL=https://ittipolint-sbu.veya.co.th/dailynews
LINE_ACCESS_TOKEN=<from-requirement>
```
และตั้งค่า httpHeaderAuth credential (Authorization Bearer token = N8N_API_KEY หรือ X-N8N-Token) สำหรับทุก node ที่เรียก API DailyNews

> **ทางเลือก:** หากไม่ใช้ n8n สำหรับการส่งข่าว ระบบสามารถใช้ `php artisan dailynews:deliver` ผ่าน schedule ได้เช่นกัน

## 8. Backup (อัตโนมัติ)

```bash
# crontab
0 2 * * * /var/www/dailynews/deploy/backup.sh
```

## 9. Metabase Dashboard (optional)

1. เข้า Metabase → Admin → Databases → Add → PostgreSQL → ชี้ไปที่ `ittipolint_dailynews`
2. สร้าง questions/dashboards:
   - ข่าวต่อแหล่งข่าวต่อวัน/สัปดาห์/เดือน
   - ข่าวที่ส่งออกต่อวัน/สัปดาห์/เดือน
   - สถิติช่องทางส่งข่าว

## 10. การทดสอบ

| หน้า | URL |
|---|---|
| Admin Login | `https://ittipolint-sbu.veya.co.th/dailynews/login` |
| Dashboard | `https://ittipolint-sbu.veya.co.th/dailynews/admin` |
| ค้นหาข่าว | `.../admin/news` |
| Chat AI | `.../chat` |
| API test | `.../api/v1/news` (ด้วย API token) |

### ทดสอบการรับข่าว
```bash
php artisan dailynews:ingest
php artisan dailynews:ingest --source=<slug>
```

### ทดสอบการแปล
```bash
php artisan dailynews:translate --limit=5
```

### ทดสอบการส่งข่าว
```bash
php artisan dailynews:deliver
```

## 11. การรายงาน Credential ใหม่

ตามข้อกำหนด หากการติดตั้งสร้าง/ออก Credential ใหม่ (เช่น Gemini API Key, SMTP, Webhook Secret, Neo4j Password) ต้องรายงานออกมาให้ทราบด้วยทุกครั้ง
