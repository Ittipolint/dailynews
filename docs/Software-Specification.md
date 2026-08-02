# Software Specification — ระบบ DailyNews

**เวอร์ชัน:** 1.0
**วันที่:** 2 สิงหาคม 2026
**ผู้จัดทำ:** ทีมออกแบบระบบ IT (ตามเอกสาร Requirement Specification)
**สถานะ:** อนุมัติเพื่อใช้เป็นแนวทางพัฒนา

---

## 1. ภาพรวมระบบ (System Overview)

DailyNews เป็นแพลตฟอร์มรวบรวมข่าวจากแหล่งข่าวสำคัญทั่วโลกแบบอัตโนมัติ (Background Job) แล้วนำมาบริหารจัดการ แปลภาษา จัดเก็บในฐานข้อมูล และกระจายข่าวไปยังสมาชิกตามช่องทางที่สมาชิกแต่ละรายกำหนด (LINE ส่วนตัว, LINE OA, Email) พร้อมด้วยความสามารถในการค้นหา/ถามตอบข่าวย้อนหลังด้วยระบบ AI Graph RAG และ Dashboard สำหรับผู้ดูแลระบบ

### 1.1 วัตถุประสงค์หลัก
- รวบรวมข่าวจากแหล่งข่าวสำคัญทั่วโลกอย่างอัตโนมัติ
- แปลข่าวจากภาษาต้นทางเป็น 3 ภาษาหลัก ได้แก่ ภาษาไทย ภาษาอังกฤษ ภาษาจีน
- กระจายข่าวไปยังสมาชิกตามช่องทางและเวลาที่สมาชิกกำหนด
- ให้สมาชิกค้นหาและสอบถามข่าวย้อนหลังผ่านระบบ AI Graph RAG
- ให้ผู้ดูแลระบบบริหารจัดการแหล่งข่าว สมาชิก Credential และดูสถิติผ่าน Dashboard

### 1.2 ขอบเขตการทำงาน (Scope)
**ใน Phase นี้:**
- ระบบรับข่าวอัตโนมัติ, จัดการแหล่งข่าว, การแปลภาษา
- ระบบส่งข่าวอัตโนมัติไปยัง LINE ส่วนตัว, LINE OA, Email
- ระบบสมาชิกและประเภทสมาชิก, การตั้งค่าความสนใจข่าว และตารางเวลาส่งข่าว
- ระบบค้นหาข่าวแบบ keyword (Admin) และ AI Graph RAG Chat
- Dashboard สำหรับ Admin
- Reference Data (แหล่งข่าว, สมาชิก, Credential)

**นอกขอบเขตใน Phase นี้ (แต่เตรียมโครงสร้างรองรับไว้):**
- การชำระค่าสมาชิกและการต่ออายุสมาชิก
- ช่องทางส่งข่าวอื่น ๆ นอกเหนือจาก LINE ส่วนตัว, LINE OA, Email

---

## 2. รายละเอียด Feature / Function ของระบบ

### 2.1 Feature การรับข่าวเข้าสู่ระบบ (News Ingestion — Background Job)

#### 2.1.1 รวบรวมข่าวจากแหล่งข่าวสำคัญทั่วโลก
- ระบบต้องสามารถดึงข่าวจากแหล่งข่าวสำคัญระดับโลกได้เบื้องต้น อย่างน้อยดังนี้:
  - **AP News** (RSS) — https://apnews.com/
  - **Reuters** (RSS) — https://www.reuters.com/
  - **BBC News** (RSS) — https://www.bbc.com/news
  - **CNN** (RSS) — https://edition.cnn.com/
  - **The New York Times** (RSS/API) — https://www.nytimes.com/
  - **Al Jazeera** (RSS) — https://www.aljazeera.com/
  - **Xinhua (ซินหัว)** (RSS) — http://www.xinhuanet.com/
  - **CCTV / China Daily** (RSS) — https://www.chinadaily.com.cn/
  - **สำนักข่าวไทย (TNA)** — https://www.tnamcot.com/
  - **Thai PBS** — https://www.thaipbs.or.th/
  - **The Nation / Bangkok Post** — https://www.bangkokpost.com/
