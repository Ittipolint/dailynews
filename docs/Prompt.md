============================== Start of Requirement Specification ==================================  
คุณคือผู้เชี่ยวชาญการออกแบบระบบ IT 
สิ่งที่ให้ท่านทำคือสร้าง Software specification เพื่อใช้สร้างระบบชื่อ "DailyNews" และนำเอกสารดังกล่าวไปเก็บยัง https://github.com/Ittipolint/dailynews
1. รายละเอียด feature/function ของระบบ DailyNews
   1.1 Feature การรับข่าวเข้าสู่ระบบ (ฺBackground Job)
       1.1.1 รวบรวมข่าวจากแหล่งข่าวที่สำคัญรอบโลก เบื้องต้นขอให้ดึงแหล่งข่าวที่สำคัญรอบโลกเข้ามาใส่ในระบบให้ก่อน
       1.1.2 มีหน้าจอที่สามารถกำหนดแหล่งข่าวเพิ่มเติม แก้ไข ลบทิ้ง Set active/in-active ได้ 
       1.1.3 เก็บ data ข่าว ลงในฐานข้อมูล
       1.1.4 แปลข่าวจากภาษาต้นทางมาเก็บเป็น 3 ภาษาหลัก ได้แก่ ภาษาไทย ภาษาอังกฤษ ภาษาจีน
       1.1.5 มีหน้าจอ chat สำหรับให้ค้นหาข่าวย้อนหลังเป็นระบบ AI Graph RAG
       1.1.6 ถ้ามี idea อื่นๆที่ดีก็เพิ่มเข้ามาได้
       1.1.7 (เพิ่มเติม Requirement) มีปุ่ม "ดึงข่าวทันที" (Fetch Now) ที่หน้าจอแหล่งข่าว เพื่อให้ Admin ดึงข่าวจากแหล่งข่าวใดก็ได้ทันทีโดยไม่ต้องรอรอบ scheduled job โดยให้ไปเรียก n8n webhook และบันทึกข่าวเข้าฐานข้อมูลด้วยกลไก ingestion เดิม
   1.2 Feature การส่งข่าว (Background Job)
       1.2.1 ส่งข่าวไปยังสมาชิกตามช่องทาง และเวลาที่กำหนด
       1.2.2 สมาชิกรับข่าว แต่ละสมาชิกสามารถรับข่าวได้มากกว่า 1 ช่องทาง โดยกำหนด
       1.2.3 ช่องทางที่กำหนดให้มีเริ่มต้นได้แก่ LINE ส่วนตัว, LINE OA, Email
       1.2.4 ถ้ามี idea อื่นๆที่ดีก็เพิ่มเข้ามาได้
   1.3 Feature การค้นหาข่าว (เปิดให้ใช้สำหรับ Admin เท่านั้น)
       1.3.1 Search keyword ได้
    1.4 Feature การบริหารจัดการสมาชิก
        1.4.1 จัดการประเภทสมาชิก เช่น สมาชิกองค์กร สมาชิกบุคคล เป็นต้น 
        1.4.2 สมาชิกสามารถเข้ามา setup ประข่าวที่สนใจจะรับได้
        1.4.3 สมาชิกสามารถ setup schedule ที่จะให้ส่งข่าวในแต่ละประเภทได้ตามวัน เวลาที่กำหนด
        1.4.4 การเก็บค่าสมาชิก ต่ออายุสมาชิก ยังไม่มีใน phase นี้ **** แต่ให้วางโครงสร้างเพื่อรองรับใน phase ถัดไป
        1.4.5 (เพิ่มเติม Requirement) มีปุ่ม "ส่งข่าว" (Send News Now) ที่หน้าจอจัดการสมาชิก เพื่อให้ Admin กดส่งข่าว Lot ล่าสุดให้กับสมาชิกคนนั้นทันที โดยส่งไปทุกช่องทางที่ Active ของสมาชิก (LINE ส่วนตัว / LINE OA / Email) บันทึก DeliveryLog (schedule_id = null) และ AuditLog ทุกครั้ง
   1.5 Feature Dashboard สำหรับ Admin
       1.5.1 ขอให้เสนอ Idea ของท่านลงใน Software Specification
       1.5.2 แต่อย่างน้อยต้องมีจำนวนข่าวที่ดึงมาจากแต่ละ source ในแต่ละวัน แต่ลสัปดาห์ แต่ละเดือน
       1.5.3 จำนวนข่าวที่ส่งออกไปในแต่ละวัน แต่ละสัปดาห์ แต่ละเดือน
   1.6 Feature Reference Data เพื่อ setup ค่าการทำงานต่างๆของระบบ
       1.6.1 การเพิ่ม แก้ไข ลบ active/not active แหล่งข่าว
       1.6.2 ให้มีรูปแบบการดึงข่าวจากแหล่งต้นทางด้วย เช่น Web Crowling, API และบริหารจัดการ Credential แหล่งข่าวได้
       1.6.3 การเพิ่ม แก้ไข ลบ active/not active สมาชิกที่จะรับข่าว และ Credential ต่างๆที่จำเป็นในการส่งข่าวออกไปให้สมาชิกแต่ละคน
       1.6.4 ส่วนของ Admin การแก้ไข Credential ของระบบที่สำคัญต่างๆ
       1.6.5 ถ้ามี idea อื่นๆที่ดีก็เพิ่มเข้ามาได้
