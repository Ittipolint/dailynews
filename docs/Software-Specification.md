# Software Specification — ระบบ DailyNews

**เวอร์ชัน:** 1.3
**วันที่:** 6 สิงหาคม 2026
**ผู้จัดทำ:** ทีมออกแบบระบบ IT (ตามเอกสาร Requirement Specification)
**สถานะ:** อนุมัติเพื่อใช้เป็นแนวทางพัฒนา (ปรับปรุงให้ตรงกับระบบที่พัฒนาจริง/Production)

> **หมายเหตุฉบับ 1.3:** ปรับปรุงจาก 1.2 (6 ส.ค. 2026) เพิ่มการจัดหมวดหมู่ข่าวอัตโนมัติตามเนื้อหา (CategoryClassifier) และการกรองข่าวตามหมวดหมู่ที่ตั้งค่าของแต่ละแหล่งข่าวระหว่างขั้นตอน ingestion ดูหัวข้อ [2.1.3](#213-เก็บข้อมูลข่าวลงฐานข้อมูล) และ [2.1.4](#214-การจัดหมวดหมู่และกรองข่าวตามหมวดหมู่)

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
- ระบบดึงข่าวจากแหล่งข่าวระดับโลกผ่าน 3 กลไก: **RSS** (`rss`), **API** (`api`), และ **Web Crawling** (`crawl`) ตามค่า `fetch_type` ของแหล่งข่าว
- แหล่งข่าวที่ seed ไว้ในระบบ (ตาม `NewsSourcesSeeder`) มี 14 รายการ:
  - **AP News** (RSS) — https://apnews.com/
  - **BBC News** (RSS) — https://www.bbc.com/news
  - **CNN** (RSS) — https://edition.cnn.com/
  - **Al Jazeera** (RSS) — https://www.aljazeera.com/
  - **The Guardian** (RSS) — https://www.theguardian.com/
  - **The New York Times** (RSS) — https://www.nytimes.com/
  - **Xinhua (ซินหัว)** (RSS) — http://www.xinhuanet.com/english/
  - **China Daily** (RSS) — https://www.chinadaily.com.cn/
  - **Reuters** (RSS ผ่าน Google News RSS) — https://www.reuters.com/
  - **Investing.com** (RSS) — https://www.investing.com/
  - **Thai PBS** (RSS) — https://www.thaipbs.or.th/
  - **Bangkok Post** (RSS) — https://www.bangkokpost.com/
  - **MGR Online** (RSS) — https://mgronline.com/
  - **TechCrunch** (Web Crawling ด้วย CSS selectors) — https://techcrunch.com/
- หมายเหตุ: แหล่งข่าว seed ทั้งหมดมี `is_active = true` แต่ Admin สลับเป็น Inactive ได้; ใน Production ณ ส.ค. 2026 มีแหล่งข่าว active จริงเพียง 3 รายการ (Bangkok Post RSS, Bloomberg Technology API demo, Thai PBS) ขึ้นกับข้อมูลปัจจุบันในตาราง `news_sources`
- แต่ละแหล่งข่าวเก็บ: ชื่อ, slug (unique), ภาษา (locale), fetch_type (rss/api/crawl), feed_url, cron_expression, config (เช่น CSS selectors ของ crawl), credentials (json), สถานะ Active/Inactive, last_fetched_at, last_status, last_error

#### 2.1.2 หน้าจอจัดการแหล่งข่าว (News Source Management)
- เพิ่ม / แก้ไข / ลบ (soft delete) / ตั้งค่า Active-Inactive แหล่งข่าว
- กำหนดรูปแบบการดึงข่าว (RSS / API / Web Crawling) และ URL/feed_url
- จัดการ Credential ของแหล่งข่าว (เช่น API key) เก็บแบบเข้ารหัส (CREDENTIAL_ENCRYPTION_KEY)
- ปุ่ม "ทดสอบ" (Test Connection) ต่อแหล่งข่าว
- **ปุ่ม "ดึงข่าวทันที" (Fetch Now)** — Admin กดปุ่มในแต่ละแถวเพื่อเรียก n8n webhook `POST /webhook/dailynews-fetch-now` ให้ดึงข่าวจากแหล่งนั้นทันที
  - Laravel ส่ง `POST` ไปยัง n8n webhook พร้อมข้อมูลแหล่งข่าว (`slug, name, url, feed_url, fetch_type, locale`) โดยใช้ `httpHeaderAuth` (DailyNews API Token)
  - n8n workflow (active ใน prod) ตรวจ `fetch_type`: `rss` → parse feed ตรง; `api` → เรียก API แล้ว normalize; แล้วส่งกลับ `POST /dailynews/api/ingest/push` เพื่อบันทึก/เด็ดซ้ำ
  - ระบบอัปเดต `last_fetched_at`, `last_status`, `last_error` ทันที
  - หาก webhook ถูกปิดใน n8n จะแสดง error 502/ข้อความแจ้งเตือน

#### 2.1.3 เก็บข้อมูลข่าวลงฐานข้อมูล
- **ฐานข้อมูลเดียว: MySQL/MariaDB** (`ittipolint_dailynews`) — ตาม `DB_CONNECTION=mysql` ใน .env Production (ไม่ใช้ PostgreSQL สำหรับข้อมูลหลัก)
- n8n ดึงข่าวแล้ว push มาที่ `POST /api/ingest/push` หรือ Laravel เรียก `dailynews:ingest` → `IngestionService` mapping เป็นโครงสร้างมาตรฐานแล้วบันทึก
- **Deduplication** อ้างอิงจาก `source_url` (unique) + `content_hash` (indexed)
- ข้อมูลข่าวเก็บ: title, summary, body, source_id, category, tags (json), thumbnail, lang, sentiment, is_breaking, published_at, fetched_at, source_url
- **การจัดหมวดหมู่ข่าวอัตโนมัติ (CategoryClassifier):** ทุกรายการที่รับเข้า (ทั้งผ่าน `IngestApiController::push` จาก n8n และ `IngestionService::storeItems` จาก `dailynews:ingest`) ผ่าน `App\Services\Ingestion\CategoryClassifier::classify($title, $summary)` เพื่อจัดหมวดหมู่จากเนื้อหาจริง (keyword EN+TH) ก่อนบันทึก — ครอบคลุม 9 หมวด: business, entertainment, general, health, politics, science, sports, technology, world; ถ้าจับคู่ไม่ได้ fallback เป็น `general`
- **การกรองตามหมวดหมู่ของแหล่งข่าว:** ระบบอ่านหมวดหมู่ที่ตั้งไว้ใน `news_sources.category` (ค่าเป็น comma-separated list) แล้วเก็บเฉพาะรายการที่ `CategoryClassifier` จัดหมวดได้ตรงกับรายการใดรายการหนึ่ง; รายการที่ไม่ตรงจะถูก **ตัดทิ้ง (ไม่บันทึก)** — เช่น Thai PBS ตั้ง category = `technology` → ระบบจะเก็บเฉพาะข่าวที่จัดเป็น `technology` เท่านั้น (ดู 2.1.4)
- การดึงเป็นแบบ incremental (เฉพาะรายการใหม่); รองรับ retry เมื่อเกิดข้อผิดพลาด
- กลไกการดึง: **Laravel scheduled job `dailynews:ingest` (ทุก 1 ชั่วโมง)** เป็นตัวหลัก; n8n ingest workflow มีใน repo แต่ถูกตั้งเป็น inactive ใน prod

#### 2.1.4 การจัดหมวดหมู่และกรองข่าวตามหมวดหมู่ (Category Classification & Filtering)
- **ที่มา:** เดิม `News.category` ถูกเขียนด้วยค่า `$source->category` ตรง ๆ (ซึ่งเป็น comma-list ครอบเกือบทุกหมวด เช่น `business,entertainment,general,...,sports,technology,world`) ทำให้การกรองข่าวตามหมวดหมู่ไร้ความหมาย เนื่องจากทุกข่าวได้หมวดตาม list ทั้งหมดของ source
- **พฤติกรรมใหม่:** ตอนบันทึกทุก item จะคำนวณ `category` จากเนื้อหาจริงผ่าน `CategoryClassifier` (น้ำหนัก keyword: title 2-3, summary 1-2, คำยาวมีน้ำหนักมากกว่า; ภาษาไทย+อังกฤษ; fallback `general`) และ `configuredCategories($source)` จะ split ค่า `news_sources.category` ด้วย `,` — ถ้า list ไม่ว่างและหมวดที่คำนวณไม่อยู่ใน list จะ **skip (ไม่บันทึก)** item นั้น
- **ผลจริงใน Production:** แหล่งข่าว Thai PBS (crawl) ตั้ง category = `technology` + URL หน้าเทคโนโลยี → เมื่อ Fetch Now ระบบเก็บได้เฉพาะบทความที่จัดเป็น `technology` (เช่น SONP, TechForge 2026) และตัดข่าวอวกาศ/ดาราศาสตร์ที่จัดเป็น `science` ทิ้งโดยอัตโนมัติ (ยืนยันจาก ingest log `stored:2` และ admin news filter)
- **ข้อควรทราบ:** แหล่งข่าว RSS/API ทั่วไปที่ต้องการรับข่าวทุกหมวด ควรตั้ง category ครอบครบทุกรายการที่มี (หรือปล่อยว่าง) เพื่อไม่ให้ถูกกรองออก

#### 2.1.5 การแปลข่าวเป็น 3 ภาษาหลัก
- ระบบแปลข่าวจากภาษาต้นทางเป็นไทย (th), อังกฤษ (en), จีน (zh)
- กลไกการแปล: **TranslationService (Google Gemini LLM, model `gemini-2.5-flash`)** ทำงานเป็น background job หลังการดึงข่าว
- **การทำงานจริง: Laravel scheduled job `dailynews:translate` (ทุก 1 ชั่วโมง, `--limit=20`)** เป็นตัวดำเนินการหลัก; n8n translate workflow มีใน repo แต่ inactive (ทำหน้าที่ health check เท่านั้น)
- เก็บข้อความแปลในตาราง `news_translations` แยกตาม locale; unique(news_id, locale)
- สถานะการแปล (pending/translated/failed) เก็บใน `news_translations.status` + `error_message` เพื่อ re-run ได้
- รองรับ batch translation (TRANSLATION_BATCH_SIZE=5) และ retry (TRANSLATION_RETRY_ATTEMPTS=3) + backoff
- **ข้อจำกัด (พบจริง)**: Gemini free tier quota จำกัด 20 request/model/วัน → เมื่อหมด quota การแปลจะ "ข้ามแล้วใช้ต้นฉบับ" (translate-on-delivery fallback) หรือ fail แล้วรอ retry รอบถัดไป

#### 2.1.6 หน้าจอ Chat สำหรับค้นหาข่าวย้อนหลัง (AI Graph RAG)
- มีหน้าจอ Chat (`GET /chat` + `POST /chat/ask`) สำหรับผู้ใช้ที่ login ค้นหาข่าวย้อนหลังด้วยภาษาธรรมชาติ
- **การทำงานจริง (ณ ส.ค. 2026) ใช้ "RAG แบบเบา" (keyword + entity retrieval) ไม่ใช่ Graph DB จริง:**
  - `GraphRagService::retrieve()` ค้นหาจาก `title/summary/body` + `news_translations` ด้วย SQL LIKE บน keyword และ entity ที่สกัดจากคำถาม (หยุดคำภาษาไทย/อังกฤษ)
  - `GraphRagService::extractKeywords()` แยก keyword + สไลด์ n-gram สำหรับภาษาไทย/จีน (ไม่มีช่องว่างระหว่างคำ)
  - `buildContext()` สร้างบริบทข่าวพร้อม source + วันที่ + URL
  - `generateAnswer()` ส่ง prompt ไป LLM (Gemini `gemini-2.5-flash`) ผ่าน `services.llm.*` ให้ตอบพร้อม citation [index]; **ถ้า LLM ไม่พร้อม (quota หมด / key ว่าง) จะ fallback ส่งรายการข่าวที่เกี่ยวข้อง ("พบข่าวที่เกี่ยวข้องดังนี้...")**
  - Response ประกอบด้วย `answer`, `sources[]` (id, title, url, source, published_at, relevance), `entities`, `keywords`
- ยังไม่มี: จริง ๆ ไม่มี pgvector/embedding/Neo4j ต่อในเวิร์กโฟลว์นี้ (config มี NEO4J_* และ EMBEDDING_* ไว้ แต่ `GraphRagService` ไม่ได้เรียกใช้) — ถือเป็นส่วน "เตรียมโครงสร้าง" สำหรับ Phase 2

#### 2.1.7 Idea เพิ่มเติม (เพิ่มเติมจาก Requirement)
- **หมวดหมู่และแท็กข่าวอัตโนมัติ** — ระบบจัดหมวดหมู่ข่าว (การเมือง เศรษฐกิจ เทคโนโลยี กีฬา ฯลฯ) และแท็กอัตโนมัติด้วย NLP เพื่อช่วยสมาชิกเลือกหัวข้อที่สนใจ
- **การตรวจจับข่าวซ้ำและคุณภาพข่าว** — กรองข่าวซ้ำและข่าวคุณภาพต่ำ (low-quality, duplicate, spam)
- **การตั้ง keyword filter** — กรองข่าวที่ไม่ต้องการ (keyword blocklist) ระดับระบบและระดับสมาชิก
- **ข่าวด่วน (Breaking News)** — ระบบเร่งความถี่ดึงข่าวจากแหล่งสำคัญ และทำเครื่องหมายข่าวสำคัญ

### 2.2 Feature การส่งข่าว (News Delivery — Background Job)

#### 2.2.1 ส่งข่าวไปยังสมาชิกตามช่องทางและเวลาที่กำหนด
- ระบบส่งข่าวล่าสุดไปยังสมาชิกตามช่องทางและตารางเวลาที่สมาชิกแต่ละรายกำหนด
- ระบบส่งข่าวล่าสุดไปยังสมาชิกตามภาษาที่กำหนดไว้ (`preferred_locale`)
- สมาชิก/Admin ตั้ง schedule (วัน/เวลา) สำหรับการส่งข่าวแต่ละประเภท/หัวข้อผ่านตาราง `member_schedules`
- **การทำงานจริง: Laravel scheduled job `dailynews:deliver` (ทุก 1 นาที)** เป็นตัวส่งหลัก — เรียก `DeliveryService::deliverScheduleNow()` ตาม schedule ที่ครบกำหนด; n8n มี webhook "Trigger Deliver (Laravel)" ที่เรียก API ให้ trigger การส่งเช่นกัน ส่วน n8n deliver workflow (`dailynews-deliver.json`) มีใน repo แต่ถูกตั้ง inactive ใน prod

#### 2.2.2 สมาชิกสามารถรับข่าวได้มากกว่า 1 ช่องทาง
- สมาชิก 1 ราย รับข่าวได้หลายช่องทางพร้อมกัน (เช่น LINE OA + Email)
- แต่ละช่องทางของสมาชิกมีสถานะ Active/Inactive และ Credential ของตัวเอง (ตาราง `member_channels`)

#### 2.2.3 การแปลข่าวให้ตรงกับภาษาสมาชิกก่อนส่ง (Translation on Delivery)
- ข่าวทุกชิ้นที่ส่งให้สมาชิก ต้องอยู่ในภาษา `preferred_locale` ของสมาชิกคนนั้น (th / en / zh)
- ระบบส่งข่าว (ทั้งแบบ Schedule และปุ่มส่งข่าวทันที) ตรวจก่อนส่งว่าแต่ละข่าวมี translation ในภาษาสมาชิกหรือไม่; ถ้ายังไม่มีให้แปลทันทีด้วย TranslationService (Gemini) แล้วค่อยส่ง
- ถ้าภาษาต้นทางของข่าวเท่ากับภาษาสมาชิก จะใช้ข้อความต้นฉบับ (ไม่เสียค่า API)
- ถ้า Gemini quota หมด → ระบบข้ามการแปลและส่งเนื้อหาต้นฉบับ (log WARNING "Translation skipped, sending original")
- ผลการแปลเก็บในตาราง `news_translations` เพื่อใช้ซ้ำในครั้งถัดไป

#### 2.2.3 ช่องทางส่งข่าวเริ่มต้น (ChannelType enum: line_personal / line_oa / email)
- **LINE ส่วนตัว** (`line_personal`) — ส่งผ่าน LINE Messaging API ไปยัง `line_user_id` (ขึ้นต้นด้วย `U...`)
- **LINE OA** (`line_oa`) — ส่งผ่าน LINE Official Account; เก็บ credential ต่อสมาชิก (`line_oa_basic_id`, `line_oa_channel_id`, `line_oa_channel_secret`) และรองรับ broadcast delivery
- **Email** (`email`) — ส่งข่าวเป็น HTML email ผ่าน SMTP
- การส่งจริงใน Production: LINE ใช้ LINE Messaging API credential จาก `services.line.*`; email ใช้ SMTP ตาม `MAIL_*` (หมายเหตุ: prod `MAIL_USERNAME/PASSWORD` ว่างอยู่ ต้องตั้งค่าก่อนจึงส่ง Email ได้จริง)

#### 2.2.4 Idea เพิ่มเติม (เพิ่มเติมจาก Requirement)
- **Telegram / WhatsApp / Web Push** — โครงสร้าง channel interface รองรับการขยาย (ChannelType enum + member_channels)
- **อีเมลสรุปประจำวัน (Daily Digest)** — ยังไม่พัฒนา
- **Log การส่งและรายงานผล** — บันทึก `delivery_logs` (channel_type, news_ids, status, error_message, sent_at, schedule_id) และ Dashboard แสดงสถิติการส่ง; มีปุ่มส่งข่าวทันทีในหน้าตารางเวลา (schedule page) ให้เรียก `POST schedules/{schedule}/send-news`

### 2.3 Feature การค้นหาข่าว (สำหรับผู้ที่มีสิทธิ์เมนู `news`; admin ทุกคนเข้าได้)
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
- สมาชิก/Admin กำหนดชื่อ schedule, ความถี่ (ทุกวัน / รายสัปดาห์ / รายเดือน), เวลา, จำนวนข่าวสูงสุด (limit), ช่องทาง และหมวดหมู่ที่สนใจ (ไม่เลือก = ทั้งหมด)
- ระบบแปลงตัวเลือกเป็น cron expression อัตโนมัติ (daily/weekly/monthly) และให้แก้ไข cron เองได้ในโหมดขั้นสูง
- ระบบเก็บ schedule ในตาราง `member_schedules` (name, cron_expression, channels[], categories[], languages[], limit, is_active) และ Laravel scheduled job `dailynews:deliver` (ทุกนาที) ใช้ในการ trigger การส่ง
- Admin จัดการ schedule ผ่านหน้า `admin/members/{member}/schedules`: เพิ่ม, **แก้ไข** (หน้า `schedules/{schedule}/edit`, PATCH), ลบ, เปิด/ปิด และปุ่มส่งข่าวทันที (ดู 2.4.4)

#### 2.4.4 ปุ่มส่งข่าวทันที (Send News Now)
- Admin กดปุ่ม **"ส่งข่าว"** ที่แถวของ **schedule แต่ละอัน** ในหน้า ตารางเวลาส่งข่าว (`POST schedules/{schedule}/send-news`) เพื่อส่งข่าวตาม **กฎของ schedule นั้น** ทันที โดยไม่ต้องรอรอบ Schedule
- การทำงานจริง (ณ ส.ค. 2026) เปลี่ยนจากแนวคิด "ข่าว Lot 10 นาที" เป็น: ใช้กฎของ schedule นั้น (categories, keyword interests ของสมาชิก, limit) ผ่าน `DeliveryService::deliverScheduleNow($schedule)`
- ระบบจะส่งไปยัง **ช่องทางที่ระบุไว้ใน schedule นั้น** (LINE ส่วนตัว / LINE OA / Email) ตาม `member_channels` ที่เปิดใช้งาน
- เมื่อกดปุ่มแล้ว Admin จะเห็นผลการส่ง (สำเร็จ/ล้มเหลว พร้อมจำนวนข่าวและช่องทาง) ผ่าน popup + reload
- ทุกครั้งที่ส่งบันทึก `delivery_logs` (channel_type, news_ids, status, error_message, sent_at, schedule_id, member_id)
- ทุกครั้งที่ส่งบันทึก AuditLog (`member_schedule`, `send_news`) พร้อมช่องทางและจำนวนข่าว
- กรณีไม่มีข่าว / ไม่มีช่องทางที่เปิดใช้งาน / สมาชิกถูกปิดใช้งาน → ตอบ HTTP 422 พร้อมข้อความแจ้ง Admin
- ข่าวที่ส่งให้สมาชิกแปลเป็นภาษาสมาชิก (preferred_locale) ก่อนส่งเสมอ (ดู 2.2.3); ถ้า quota หมด → ส่งต้นฉบับ
- หมายเหตุ: LINE ต้องใช้ LINE userId (ขึ้นต้น `U...`) ที่ถูกต้อง / LINE OA credential; Email ต้องตั้ง SMTP ครบก่อน
- ข่าวที่ส่งให้สมาชิกจะถูกแปลเป็นภาษาที่สมาชิกตั้งค่า (preferred_locale) ก่อนส่งเสมอ (ดูหัวข้อ 2.2.3)
- หมายเหตุ: การส่งจริงผ่าน LINE ต้องใช้ LINE userId (ขึ้นต้นด้วย `U...`) ที่ถูกต้อง และ Email ต้องตั้งค่า SMTP credentials ให้พร้อมก่อน

#### 2.4.5 การเก็บค่าสมาชิก / ต่ออายุสมาชิก (Phase ถัดไป — เตรียมโครงสร้างไว้)
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

#### 2.6.4 การจัดการผู้ใช้ (User Management) — เพิ่มเติมจาก Requirement
- หน้าจอ `GET /admin/users` (เมนู "จัดการผู้ใช้") ให้ Admin เพิ่ม / แก้ไข / ลบผู้ใช้ระบบ
- ฟิลด์: name, username (ใช้ล็อกอินได้), email (ใช้ล็อกอินได้), password (ตั้งใหม่ตอนเพิ่ม/แก้ไข; เว้นว่าง = ไม่เปลี่ยน), role (admin/staff/user), permissions (เฉพาะ role ที่ไม่ใช่ admin — กำหนดสิทธิ์ตามเมนูได้แก่ `dashboard/news/chat/sources/members/categories/credentials/users`)
- ล็อกอิน (`/login`) ใช้ช่องเดียว "รหัสผู้ใช้ / อีเมล" โดยลอง match กับ `email` หรือ `username` แล้วตรวจรหัสผ่านด้วย `Hash::check`
- บทบาท `admin` = เข้าถึงทุกเมนู (permissions ถูกบังคับเป็นเมนูทั้งหมดอัตโนมัติ); `staff` = ผู้ดูแลแต่ไม่ใช่ admin (ต้องกำหนด permissions); `user` = ผู้ใช้ทั่วไปตาม permissions
- Guardrail: กันลบบัญชีตัวเอง, กันลบ/ลดสิทธิ์ผู้ดูแลระบบคนสุดท้าย (เหลือ admin < 1 ไม่ได้), Sidebar แสดงเฉพาะเมนูที่ผู้ใช้มีสิทธิ์
- Seed ผู้ใช้เริ่มต้น (`UsersSeeder`): `ittipolint@gmail.com` (admin, ทุกเมนู, รหัสผ่านเดิมคงเดิม), `admin`/`10203040` (admin, ทุกเมนู), `user1`/`10203040` (user, `dashboard/news/chat`)

#### 2.6.5 Idea เพิ่มเติม (เพิ่มเติมจาก Requirement)
- **Audit Log** — บันทึกการแก้ไข Reference Data (ใคร แก้ไขอะไร เมื่อไร)
- **ทดสอบการเชื่อมต่อ (Test Connection)** — ปุ่มทดสอบว่า Credential/แหล่งข่าวเชื่อมต่อได้หรือไม่
- **โครงสร้าง Category / Keyword / Tag** — จัดการหมวดหมู่และคีย์เวิร์ดกลางของระบบ

---

## 3. เทคโนโลยีที่ใช้ (Technology Stack)

| ส่วนประกอบ | เทคโนโลยีที่ใช้จริง (Production) | หมายเหตุ |
|---|---|---|
| OS ของ Server | **Shared Linux hosting (cPanel-style, domains/…/public_html)** | Production ไม่ใช่ Ubuntu เฉพาะ |
| Web Server | **Apache (ผ่าน cPanel)** + PHP-FPM | `deploy/nginx-dailynews.conf` มีไว้สำหรับ Ubuntu option |
| Web Application (Backend) | **PHP 8.3.x + Laravel 12** (`php: ^8.2`, runtime 8.3.32) | Framework มาตรฐาน |
| ฐานข้อมูล (หลัก + ทั้งหมด) | **MySQL / MariaDB** (เดียว) | `DB_CONNECTION=mysql`, DB `ittipolint_dailynews`; เก็บข่าว สมาชิก แหล่งข่าว แปลภาษา schedule log ทั้งหมด |
| Workflow / Background Job | **Laravel Console Scheduler (`schedule:run`) + n8n (webhook เท่านั้น)** | `dailynews:ingest`/`translate` (รายชั่วโมง) + `dailynews:deliver` (ทุกนาที); n8n ใช้สำหรับ Fetch Now webhook + trigger deliver |
| AI / LLM / Translation | **Google Gemini** (`gemini-2.5-flash`) | แปลข่าว 3 ภาษา + ตอบคำถาม chat; embedding config มีไว้ (gemini-embedding-001) แต่ยังไม่เชื่อม workflow |
| Frontend | **Laravel Blade + Bootstrap 5 (Bootstrap Icons)** + vanilla JS/AJAX | ไม่ใช้ Vue.js |
| Search (Admin) | **MySQL `LIKE` + filters** (`NewsSearchService`) | ค้นหา title/summary/body + กรองหมวด/แหล่ง/ภาษา/ช่วงเวลา |
| Cache / Session / Queue | **File** (`CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`) | ไม่ใช้ Redis ใน Production |
| Notification Channel | **LINE Messaging API** + **SMTP Email** | ผ่าน `services.line.*` / `MAIL_*` |
| Web Server (ทางเลือก Ubuntu) | **Nginx + PHP-FPM** | `deploy/` (ท้องถิ่น/Ubuntu) |
| Container | **Docker / Docker Compose — เฉพาะ Local Dev เท่านั้น** | `docker/docker-compose.yml` มี n8n, Postgres, MySQL, Redis, Metabase แต่ **ไม่ได้ใช้ใน Production** (ดูหัวข้อ Docker) |
| Dashboard / BI | **Laravel Blade Dashboard** (สถิติ + Export CSV) | มี API `dashboard/stats` + `dashboard/export`; Metabase มีใน docker local เท่านั้น |
| Database ฝั่ง n8n | **PostgreSQL** (เฉพาะ service n8n/Metabase ใน Docker local) | ไม่ใช่ฐานข้อมูลหลักของแอป |
| Search semantic | PostgreSQL FTS / pgvector / Neo4j ยัง **ไม่นำมาใช้** ใน workflow ปัจจุบัน | config มี NEO4J_* / EMBEDDING_* / VECTOR_* เหลือไว้สำหรับ Phase 2 |

> **ข้อเท็จจริงสำคัญ:** แอปหลักใน Production ใช้ **MySQL/MariaDB ฐานเดียว** (เก็บข้อมูลทุกอย่างของแอป) ตาม `DB_CONNECTION=mysql`. PostgreSQL/Redis นั้น **ปรากฏเฉพาะใน docker-compose (Local Dev)** สำหรับ n8n/Metabase และไม่ได้เป็นฐานข้อมูลหลักของแอปใน Production. ส่วน Neo4j/pgvector/embedding เป็นการ "เตรียมโครงสร้าง" ยังไม่ต่อเข้าเวิร์กโฟลว์จริงในส.ค. 2026

---

## 4. สถาปัตยกรรมระบบ (System Architecture)

```
                        ┌──────────────────────────────────────────┐
                        │          Sources (RSS/API/Web)            │
                        └───────────────┬──────────────────────────┘
                                        │
              ┌─────────────────────────┼──────────────────────────┐
              │ n8n (webhook only)      │ Laravel scheduled jobs   │
              │ "Fetch Source Now"      │ dailynews:ingest (hourly)│
              │ dailynews-fetch-now     │ dailynews:translate      │
              └──────────┬──────────────┴──────────────┬───────────┘
                         │ POST /api/ingest/push       │ IngestionService
                         ▼                             ▼
                 ┌───────────────────────────────────────────────┐
                 │        MySQL / MariaDB  (ittipolint_dailynews)│
                 │  news, news_translations, news_sources,       │
                 │  members, member_channels, member_schedules,  │
                 │  member_interests, delivery_logs, credentials │
                 └───────────────┬───────────────────────────────┘
                                 │
                    ┌────────────┴─────────────┐
                    ▼                           ▼
        ┌─────────────────────────┐  ┌───────────────────────────┐
        │ TranslationService      │  │ DeliveryService            │
        │ (Gemini gemini-2.5-flash│  │ (dailynews:deliver, every  │
        │  → news_translations)   │  │  1 min; schedule-based)    │
        └─────────────────────────┘  └─────────────┬─────────────┘
                                                   ▼
                    ┌──────────────────────────────────────────┐
                    │  Channels: LINE Personal / LINE OA / Email│
                    └──────────────────────────────────────────┘

        ┌──────────────────────────────────────────┐
        │  Web App (Laravel 12 + Blade + Bootstrap)│
        │   - Admin panel / Dashboard (stats+CSV)  │
        │   - News Search (Admin, MySQL LIKE)      │
        │   - Chat AI (GraphRagService: keyword    │
        │     retrieval + Gemini LLM w/ citation)  │
        └──────────────────────────────────────────┘
```

### 4.1 หลักการทำงานหลัก (Key Flows)

**Flow 1: รับข่าว (Ingestion)**
1. Trigger: Laravel schedule `dailynews:ingest` (ทุก 1 ชม.) หรือ n8n webhook "Fetch Source Now" (กดปุ่มในหน้า Admin)
2. `IngestionService` ดึงตาม `fetch_type` (rss/api/crawl) — RSS ผ่าน HTTP + parse XML, API ผ่าน HTTP + normalize, Crawl ผ่าน CSS selectors
3. Mapping + Deduplication (`source_url` unique + `content_hash`) → บันทึก MySQL
4. `dailynews:translate` (ทุก 1 ชม.) แปล pending เป็น th/en/zh ลง `news_translations` (Gemini)

**Flow 2: ส่งข่าว (Delivery)**
1. Laravel schedule `dailynews:deliver` (ทุกนาที) ตรวจ `member_schedules` ที่ครบกำหนด (หรือ Admin กดปุ่มส่งข่าวทันทีในหน้า schedule)
2. `DeliveryService::deliverScheduleNow()` Query ข่าวตามกฎ schedule (categories / interests ของสมาชิก / limit)
3. ตรวจ/แปลภาษาให้ตรง `preferred_locale` (Gemini; ถ้า quota หมดส่งต้นฉบับ)
4. ส่งผ่าน LINE Messaging API (personal/OA) หรือ SMTP email ตาม `member_channels`
5. บันทึก `delivery_logs` + `audit_logs`

**Flow 3: Chat AI (RAG แบบเบา)**
1. ผู้ใช้ส่งคำถาม (`POST /chat/ask`)
2. `GraphRagService` สกัด keyword/entity (รวม n-gram สำหรับภาษาไทย/จีน) → query MySQL (title/summary/body + translations) ด้วย LIKE
3. buildContext → Gemini `generateContent` สร้างคำตอบพร้อม citation [index]
4. แสดงคำตอบ + `sources[]` (ลิงก์บทความต้นฉบับ); ถ้า LLM ล้มเหลว → fallback รายการข่าวที่เกี่ยวข้อง

---

## 5. โครงสร้างฐานข้อมูล (Data Model)

### 5.1 ฐานข้อมูลหลัก: MySQL/MariaDB (`ittipolint_dailynews`) — ตาม `DB_CONNECTION=mysql`
ตารางแอปพลิเคชัน (สร้างโดย migration `2026_08_01_000001_create_dailynews_core_tables.php`):
- `news_sources` — id, name, slug, url, locale, fetch_type (rss/api/crawl), feed_url, cron_expression, credentials (json), config (json), category (comma-separated list ของหมวดที่ต้องการรับ; เอกสารจริงระบบกรองข่าวตามนี้ ผ่าน CategoryClassifier — ดู 2.1.4), is_active, last_fetched_at, last_status, last_error, timestamps, deleted_at
- `categories` — id, code, name, is_active
- `news` — id, source_id (FK), source_url (unique), title, summary, body, category, tags (json), thumbnail, lang, content_hash (dedup), status, sentiment, is_breaking, published_at, fetched_at
- `news_translations` — id, news_id (FK), locale (th/en/zh), title, summary, body, status, error_message, translated_at; unique(news_id, locale)
- `member_types` — id, code, name, description, is_active (บุคคล/องค์กร)
- `members` — id, member_type_id (FK), name, email, line_user_id, line_oa_user_id, line_oa_basic_id, line_oa_channel_id, line_oa_channel_secret, line_oa_webhook_url, preferred_locale, status, is_active, plan_start_date, plan_end_date, deleted_at
- `member_channels` — id, member_id (FK), channel_type (line_personal/line_oa/email), credentials (json), is_active; unique(member_id, channel_type)
- `member_interests` — id, member_id (FK), type, value, config (json), is_active; unique(member_id, type, value)
- `member_schedules` — id, member_id (FK), name, cron_expression, channels (json), categories (json), languages (json), limit, is_active
- `delivery_logs` — id, member_id, schedule_id, channel_type, news_ids (json), status, error_message, sent_at
- `packages` / `subscriptions` (Phase 2 — โครงสร้างพร้อม) — id, code, name, price, currency, features / id, member_id, package_id, plan_start/end_date, billing_cycle, status, payment_gateway, payment_ref
- `credentials` — id, code, name, config (json, encrypted), is_active, updated_by, last_tested_at (LINE, SMTP, Gemini LLM, n8n API, Neo4j)
- `audit_logs` — id, user_id, action, entity, entity_id, old_value (json), new_value (json)

### 5.2 ตาราง framework (Laravel) อยู่ใน MySQL เดียวกัน (migration `2026_08_01_000002_create_framework_tables.php` + `2026_08_07_000001_add_username_permissions_to_users.php`)
- `users` (พร้อม column `role`: admin/staff/user, `username` (nullable unique), `permissions` (json, ใช้เฉพาะ role ที่ไม่ใช่ admin)), `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`

### 5.3 PostgreSQL / pgvector / Neo4j
- **ไม่มีบทบาทเป็นฐานข้อมูลหลักของแอปใน Production**
- ใช้เฉพาะใน Local Dev (docker) สำหรับ: (1) n8n ใช้ Postgres เก็บ workflow DB, (2) Metabase ใช้ Postgres, (3) เตรียมโครงสร้าง pgvector/Neo4j สำหรับ Phase 2 แต่ยังไม่มี schema/เอกสารแยกให้ตรวจสอบเพิ่มเติม

---

## 6. ข้อกำหนดทางเทคนิคและความปลอดภัย (สถานะจริง)

- Credential เก็บแบบเข้ารหัส (encrypted at rest ด้วย `CREDENTIAL_ENCRYPTION_KEY`) และ input ที่เป็น secret มีปุ่ม show/hide (masked) ในหน้า Admin
- ใช้ HTTPS ทุกช่องทาง (SESSION_SECURE_COOKIE=true)
- RBAC: แยกสิทธิ์ **Admin / Staff / User** ผ่าน column `users.role` + `users.permissions` (json) + middleware `EnsureMenuAccess` (alias `menu`) สำหรับ gate ตามเมนู (`dashboard/news/chat/sources/members/categories/credentials/users`); ผู้ใช้ role `admin` ได้ทุกเมนู และกรณีเป็น admin แล้วหา role อื่นแทนไม่ได้ถ้าเป็นผู้ดูแลคนสุดท้าย (รวมถึงกันลบตัวเอง/ผู้ดูแลคนสุดท้าย)
- API ภายใน (n8n ↔ Laravel) ตรวจสอบผ่าน **X-API-Token** (middleware `ApiToken`) — ไม่ใช่ signature/secret per request
- Rate limiting: login (throttle 10/min) และ API routes
- Backup: `deploy/backup.sh` (ตั้ง crontab `0 2 * * *`) + `deploy/restore.sh` — dump MySQL `ittipolint_dailynews`
- Error handling: log แยก channel (`ingest`, `delivery`, `translation`, `daily` Laravel) + retry/backoff ใน TranslationService; n8n nodes ใช้ `onError: continueRegularOutput` กัน workflow ตายกลางคัน
- Privacy: นโยบายข้อมูลสมาชิก (อ้างอิงตาม Requirement)

---

## 7. สภาพแวดล้อมและการติดตั้ง (Deployment & Credentials) — สถานะจริง

### 7.1 Server Environment (Production)
- Web Server & Database Server: **shared Linux hosting** → `https://ittipolint-sbu.veya.co.th` (sub-path `/dailynews`), root จริงบน FTP = `public_html/dailynews/` (CWD `/home/ittipolint/domains/ittipolint-sbu.veya.co.th/public_html/dailynews`)
- FTP: IP `119.59.116.53`, User `ittipolint`
- ฐานข้อมูล: MySQL/MariaDB เดียว `ittipolint_dailynews`
- n8n Server (แยก): `https://n8n38-sbu.veya.co.th`
- Deploy วิธีปัจจุบัน: **FTP upload ไฟล์** ไปยัง `public_html/dailynews/` (หรือใช้ `deploy/deploy.sh` + `nginx-dailynews.conf` บน Ubuntu option)
- Cache/Session/Queue: **file** (ไม่มี Redis); Scheduler ผ่าน `php artisan schedule:run` (cron ทุกนาที)

### 7.2 Credential ที่สำคัญ (ตามเอกสาร Requirement / .env Production)

| รายการ | ค่า |
|---|---|
| **Web Server URL** | `https://ittipolint-sbu.veya.co.th/dailynews` |
| **FTP IP / User** | `119.59.116.53` / `ittipolint` |
| **DB (MySQL)** | DB `ittipolint_dailynews` / user `ittipolint_dailynews` |
| **n8n URL / User** | `https://n8n38-sbu.veya.co.th` / `ittipolint@gmail.com` |
| **API Token (n8n ↔ Laravel)** | `API_TOKEN` ใน .env |
| **LINE Channel ID / Name** | `1528339539` / `Ittipol@` |
| **LINE Webhook URL** | `https://n8n38-sbu.veya.co.th/webhook/line` |
| **Fetch Now Webhook URL** | `https://n8n38-sbu.veya.co.th/webhook/dailynews-fetch-now` |
| **Gemini (Translation + LLM)** | `GOOGLE_GEMINI_API_KEY` / `LLM_API_KEY`, model `gemini-2.5-flash` |
| **Embedding (เตรียมไว้)** | `EMBEDDING_API_KEY`, model `gemini-embedding-001` |
| **Email Sender** | `DailyNews` (`dailynews@ittipolint-sbu.veya.co.th`) |
| **Admin** | `ADMIN_EMAIL=ittipolint@gmail.com` |

> **หมายเหตุ:** Secret ทั้งหมดเก็บใน `.env` Production (และสำรองใน `dn-prod-env.backup`); ห้าม hardcode/commit ลง Git. เอกสารนี้แสดงเฉพาะชื่อ field ไม่แสดงค่า secret

### 7.3 ข้อกำหนดเรื่อง Credential ใหม่
- กรณีที่ระบบสร้าง/ออก Credential ใหม่ (เช่น Translation API Key, SMTP, Webhook Secret, LLM Key) ให้รายงานออกมาให้ทราบด้วยทุกครั้ง

---

## 8. ระบบ Background Workflow: n8n Workflows (บทบาทจริง)

### สรุปบทบาทจริง (ตรวจจาก n8n instance ณ ส.ค. 2026)
- **เฉพาะ 2 workflows ที่ active:** "DailyNews - Fetch Source Now" (webhook) และ "DailyNews - Trigger Deliver (Laravel)"
- **Ingest / Translate / Deliver workflows มีใน repo (`n8n/workflows/`) แต่ถูกตั้ง inactive** เพราะงานเหล่านี้ทำงานผ่าน Laravel scheduled jobs เป็นหลัก

### 8.1 dailynews-ingest-rss.json — "DailyNews - Ingest RSS Sources" (inactive)
- Trigger: ทุก 1 ชั่วโมง → GET `/api/v1/sources` → กรองเฉพาะ `fetch_type === 'rss'`
- วนทีละแหล่ง (`splitInBatches`) → node RSS Feed Read parse feed (`feed_url || url`)
- Build payload `{source: slug, items[]}` → IF items ไม่ว่าง → `POST /api/ingest/push`
- หมายเหตุ: ปัจจุบันงานนี้ถูก Laravel `dailynews:ingest` ทับ (ทำ RSS/API/Crawl ครบในตัว)

### 8.2 dailynews-ingest-api.json — "DailyNews - Ingest API Sources" (inactive)
- Trigger: ทุก 1 ชั่วโมง → GET `/api/v1/sources` → กรอง `fetch_type === 'api'`
- วนทีละแหล่ง → HTTP GET `feed_url` (JSON) → normalize (`articles|data|results`) → `POST /api/ingest/push`

### 8.3 dailynews-fetch-source-now.json — "DailyNews - Fetch Source Now" (ACTIVE — webhook)
- Trigger: **n8n Webhook** `POST /webhook/dailynews-fetch-now` (headerAuth, DailyNews API Token)
- รับ payload จากปุ่ม "Fetch Now" ในหน้า Admin → Extract `source`
- IF `fetch_type === 'rss'` → Parse RSS → Build RSS Payload → push
- ELSE → Call API (`feed_url`, JSON) → Normalize → push
- Push ไป `POST /api/ingest/push` เสมอ
- ใช้ร่วมกับ `routes: POST /admin/sources/{source}/fetch-now` (Laravel ส่ง request ไป n8n) + `GET /api/v1/sources`

### 8.4 dailynews-deliver.json — "DailyNews - Deliver News to Members" (inactive)
- Trigger: ทุก 1 นาที → GET `/api/v1/schedules/due` → split → GET `/api/v1/news?from=today` → Build Message (ข่าว 5 ชิ้นบนสุด) → Fanout per channel
- `Is LINE channel?` → `Send via LINE` (LINE node, ใช้ line_user_id/line_oa_user_id) มิฉะนั้น `Send via Email`
- สุดท้าย `POST /api/v1/deliveries` บันทึก DeliveryLog
- หมายเหตุ: ปัจจุบันการส่งทำผ่าน Laravel `dailynews:deliver` (ทุกนาที) เป็นหลัก ซึ่งมี translation-on-delivery + interests/categories filter ครบกว่า; workflow นี้เก็บไว้เป็นทางเลือก

### 8.5 dailynews-translate.json — "DailyNews - Translate News (th/en/zh)" (inactive)
- Trigger: ทุก 1 ชั่วโมง → `Empty Trigger` → GET `/api/v1/news?q=translate&limit=20` (ตรวจ pending)
- Node สุดท้ายเป็น "Note / Health Check" — แปลว่า **การแปลจริงทำโดย Laravel** (`artisan dailynews:translate`); workflow นี้มีไว้ตรวจสุขภาพ/trigger เท่านั้น

### 8.6 "DailyNews - Trigger Deliver (Laravel)" (ACTIVE)
- Workflow ที่เปิดอยู่บน n8n instance ทำหน้าที่ trigger ให้ Laravel รันการส่ง (เรียก API ของแอป) — เป็น webhook/trigger helper

### 8.7 Environment/credential ใน n8n
- httpHeaderAuth credential "DailyNews API Token" ใช้กับทุก node ที่เรียก API DailyNews
- LINE credential (Line Messaging account) ใช้ใน deliver workflow

---

## 9. Docker: บทบาทจริง

### ใช้หรือไม่? — **เฉพาะ Local Development เท่านั้น; Production ไม่ใช้ Docker**
- หลักฐาน: Production เป็น shared hosting/cPanel (FTP path `public_html/dailynews/`, PHP-FPM, MySQL เดียว, file cache/queue) ไม่มี container runtime; `docker/docker-compose.yml` มีไว้สำหรับรัน stack ทั้งหมดในเครื่อง dev

### docker/docker-compose.yml มี services:
| Service | Image | บทบาทใน Local Dev |
|---|---|---|
| `web` | build จาก `docker/php/Dockerfile` | Laravel app (PHP) mount `../webapp` |
| `nginx` | nginx:1.27-alpine | serve app port `${WEB_PORT:-8080}` |
| `mysql` | mysql:8.4 | DB ของแอป (dailynews) |
| `postgres` | pgvector/pgvector:pg16 | สำหรับ n8n/Metabase + เตรียม vector |
| `redis` | redis:7-alpine | cache/queue (ไม่ใช้ใน prod) |
| `n8n` | n8nio/n8n:latest | workflow engine (local), DB = postgres |
| `metabase` | metabase/metabase:v0.50.16 | BI/Dashboard (local) |

- `docker/php/Dockerfile` + `php.ini`, `docker/nginx/default.conf` ใช้ configure สภาพ dev
- เริ่มใช้งาน: `cd docker && cp .env.example .env && docker compose up -d --build`

### สรุป
- Docker = environment สำหรับพัฒนา/ทดสอบทั้งหมดในเครื่องเดียว (สะดวกพกพา)
- Production deployment = FTP/rsync ขึ้น shared hosting (ดู section 7)

---

## 10. Roadmap การพัฒนา (Development Phases)

### Phase 1 (สถานะปัจจุบัน — เสร็จตามที่พัฒนาจริง)
- Web App Laravel (Admin/Member/Chat) + MySQL เดียว
- Import แหล่งข่าวเริ่มต้น (14 sources) + ingestion (RSS/API/Crawl) ผ่าน Laravel + n8n fetch-now
- ระบบแปลภาษา th/en/zh ใช้ Google Gemini (`dailynews:translate`)
- ระบบจัดการสมาชิก + channel LINE/Email + schedule ส่งข่าว + ปุ่มส่งข่าวทันที (แก้ไข schedule ได้)
- Dashboard พื้นฐาน (สถิติ + Export CSV) ใน Blade
- ระบบค้นหา keyword (Admin) + AI Chat (RAG แบบเบา: keyword + Gemini LLM)
- n8n ใช้เฉพาะ webhook (Fetch Now, Trigger Deliver)

### Phase 2 (ถัดไป)
- ต่อจริง: pgvector/embedding + Neo4j Graph DB เข้า GraphRagService (ปัจจุบันเป็น keyword-based)
- การเก็บค่าสมาชิก / ต่ออายุ (subscription, packages, payment — โครงสร้างตารางมีแล้ว)
- ช่องทางส่งข่าวเพิ่มเติม (Telegram, Web Push)
- ปรับปรุง Graph RAG ให้มีข้อมูลเชิงลึกและ Personalized News

---

## 11. รายการ Idea เพิ่มเติม (Enhancement Ideas)

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

## 12. ผู้มีส่วนได้ส่วนเสียและการอนุมัติ

- **เจ้าของระบบ (Owner):** อ้างอิงตามข้อกำหนด — GitHub: https://github.com/Ittipolint/dailynews
- **เอกสารต้นทาง:** `docs/Prompt.md` (Requirement Specification)
- **เอกสารฉบับนี้:** `docs/Software-Specification.md`

---

**— End of Software Specification —**