- แหล่งข่าวเริ่มต้นให้ import เข้าระบบ (seed data) ผ่าน Reference Data
- แต่ละแหล่งข่าวต้องกำหนด: ชื่อ, ภาษา (Locale), ประเภทการดึงข้อมูล (RSS/API/Web Crawling), URL, สถานะ Active/Inactive, Credential (ถ้ามี), ความถี่ในการดึง

#### 2.1.2 หน้าจอจัดการแหล่งข่าว (News Source Management)
- เพิ่มแหล่งข่าวใหม่
- แก้ไขแหล่งข่าว
- ลบแหล่งข่าว (soft delete)
- ตั้งค่า Active / Inactive
- กำหนดรูปแบบการดึงข่าว (RSS / API / Web Crawling)
- จัดการ Credential ของแหล่งข่าว (เช่น API Key) เก็บแบบเข้ารหัส
- ดูประวัติการดึงข่าวล่าสุดของแต่ละแหล่ง (last fetched, status, error)

#### 2.1.3 เก็บข้อมูลข่าวลงฐานข้อมูล
- เมื่อ n8n ดึงข่าวได้แล้วต้อง mapping ข้อมูลให้เป็นโครงสร้างมาตรฐาน แล้วบันทึกลงฐานข้อมูล Postgres
- ต้องมีการ Deduplication / Normalization เพื่อไม่ให้ข่าวซ้ำ (อ้างอิงจาก URL + hash ของ headline)
- ข้อมูลข่าวต้องเก็บ: title, summary, body/content, source, category, tags, published_at, fetched_at, language ต้นทาง, url, thumbnail
- รองรับการดึงแบบ incremental (เฉพาะข่าวใหม่) และการ re-fetch กรณีผิดพลาด (retry)
- n8n จะต้องทำงานอยู่ตลอดเวลาตามค่าที่ setup ไว้, โดยเริ่มต้นให้ทำงานทุก 1 ชั่วโมง

#### 2.1.4 การแปลข่าวเป็น 3 ภาษาหลัก
- ระบบต้องแปลข่าวจากภาษาต้นทางเป็น:
  - ภาษาไทย (th)
  - ภาษาอังกฤษ (en)
  - ภาษาจีน (zh)
- กลไกการแปล: ใช้ Translation Engine (เลือก opensource หรือ API แปลภาษา) ทำงานเป็น background job ภายหลังการดึงข่าว
- เก็บข้อความแปลไว้ในตาราง news_translations แยกตาม locale เพื่อให้ใช้งานร่วมกับระบบค้นหาและ Graph RAG
- ควรเก็บสถานะการแปล (pending / translated / failed) เพื่อให้สามารถ re-run ได้

#### 2.1.5 หน้าจอ Chat สำหรับค้นหาข่าวย้อนหลัง (AI Graph RAG)
- มีหน้าจอ Chat ให้ผู้ใช้งาน (Admin / สมาชิกตามสิทธิ์) ค้นหาข่าวย้อนหลังด้วยภาษาธรรมชาติ
- ระบบใช้แนวทาง **Graph RAG**:
  - ข่าวแต่ละเรื่องและเอนทิตี (บุคคล องค์กร สถานที่ หัวข้อ) แทนเป็น nodes ใน Graph
  - ความสัมพันธ์ระหว่างข่าว/เอนทิตีแทนเป็น edges
  - ใช้ Vector Embedding เก็บความหมายของข้อความข่าวในแต่ละภาษา (th/en/zh) สำหรับ semantic search
  - คำตอบจาก LLM อ้างอิง (cite) แหล่งข่าวที่เกี่ยวข้องเพื่อตรวจสอบย้อนหลังได้
- ต้องมีการ index ข่าวย้อนหลังทั้ง 3 ภาษาลงใน Vector Store และ Graph Database

#### 2.1.6 Idea เพิ่มเติม (เพิ่มเติมจาก Requirement)
- **หมวดหมู่และแท็กข่าวอัตโนมัติ** — ระบบจัดหมวดหมู่ข่าว (การเมือง เศรษฐกิจ เทคโนโลยี กีฬา ฯลฯ) และแท็กอัตโนมัติด้วย NLP เพื่อช่วยสมาชิกเลือกหัวข้อที่สนใจ
- **การตรวจจับข่าวซ้ำและคุณภาพข่าว** — กรองข่าวซ้ำและข่าวคุณภาพต่ำ (low-quality, duplicate, spam)
- **การตั้ง keyword filter** — กรองข่าวที่ไม่ต้องการ (keyword blocklist) ระดับระบบและระดับสมาชิก
- **ข่าวด่วน (Breaking News)** — ระบบเร่งความถี่ดึงข่าวจากแหล่งสำคัญ และทำเครื่องหมายข่าวสำคัญ

