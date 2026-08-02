# DailyNews

DailyNews เป็นแพลตฟอร์มรวบรวมข่าวจากแหล่งข่าวสำคัญทั่วโลกแบบอัตโนมัติ บริหารจัดการ แปลภาษา (ไทย/อังกฤษ/จีน) จัดเก็บในฐานข้อมูล และกระจายข่าวไปยังสมาชิกตามช่องทางที่กำหนด (LINE ส่วนตัว, LINE OA, Email) พร้อมระบบค้นหาข่าวและ AI Graph RAG Chat และ Dashboard สำหรับผู้ดูแลระบบ

> อ้างอิง: [Software Specification](docs/Software-Specification.md) และ [Requirement Specification](docs/Prompt.md)

## โครงสร้างโปรเจกต์

```
dailynews/
├── webapp/                    # Laravel 11 Web Application (Admin Panel + Member Portal)
│   ├── app/                   # Models, Services (Ingestion/Translation/Delivery/RAG), Controllers
│   ├── config/                # การตั้งค่าแอปพลิเคชัน
│   ├── database/
│   │   ├── migrations/        # Schema PostgreSQL (main) + MySQL (framework)
│   │   └── seeders/           # แหล่งข่าวเริ่มต้น, หมวดหมู่, สมาชิก, Credential, Admin
│   ├── resources/views/       # Blade views (Admin / Member / Chat)
│   ├── routes/                # web.php, api.php, console.php
│   └── .env.example
├── n8n/workflows/             # n8n workflow JSON (ingest RSS/API, deliver LINE/Email, translate)
├── docker/                    # docker-compose + Dockerfile (PHP/Nginx/MySQL/Postgres/Redis/n8n/Metabase)
├── deploy/                    # deploy.sh, backup.sh, restore.sh, nginx config
└── docs/                      # เอกสาร Requirement & Software Specification
```

## คุณสมบัติหลัก

- **การรับข่าว (Ingestion)**: ดึงข่าวจากแหล่งข่าวสำคัญระดับโลก (RSS/API/Web Crawling) ผ่าน n8n หรือ Laravel schedule, Deduplication, จัดการแหล่งข่าวได้
- **การแปลภาษา**: แปลข่าวเป็นไทย/อังกฤษ/จีน ด้วย Google Gemini LLM (เก็บใน `news_translations`)
- **การส่งข่าว (Delivery)**: ส่งข่าวล่าสุดไปยังสมาชิกตาม schedule (cron) ผ่าน LINE ส่วนตัว, LINE OA, Email
- **การค้นหาข่าว**: ค้นหา keyword สำหรับ Admin พร้อมกรองตามหมวดหมู่/แหล่งข่าว/ภาษา/ช่วงเวลา
- **AI Graph RAG Chat**: ถามข่าวย้อนหลังด้วยภาษาธรรมชาติ พร้อมการอ้างอิงแหล่งข่าว (citation)
- **Dashboard**: สถิติข่าวที่ดึง/ส่งรายวัน-สัปดาห์-เดือน, แนวโน้ม, ตามแหล่งข่าว/หมวดหมู่/ช่องทาง, Export CSV
- **การบริหารสมาชิก**: ประเภทสมาชิก, ช่องทาง, หัวข้อที่สนใจ, ตารางเวลาส่งข่าว (โครงสร้างรองรับ Subscription Phase 2)
- **Reference Data**: แหล่งข่าว, หมวดหมู่, Credential (เก็บแบบเข้ารหัส)

## เริ่มต้นใช้งาน (Local / Docker)

```bash
cd docker
cp .env.example .env
docker compose up -d --build
```

จากนั้น:

| บริการ | URL |
|---|---|
| Web App (Nginx + PHP) | http://localhost:8080 |
| n8n | http://localhost:5678 |
| Metabase | http://localhost:3000 |
| PostgreSQL | localhost:5432 |
| MySQL | localhost:3306 |

### ติดตั้ง Laravel dependencies และ initialize

```bash
cd webapp
composer install
cp .env.example .env
php artisan key:generate
# แก้ไข .env (DB, PGSQL, credentials)
php artisan migrate --force
php artisan migrate --database=pgsql --force
php artisan db:seed --force
php artisan serve
```

## Deployment บน Ubuntu (Production)

ดูรายละเอียดใน [docs/INSTALLATION.md](docs/INSTALLATION.md)

```bash
bash deploy/deploy.sh
```

## Credential / สภาพแวดล้อม

ดูตาราง Credential ใน [Software Specification](docs/Software-Specification.md) section 7.
Credential ทั้งหมดเก็บใน environment variables / Secret Management และไม่ commit ลง Git

## License

MIT
