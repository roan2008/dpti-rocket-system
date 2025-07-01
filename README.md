# 🚀 DPTI Rocket Production Management System

**ระบบจัดการการผลิตจรวดสำหรับองค์กร DPTI**

---

## 📋 Quick Start

1. **ติดตั้ง XAMPP** และเริ่มต้น Apache + MySQL
2. **Clone โปรเจค** ไปที่ `c:\xampp\htdocs\dpti-rocket-system\`
3. **สร้างฐานข้อมูล:** `CREATE DATABASE dpti_rocket_prod;`
4. **Import Schema:** `mysql -u root dpti_rocket_prod < docs/database_schema.sql`
5. **เข้าใช้งาน:** `http://localhost/dpti-rocket-system/`

### 🔐 Test Accounts
| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Admin (สิทธิ์เต็ม) |
| `engineer` | `engineer123` | Engineer (จัดการ + อนุมัติ) |
| `staff` | `staff123` | Staff (ดู + เพิ่มข้อมูล) |

---

## 🎯 สถานะปัจจุบัน

### ✅ **เสร็จสิ้นแล้ว**
- **User Authentication** - ระบบล็อกอิน/ล็อกเอาต์ พร้อม role-based access
- **Rocket Management** - เพิ่ม/แก้ไข/ลบ/ดูรายละเอียดจรวด
- **Production Steps Tracking** - ติดตามขั้นตอนการผลิต 12 ประเภท ✅ COMPLETE

### 🔄 **Next Phase**
- **Approval Workflow System** - ระบบอนุมัติขั้นตอนการผลิต

---

## 📚 **เอกสารประกอบ**

| เอกสาร | วัตถุประสงค์ | สำหรับใคร |
|--------|-------------|----------|
| **[📖 docs/USER_GUIDE.md](docs/USER_GUIDE.md)** | คู่มือการใช้งาน | ผู้ใช้ทุก Role |
| **[🛠️ docs/TECHNICAL_GUIDE.md](docs/TECHNICAL_GUIDE.md)** | คู่มือเทคนิค | Developer |
| **[📋 docs/MILESTONE_PRODUCTION_STEPS.md](docs/MILESTONE_PRODUCTION_STEPS.md)** | รายงานความก้าวหน้า | Project Manager |
| **[🗄️ docs/database_schema.sql](docs/database_schema.sql)** | โครงสร้างฐานข้อมูล | Developer/DBA |

---

## 🔧 **System Features**

### **Rocket Management**
- เพิ่มจรวดใหม่ (Serial Number + Project Name)
- แก้ไขข้อมูลจรวด
- อัพเดทสถานะจรวด (7 สถานะ)
- ลบจรวด (Admin เท่านั้น)

### **Production Steps Tracking** ✅
12 ขั้นตอนการผลิต:
```
Design Review → Material Preparation → Tube Preparation
→ Propellant Mixing → Propellant Casting → Motor Assembly
→ Component Assembly → Quality Check → System Test
→ Integration Test → Final Inspection → Launch Preparation
```

### **Security Features**
- PDO Prepared Statements (ป้องกัน SQL Injection)
- Password Hashing (bcrypt)
- Session Management
- Role-based Access Control
- XSS Prevention

---

## 📁 **โครงสร้างโปรเจค**

```
dpti-rocket-system/
├── 📁 assets/           # CSS, JS, รูปภาพ
├── 📁 controllers/      # การจัดการ HTTP requests
├── 📁 docs/            # เอกสารและ database schema
├── 📁 includes/        # Functions และ database connections
├── 📁 tests/           # Test scripts
├── 📁 views/           # หน้าแสดงผล (HTML + PHP)
├── dashboard.php       # หน้าแรกหลังล็อกอิน
├── index.php          # หน้าล็อกอิน
└── README.md          # ไฟล์นี้
```

---

## 🧪 **Testing**

เรียกใช้ test scripts:
```bash
cd tests/
php test_production_steps.php     # Test backend functions
php test_production_frontend.php  # Test frontend integration
```

**สถานะการทดสอบ:** ✅ All tests passing (18/18 test cases)

---

## 🛠️ **Technology Stack**

- **Backend:** PHP 8.0+
- **Database:** MySQL 8.0
- **Frontend:** Vanilla HTML/CSS/JavaScript
- **Architecture:** Separation of Concerns (MVC Pattern)
- **Security:** PDO, bcrypt, Session Management

---

## 📈 **Development Progress**

| Phase | Status | Features |
|-------|--------|----------|
| **Phase 1** | ✅ Complete | Foundation, User Auth, Database |
| **Phase 2** | ✅ Complete | Rocket Management, Production Steps |
| **Phase 3** | 🔄 Next | Approval Workflow, User Management |
| **Phase 4** | 📋 Planned | Advanced Reporting, Notifications |
| **Phase 5** | 📋 Planned | Polish, Performance, Deployment |

---

## 📞 **Support**

- **Documentation:** อ่านใน `docs/` folder
- **Technical Issues:** ตรวจสอบ test scripts ก่อน
- **User Guide:** เริ่มต้นที่ `docs/USER_GUIDE.md`

---

**Version:** 2.0  
**Last Updated:** July 1, 2025  
**Development Team:** GitHub Copilot