### 2.2 Feature การส่งข่าว (News Delivery — Background Job)

#### 2.2.1 ส่งข่าวไปยังสมาชิกตามช่องทางและเวลาที่กำหนด
- ระบบส่งข่าวล่าสุดไปยังสมาชิกตามช่องทางและตารางเวลาที่สมาชิกแต่ละรายกำหนด
- ระบบส่งข่าวล่าสุดไปยังสมาชิกตามภาษาที่กำหนดไว้
- สมาชิกสามารถตั้ง schedule (วัน/เวลา) สำหรับการส่งข่าวแต่ละประเภท/หัวข้อ
- การส่งทำงานเป็น background job ผ่าน n8n ตามเวลาที่กำหนด

#### 2.2.2 สมาชิกสามารถรับข่าวได้มากกว่า 1 ช่องทาง
- สมาชิก 1 ราย รับข่าวได้หลายช่องทางพร้อมกัน (เช่น LINE OA + Email)
- แต่ละช่องทางของสมาชิกมีสถานะ Active/Inactive และ Credential ของตัวเอง

#### 2.2.3 ช่องทางส่งข่าวเริ่มต้น
- **LINE ส่วนตัว** — ส่งข้อความ/Flex Message ผ่าน LINE Messaging API (กลุ่ม/ผู้รับที่ลงทะเบียน)
- **LINE OA** — ส่งข้อความผ่าน LINE Official Account (broadcast / multi-cast)
- **Email** — ส่งข่าวเป็น HTML email ผ่าน SMTP

#### 2.2.4 Idea เพิ่มเติม (เพิ่มเติมจาก Requirement)
- **Telegram / WhatsApp / Web Push** — ออกแบบโครงสร้าง channel interface ให้ขยายเพิ่มได้ในอนาคต
- **อีเมลสรุปประจำวัน (Daily Digest)** — สรุปข่าวที่น่าสนใจตามหัวข้อที่สมาชิกเลือก ส่งตอนเช้าตามเวลาที่กำหนด
- **Log การส่งและรายงานผล** — บันทึกสถานะการส่งทุกครั้ง (success/fail, error) และ Dashboard แสดงสถิติการส่ง

### 2.3 Feature การค้นหาข่าว (เปิดให้ใช้สำหรับ Admin เท่านั้น)
- หน้าจอค้นหาข่าวด้วย keyword
- ค้นหาได้จาก title, summary, body, tags, category, source, ช่วงวันที่
- แสดงผลแบบ pagination พร้อมข้อมูลต้นทางและลิงก์ไปยังบทความต้นฉบับ
- รองรับการกรองตามภาษา ภาษาแหล่งข่าว และช่วงเวลา

### 2.4 Feature การบริหารจัดการสมาชิก

#### 2.4.1 ประเภทสมาชิก (Member Type)
- ระบบรองรับประเภทสมาชิก อย่างน้อย:
  - สมาชิกองค์กร (Organization)
  - สมาชิกบุคคล (Individual)
- โครงสร้าง member_type ต้องยืดหยุ่นรองรับการเพิ่มประเภทใหม่ได้ในอนาคต
- แต่ละประเภทสามารถกำหนดสิทธิ์/แพ็กเกจที่แตกต่างกันได้

#### 2.4.2 สมาชิกตั้งค่าหัวข้อข่าวที่สนใจ
- สมาชิก login เข้าสู่ระบบ (หรือลงทะเบียน) เพื่อ setup หัวข้อข่าวที่สนใจ (จากหมวดหมู่/แท็ก/keyword)
- ระบบนำหัวข้อที่สมาชิกสนใจไปใช้กรองข่าวเพื่อส่งให้สมาชิก

#### 2.4.3 สมาชิกตั้งค่า Schedule ส่งข่าว
- สมาชิกกำหนดวัน/เวลาที่ต้องการให้ระบบส่งข่าวในแต่ละประเภท/หัวข้อ
- เช่น ส่งข่าวเทคโนโลยีทุกวัน 08:00 น. และสรุปข่าวเช้าทุกวันจันทร์-ศุกร์ 07:30 น.
- ระบบเก็บ schedule ของสมาชิกในตาราง member_schedules และให้ n8n นำไปใช้ในการ trigger การส่ง