2. เทคโนโลยีที่ใช้
   2.1 OS ของ Server คือ Ubuntu และ Software ทั้งหมดจะเป็น Opensource
   2.2 n8n ทำหน้าที่เป็นกลไก workflow ที่ดึงข่าวจากแหล่งข่าวเป้าหมายมาบันทึกลงฐานข้อมูลข่าว
   2.3 n8n ทำหน้าที่ส่งข่าวล่าสุดให้กับผู้เป็นสมาชิกของระบบตามช่องทางที่สมาชิกแต่ละท่านกำหนด
   2.4 ฐานข้อมูลเก็บข่าว และข้อมูลอื่นๆทั้งหมดให้ใช้ Postgres
   2.5 Dashboard ให้ท่านเลือกใช้ Opensource ที่นิยมใช้กันอย่างแพร่หลาย
   2.6 ภาษาหลักที่ใช้ในการสร้าง Web ใช้ PHP และ MySQL ที่ติดตั้งไว้แล้ว
   2.7 เลือก Framework ที่นิยมใช้กันแพร่หลายและเป็นมาตราฐาน
   2.8 Technology tools ต่างๆที่ใช้นอกเหนือจากนี้ให้กำหนดลงมาใน Software Specification ให้ครบ
3. Credential ต่างๆที่สำคัญในการสร้างและติดตั้งระบบ
    3.1 Web Server และ Database server
        3.1.1 https://ittipolint-sbu.veya.co.th  โดยให้สร้าง sub ใหม่ชื่อ /dailynews
        3.1.2 FTP IP: 119.59.116.53
                 User: ittipolint
                 Pass: <REDACTED - stored in deploy secrets>
        3.1.3 Database name: ittipolint_dailynews
                 User: ittipolint_dailynews
                 Pass: <REDACTED - stored in deploy secrets>
    3.2 n8n Server
        3.2.1 https://n8n38-sbu.veya.co.th
        3.2.2 user: ittipolint@gmail.com
        3.2.3 pass: <REDACTED - stored in deploy secrets>
        3.2.4 n8n API key: <REDACTED - stored in deploy secrets>
        3.2.5 Fetch Now Webhook URL (feature Fetch Now): https://n8n38-sbu.veya.co.th/webhook/dailynews-fetch-now
    3.3 LINE Messaging API สำหรับการทดสอบ
        3.3.1 Your user ID: Ue25e822f472f9646c5fe76482825567f
        3.3.2 Channel id.: <REDACTED - stored in deploy secrets>
        3.3.3 Channel name: Ittipol@
        3.3.4 Channel secret: <REDACTED - stored in deploy secrets>
        3.3.5 Channel access token: <REDACTED - stored in deploy secrets>
        3.3.6 Webhook URL: https://n8n38-sbu.veya.co.th/webhook/line
    3.4 LINEOA
        3.4.1 ชื่อบัญชี: DailyNews
        3.4.2 เบสิค ID: @131ofesf
        3.4.3 ชื่อโพรไวเดอร์: POL
        3.4.4 แชนแนล ID: 2010949885
        3.4.5 ความลับแชนแนล: 8d81847c39110fe9ae59ea6c12c23b9f
        3.4.6 Webhook URL: https://n8n38-sbu.veya.co.th/webhook/line
    3.5 Email สำหรับส่งทดสอบ:
        3.5.1 Email sender: DailyNews
============================== End of Requirement Specification ==================================   
      - Email receive address: ittipolint@gmail.com 
   - กรณีที่สร้างสิ่งใดใหม่ให้รายงาน Credential ออกมาให้ทราบด้วย 
