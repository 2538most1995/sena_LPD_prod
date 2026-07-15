# Sena LPD

ระบบการเรียนรู้เพื่อการพัฒนาตนเอง สกร.ระดับอำเภอเสนา พัฒนาด้วย Laravel, React, TanStack Query และ MySQL โดยเป็นระบบ Laravel เดียว ไม่มีการพึ่งพา PHP ระบบเก่า

## ความสามารถหลัก

- ผู้ดูแล 3 ระดับ: Super Admin, Admin ระดับอำเภอ และ Admin ระดับตำบล
- จัดการหลักสูตรและแนบไฟล์ Word/PDF พร้อมกัน
- จัดตั้งกลุ่ม ผู้เรียน วิทยากร คะแนน และภาพกิจกรรม
- ขั้นตอนส่งอนุมัติ/อนุมัติ/ส่งกลับ พร้อมการแจ้งเตือนและประวัติ
- Dashboard และสถิติหลักสูตร/การจัดตั้งกลุ่ม
- สร้างเอกสาร พต. และรายงาน PDF โดย Laravel โดยตรง
- Audit log, rate limit, validation และสิทธิ์การเข้าถึงตามหน่วยงาน

## ติดตั้ง

ต้องใช้ PHP 8.3+, MySQL 8+, Composer และ Node.js

```bash
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

กำหนดค่าฐานข้อมูลจริงใน `.env` และให้เว็บเซิร์ฟเวอร์ชี้ Document Root มาที่โฟลเดอร์ `public` เพื่อความปลอดภัยสูงสุด

โฟลเดอร์ต่อไปนี้ต้องเขียนได้โดย PHP:

```text
storage/
bootstrap/cache/
```

## ใช้กับ MAMP ใน workspace นี้

ระบบรองรับ URL `http://localhost:8888/index.php` ผ่าน front controller ที่รากโครงการ และยังคงใช้โครงสร้าง `public` มาตรฐานสำหรับ production

```bash
/Applications/MAMP/bin/php/php8.3.14/bin/php artisan migrate
/Applications/MAMP/bin/php/php8.3.14/bin/php artisan test
npm run build
```

## ตรวจสอบก่อนนำขึ้นระบบจริง

```bash
php artisan about
php artisan migrate:status
php artisan test
./vendor/bin/pint --test
npm run build
php artisan optimize
```

ตั้งค่า production อย่างน้อยดังนี้:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example/sena_LPD
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_strong_password
SESSION_SECURE_COOKIE=true
```

ห้ามนำ `.env`, ฐานข้อมูลสำรอง หรือไฟล์ใน `storage/app/private` ไปเปิดเป็น public URL โดยตรง