#### 2.4.4 การเก็บค่าสมาชิก / ต่ออายุสมาชิก (Phase ถัดไป — เตรียมโครงสร้างไว้)
- **ยังไม่มีใน Phase นี้** แต่ต้องออกแบบโครงสร้างข้อมูลและ module ให้รองรับ:
  - ตาราง subscriptions / member_packages / invoices / payments
  - สถานะสมาชิก: active, expired, trial, suspended
  - แพ็กเกจและสิทธิ์ต่าง ๆ เชื่อมโยงกับ member_type
  - Field ที่จำเป็น เช่น plan_start_date, plan_end_date, payment_gateway, billing_cycle

### 2.5 Feature Dashboard สำหรับ Admin

#### 2.5.1 Idea Dashboard (เพิ่มเติมจาก Requirement)
- ภาพรวมจำนวนข่าวในระบบ (ยอดสะสม, วันนี้, สัปดาห์นี้, เดือนนี้)
- กราฟแนวโน้มจำนวนข่าวต่อวัน (Trend Line)
- สถิติตามหมวดหมู่ข่าว / ภาษา
- สถานะแหล่งข่าว (จำนวนที่ active/inactive, อัตราความสำเร็จการดึง)
- สถิติการส่งข่าวรายช่องทาง (LINE ส่วนตัว / LINE OA / Email)
- จำนวนสมาชิกและการแยกตามประเภทสมาชิก
- แจ้งเตือน error / ความผิดปกติของ background job
- แผนที่/ภูมิภาคแหล่งข่าว (optional)

#### 2.5.2 ข้อกำหนดบังคับขั้นต่ำ (Minimum Requirement)
- จำนวนข่าวที่ดึงมาจากแต่ละ source ในแต่ละวัน/สัปดาห์/เดือน
- จำนวนข่าวที่ส่งออกไปในแต่ละวัน/สัปดาห์/เดือน
- Dashboard ต้องกรองตามช่วงวันที่ และ export เป็น CSV ได้

### 2.6 Feature Reference Data

#### 2.6.1 แหล่งข่าว (News Source)
- เพิ่ม / แก้ไข / ลบ / ตั้งค่า Active-Inactive แหล่งข่าว
- ระบุรูปแบบการดึง: **Web Crawling**, **RSS/API**
- บริหารจัดการ Credential ของแหล่งข่าว (เช่น API key) เก็บแบบเข้ารหัส

#### 2.6.2 สมาชิกและ Credential การส่งข่าว
- เพิ่ม / แก้ไข / ลบ / ตั้งค่า Active-Inactive สมาชิก
- จัดการ Credential ที่จำเป็นต่อการส่งข่าวของสมาชิกแต่ละคน/แต่ละช่องทาง
  - เช่น LINE userId / LINE OA, email address, รายละเอียด SMTP ถ้าจำเป็น

#### 2.6.3 Credential ระบบสำหรับ Admin
- หน้าจอให้ Admin แก้ไข Credential ที่สำคัญของระบบ เช่น
  - Translation API key
  - LLM / Embedding API key
  - n8n API key
  - SMTP ของระบบ
  - LINE Messaging API Credential (Channel ID, Secret, Access Token)

#### 2.6.4 Idea เพิ่มเติม (เพิ่มเติมจาก Requirement)
- **Audit Log** — บันทึกการแก้ไข Reference Data (ใคร แก้ไขอะไร เมื่อไร)
- **ทดสอบการเชื่อมต่อ (Test Connection)** — ปุ่มทดสอบว่า Credential/แหล่งข่าวเชื่อมต่อได้หรือไม่
- **โครงสร้าง Category / Keyword / Tag** — จัดการหมวดหมู่และคีย์เวิร์ดกลางของระบบ

---

## 3. เทคโนโลยีที่ใช้ (Technology Stack)

