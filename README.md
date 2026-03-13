# papaiwat2

ระบบบริหารจัดการเนื้อหาเว็บไซต์ (CMS) สำหรับหน่วยงานภาครัฐ พัฒนาด้วย Zend Framework 1.x รองรับหลายภาษา

## ความต้องการของระบบ

- PHP 7.4
- MySQL
- Apache (mod_rewrite)
- Composer

## การติดตั้งและรันด้วย Docker

### Development

```bash
cp .env.example .env
# แก้ไขค่า DB_HOST, DB_PASSWORD ตามสภาพแวดล้อม

docker compose -f docker-compose-dev.yml up -d
```

- เข้าใช้งานที่ `http://localhost:8001`
- หน้า Admin: `http://localhost:8001/admin`
- โค้ดจะ mount แบบ live (แก้ไขไฟล์แล้วเห็นผลทันที)

### Production

```bash
docker compose up -d --build
```

- ไฟล์อัปโหลดจะเก็บใน volume: `public/uploads` และ `files`

### ตัวแปร Environment

| ตัวแปร | ค่าเริ่มต้น | คำอธิบาย |
|---|---|---|
| `APPLICATION_ENV` | `production` | โหมดแอปพลิเคชัน (`development` / `production`) |
| `APP_PORT` | `8001` | พอร์ตที่เปิดให้เข้าถึง |
| `DB_HOST` | `host.docker.internal` | โฮสต์ฐานข้อมูล MySQL |
| `DB_PORT` | `3306` | พอร์ตฐานข้อมูล |
| `DB_DATABASE` | `d2ms_papaiwat_db` | ชื่อฐานข้อมูล |
| `DB_USERNAME` | `papaiwat2` | ชื่อผู้ใช้ฐานข้อมูล |
| `DB_PASSWORD` | - | รหัสผ่านฐานข้อมูล |

## การติดตั้งแบบไม่ใช้ Docker

1. ชี้ Document Root ของ Apache ไปที่ `public/`
2. เปิดใช้งาน `mod_rewrite`
3. ติดตั้ง Composer dependencies:

```bash
cd library
composer install
```

4. แก้ไขค่าฐานข้อมูลใน:
   - `applicationfront/configs/application.ini`
   - `applicationadmin/configs/application.ini`

## โครงสร้างโปรเจค

```
├── applicationfront/       # แอปพลิเคชันหน้าเว็บ (Frontend)
│   ├── configs/            #   ไฟล์ตั้งค่า (application.ini)
│   ├── controllers/        #   คอนโทรลเลอร์หลัก
│   ├── modules/            #   โมดูลต่าง ๆ (content, gallery, event, ...)
│   ├── views/              #   เทมเพลต
│   └── layouts/            #   เลย์เอาต์
│
├── applicationadmin/       # แอปพลิเคชันหลังบ้าน (Admin)
│   ├── configs/            #   ไฟล์ตั้งค่า
│   ├── controllers/        #   คอนโทรลเลอร์หลัก
│   ├── modules/            #   โมดูลจัดการ (user, system, setting, report, ...)
│   ├── views/              #   เทมเพลต
│   └── layouts/            #   เลย์เอาต์
│
├── library/                # ไลบรารีที่ใช้ร่วมกัน
│   ├── Emwork/             #   ยูทิลิตี้ของบริษัท (Grid, ACL, Translate, ...)
│   ├── App/                #   คลาสเฉพาะแอป (DB adapter, Rights, SMS, ...)
│   ├── Zend/               #   Zend Framework 1.x
│   └── vendor/             #   Composer packages
│
├── public/                 # Web root (Document Root)
│   ├── index.php           #   จุดเข้าหน้าเว็บ
│   ├── admin/index.php     #   จุดเข้าหลังบ้าน
│   ├── plugins/            #   jQuery, CKEditor, Bootstrap, ...
│   └── uploads/            #   ไฟล์อัปโหลด
│
├── languages/              # ไฟล์ภาษา (INI format)
├── files/                  # ไฟล์เอกสาร
├── docker/                 # Docker config (php.ini, entrypoint)
├── Dockerfile              # Production image
├── Dockerfile.dev          # Development image
├── docker-compose.yml      # Production compose
└── docker-compose-dev.yml  # Development compose
```

## โมดูลหลัก

| โมดูล | คำอธิบาย |
|---|---|
| `content` | จัดการบทความ/เนื้อหา |
| `banner` | จัดการแบนเนอร์ |
| `gallery` | อัลบั้มรูปภาพ |
| `event` | ปฏิทินกิจกรรม |
| `calendar` | ปฏิทิน |
| `faq` | คำถามที่พบบ่อย |
| `newsletter` | จดหมายข่าว |
| `eform` | แบบฟอร์มอิเล็กทรอนิกส์ |
| `orgchart` | ผังองค์กร |
| `complain` | ระบบร้องเรียน |
| `guestbook` | สมุดเยี่ยม |
| `information` | ข้อมูลข่าวสาร |
| `license` | จัดการใบอนุญาต |

## เทคโนโลยีที่ใช้

- **Backend:** PHP 7.4, Zend Framework 1.x
- **Database:** MySQL (Mysqli adapter)
- **Frontend:** jQuery, Bootstrap, CKEditor
- **PDF:** spipu/html2pdf, TCPDF
- **Excel:** PHPExcel
- **APIs:** Facebook Graph SDK, Google API Client
- **อื่น ๆ:** Thai Bulk SMS, PromptPay QR Code