| ส่วนประกอบ | เทคโนโลยีที่เลือก | หมายเหตุ |
|---|---|---|
| OS ของ Server | **Ubuntu (LTS)** | Opensource |
| Workflow / Background Job (รับข่าว + ส่งข่าว) | **n8n** | Workflow engine, cron trigger, webhook |
| ฐานข้อมูลหลัก | **PostgreSQL** | เก็บข่าว สมาชิก ระบบทั้งหมด |
| Web Application (Frontend + Backend) | **PHP 8.x + Laravel 11** | Framework ที่นิยมและเป็นมาตรฐาน |
| Database ของ Web App | **MySQL** | ตามที่ติดตั้งไว้แล้ว (ใช้สำหรับข้อมูล Web app) |
| Dashboard / BI | **Metabase** หรือ **Grafana** | Opensource ยอดนิยม; แนะนำ Metabase ใช้งานง่าย + query กับ PostgreSQL |
| Frontend Framework | **Vue.js 3** หรือ **Laravel Blade + Alpine.js** | รองรับ SPA/CSR ที่ต้องการ |
| Search Engine | **OpenSearch** (หรือ PostgreSQL FTS สำหรับ Phase 1) | ค้นหา keyword, full-text |
| Vector Store | **pgvector (บน PostgreSQL)** หรือ **Qdrant** | เก็บ embedding สำหรับ Graph RAG |
| Graph Database | **Neo4j** (Community Edition) | ใช้ในระบบ Graph RAG |
| Embedding / LLM | **API แปลภาษา + Embedding** (เช่น Google Translate API / OpenRouter / Ollama) | ใช้ LLM สำหรับแปลและตอบคำถาม |
| Translation | Translation service (opensource เช่น Argos Translate, LibreTranslate หรือ API) | สำหรับแปล 3 ภาษา |
| Message Queue / Scheduler | **Redis** (cache + queue) + **n8n schedules** | สำหรับ background job รับ/ส่งข่าว |
| Web Server | **Nginx** + PHP-FPM | Opensource |
| Container | **Docker / Docker Compose** | จัดการ n8n, Postgres, services ต่าง ๆ |
| CI/CD & Monitoring (optional) | GitHub Actions, Uptime Kuma | สำหรับ Deploy และตรวจสอบความพร้อมใช้งาน |

> **หมายเหตุ:** Web Application หลักใช้ PHP + MySQL ตามข้อกำหนด 2.6 และ 2.7 ขณะที่ Postgres (ข้อกำหนด 2.4) ใช้เป็นฐานข้อมูลหลักสำหรับ n8n เก็บข่าวและข้อมูลโครงสร้าง (news, members, translations) ข้อมูล reference/web ใช้ MySQL

---

## 4. สถาปัตยกรรมระบบ (System Architecture)

```
                        ┌──────────────────────────────────────────┐
                        │             Sources (RSS/API/Web)         │
                        └────────────────────┬─────────────────────┘
                                             │
   ┌───────────────────────┐                 │
   │  n8n Workflow          │  fetch/parse    │
   │  (Ingestion jobs)      ├─────────────────┘
   │  (Scheduler/Cron)      │
   └──────────┬────────────┘
              │ normalize + dedup
              ▼
   ┌───────────────────────┐     ┌───────────────────────┐
   │   PostgreSQL          │     │  Vector Store         │
   │   (news, members,     │────▶│  (pgvector/Qdrant)    │
   │    schedules, ...)    │     └──────────┬────────────┘
   └──────────┬────────────┘                │
              │ translation pipeline        │ embedding
              ▼                             ▼
   ┌───────────────────────┐     ┌───────────────────────┐
   │   Translation Engine   │     │  Graph DB (Neo4j)     │
   │   (th/en/zh)           │     │  Graph RAG index      │
   └──────────┬────────────┘     └──────────┬────────────┘
              │                             │
              ▼                             ▼
   ┌───────────────────────┐     ┌───────────────────────┐
   │  n8n Workflow          │     │  Web App (PHP+Laravel)│
   │  (Delivery jobs)       │     │  - Admin panel        │
   │  send via LINE/Email   │◀────│  - Chat Graph RAG     │
   └──────────┬────────────┘     │  - Dashboard UI       │
              │                  └──────────┬────────────┘
              ▼                             │
   ┌───────────────────────┐                ▼
   │ LINE Personal/OA,      │     ┌───────────────────────┐
   │ Email (SMTP)           │     │  Metabase / Grafana    │
   └───────────────────────┘     └───────────────────────┘
```

### 4.1 หลักการทำงานหลัก (Key Flows)

**Flow 1: รับข่าว (Ingestion)**
1. n8n Cron trigger ตาม schedule ของแต่ละ source → ดึงข้อมูล (RSS/API/Crawl)
2. Parse + Normalize → ตรวจซ้ำ (dedup) → บันทึก Postgres
3. Trigger translation pipeline → แปลเป็น th/en/zh → เก็บ news_translations
4. สร้าง embedding → เก็บ Vector Store
5. สร้าง/อัปเดต nodes & edges ใน Graph DB (Graph RAG index)

**Flow 2: ส่งข่าว (Delivery)**
1. n8n Cron trigger ตาม schedule ของสมาชิกแต่ละราย/แต่ละหัวข้อ
2. Query ข่าวใหม่ที่ตรงกับหัวข้อที่สนใจ
3. สร้างข้อความ/อีเมลตาม channel template
4. ส่งผ่าน LINE Messaging API / SMTP
5. บันทึก delivery log

**Flow 3: Chat Graph RAG**
1. ผู้ใช้ส่งคำถามเป็นภาษาธรรมชาติ
2. ระบบทำ embedding ค้นหา semantic + query graph สำหรับบริบท/เอนทิตี
3. LLM สร้างคำตอบพร้อม citation จากข่าวจริง
4. แสดงผลพร้อมลิงก์อ้างอิงไปยังบทความต้นฉบับ

---

## 5. โครงสร้างฐานข้อมูล (Data Model — เบื้องต้น)

### 5.1 PostgreSQL (ฐานข้อมูลหลัก)
- `news_sources` — id, name, url, locale, fetch_type (rss/api/crawl), cron_expression, credentials (encrypted), is_active, last_fetched_at, created_at, updated_at, deleted_at
- `news` — id, source_id (FK), source_url (unique), title, summary, body, category, tags[], published_at, fetched_at, lang, sentiment (optional), created_at
- `news_translations` — id, news_id (FK), locale (th/en/zh), title, summary, body, translated_at, status
- `categories` / `news_categories` — หมวดหมู่ข่าว
- `members` — id, member_type_id, name, email, status, line_user_id, line_oa_user_id, is_active, created_at, deleted_at
- `member_types` — id, code, name, description (องค์กร/บุคคล ฯลฯ)
- `member_channels` — id, member_id, channel_type (line_personal/line_oa/email), credentials (encrypted), is_active
- `member_interests` — id, member_id, category/keyword/tag, config
- `member_schedules` — id, member_id, schedule_name, cron_expression, channels[], categories, is_active
- `delivery_logs` — id, member_id, channel_type, news_ids[], status, error_message, sent_at
- `subscriptions` (รองรับ Phase ถัดไป) — id, member_id, package_id, plan_start_date, plan_end_date, status, billing_cycle
- `packages` (รองรับ Phase ถัดไป) — id, name, price, currency, features
- `credentials` — id, code, config (encrypted), updated_by, updated_at (เก็บ Credential ระบบ)
- `audit_logs` — id, user_id, action, entity, entity_id, old_value, new_value, created_at

### 5.2 MySQL (ฐานข้อมูล Web App ตามข้อกำหนด 2.6)
- ใช้สำหรับตารางของ Web Application (users/admin, sessions, migrations, config) ตามที่ framework กำหนด

---

## 6. ข้อกำหนดทางเทคนิคและความปลอดภัย

- เก็บ Credential ทั้งหมดแบบเข้ารหัส (encrypted at rest) ไม่แสดงค่าจริงในหน้าจอ (masked)
- ใช้ HTTPS ทุกช่องทาง
- RBAC: แยกสิทธิ์ Admin / Staff / สมาชิก
- Webhook จาก n8n ต้องตรวจสอบ Signature/Secret
- Rate limiting สำหรับ API และหน้า login
- Backup ฐานข้อมูลอัตโนมัติเป็นรายวัน (PostgreSQL + MySQL)
- Error handling: ระบบบันทึก error log และมี retry กลไกใน n8n
- นโยบายความเป็นส่วนตัวของข้อมูลสมาชิก

---

## 7. สภาพแวดล้อมและการติดตั้ง (Deployment & Credentials)

### 7.1 Server Environment
- OS: Ubuntu (ตามข้อกำหนด 2.1)
- Web Server & Database Server: https://ittipolint-sbu.veya.co.th (สร้าง sub-path `/dailynews`)
- FTP Server: IP `119.59.116.53`, User `ittipolint` (Pass ตามที่กำหนดไว้)
- n8n Server: https://n8n38-sbu.veya.co.th

### 7.2 Credential ที่สำคัญ (ตามเอกสาร Requirement)

| รายการ | ค่า |
|---|---|
| **Web Server URL** | `https://ittipolint-sbu.veya.co.th/dailynews` |
| **FTP IP / User** | `119.59.116.53` / `ittipolint` |
| **DB name / User** | `ittipolint_dailynews` / `ittipolint_dailynews` |
| **n8n URL / User** | `https://n8n38-sbu.veya.co.th` / `ittipolint@gmail.com` |
| **n8n API Key** | (ตามเอกสาร — เก็บใน Secret Management) |
| **LINE Channel ID** | `1528339539` |
| **LINE Channel Name** | `Ittipol@` |
| **LINE Channel Secret** | (ตามเอกสาร — เก็บใน Secret Management) |
| **LINE Access Token** | (ตามเอกสาร — เก็บใน Secret Management) |
| **LINE Webhook URL** | `https://n8n38-sbu.veya.co.th/webhook/line` |
| **Email Sender** | `DailyNews` |
| **Email Receive (ทดสอบ)** | `ittipolint@gmail.com` |

> **หมายเหตุ:** ค่า Secret ทั้งหมดถูกอ้างอิงตามเอกสาร Requirement Specification และต้องเก็บไว้ในระบบจัดการความลับ (Secret Store / Environment Variables) ห้าม hardcode ใน source code หรือ commit ลง Git

### 7.3 ข้อกำหนดเรื่อง Credential ใหม่
- กรณีที่ระบบสร้าง/ออก Credential ใหม่ (เช่น Translation API Key, SMTP, Webhook Secret, LLM Key) ให้รายงานออกมาให้ทราบด้วยทุกครั้ง

---

## 8. Roadmap การพัฒนา (Development Phases)

### Phase 1 (Phase นี้)
- ตั้งค่า Server, PostgreSQL, n8n, Web App (Laravel)
- Import แหล่งข่าวเริ่มต้น + workflow รับข่าว (RSS/API)
- ระบบแปลภาษา th/en/zh ใช้ LLM ของ google
- ระบบจัดการสมาชิก + channel LINE/Email + workflow ส่งข่าว
- Dashboard พื้นฐาน (สถิติการรับ/ส่งข่าว) ด้วย Metabase
- ระบบค้นหา keyword (Admin) + เริ่มต้น AI Graph RAG Chat

### Phase 2 (ถัดไป)
- การเก็บค่าสมาชิก / ต่ออายุ (subscription, packages, payment)
- ช่องทางส่งข่าวเพิ่มเติม (Telegram, Web Push)
- ปรับปรุง Graph RAG ให้มีข้อมูลเชิงลึกและ Personalized News

---

## 9. รายการ Idea เพิ่มเติม (Enhancement Ideas)

1. หมวดหมู่/แท็กข่าวอัตโนมัติด้วย NLP
2. ข่าวด่วน (Breaking News) และเร่งความถี่ดึงข่าว
3. Keyword blocklist / filter ระดับระบบและสมาชิก
4. Daily Digest email สรุปข่าวเช้า
5. สถิติพฤติกรรมสมาชิก (คลิก/อ่าน) เพื่อปรับปรุงเนื้อหาที่ส่ง
6. ระบบแจ้งเตือน Admin เมื่อ background job ล้มเหลว (Alerting)
7. Export ข่าวเป็น PDF / CSV
8. ระบบแจ้งเตือนข่าวที่สมาชิกสนใจแบบ real-time ผ่าน Webhook/WebSocket
9. ฟีเจอร์แจ้งเตือนเมื่อมีข่าวที่ตรงกับ keyword ที่สมาชิกติดตาม (Keyword Alert)
10. Graph RAG แบบ Multi-turn conversation และแนะนำข่าวที่เกี่ยวข้อง (Related News)

---

## 10. ผู้มีส่วนได้ส่วนเสียและการอนุมัติ

- **เจ้าของระบบ (Owner):** อ้างอิงตามข้อกำหนด — GitHub: https://github.com/Ittipolint/dailynews
- **เอกสารต้นทาง:** `docs/Prompt.md` (Requirement Specification)
- **เอกสารฉบับนี้:** `docs/Software-Specification.md`

---

**— End of Software Specification —**
