# 📖 User Guide - DPTI Rocket System

**คู่มือการใช้งานสำหรับผู้ใช้ทุก Role**

---

## 🚀 **เริ่มต้นใช้งาน**

### **การเข้าสู่ระบบ**
1. เปิดเว็บไซต์: `http://localhost/dpti-rocket-system/`
2. กรอก Username และ Password
3. คลิก "Login"

### **Test Accounts สำหรับทดสอบ**
| Username | Password | สิทธิ์ |
|----------|----------|-------|
| `admin` | `admin123` | ผู้ดูแลระบบ (สิทธิ์เต็ม) |
| `engineer` | `engineer123` | วิศวกร (จัดการ + อนุมัติ) |
| `staff` | `staff123` | พนักงาน (ดู + เพิ่มข้อมูล) |

---

## 🎯 **Dashboard - หน้าแรก**

หลังล็อกอินสำเร็จ จะเห็น:

### **ส่วนข้อมูลรวม**
- จำนวนจรวดทั้งหมด
- จำนวน Production Steps
- สถิติต่างๆ

### **ตารางจรวด**
- รายการจรวดทั้งหมด
- สถานะปัจจุบัน
- ปุ่มดำเนินการ (View, Edit, Delete)

### **เมนูการทำงาน**
- **Add New Rocket** - เพิ่มจรวดใหม่
- **Logout** - ออกจากระบบ

---

## 🛠️ **การจัดการจรวด**

### **1. เพิ่มจรวดใหม่**
**สิทธิ์:** Admin, Engineer เท่านั้น

1. คลิก **"Add New Rocket"** ที่ Dashboard
2. กรอกข้อมูล:
   - **Serial Number:** รหัสจรวด (ตัวอย่าง: `RKT-007`, `MARS-ALPHA-01`)
   - **Project Name:** ชื่อโปรเจค (ตัวอย่าง: `Mars Mission Alpha`)
   - **Initial Status:** สถานะเริ่มต้น (ไม่บังคับ - default คือ "New")
3. คลิก **"Add Rocket"**

**ข้อกำหนด:**
- Serial Number ต้องไม่ซ้ำกับที่มีอยู่
- ใช้ได้เฉพาะตัวอักษร, ตัวเลข, และเครื่องหมาย `-`
- Project Name ต้องไม่เกิน 255 ตัวอักษร

### **2. ดูรายละเอียดจรวด**
**สิทธิ์:** ทุก Role

1. คลิก **"View"** ที่จรวดที่ต้องการ
2. ดูข้อมูลครบถ้วน:
   - ข้อมูลพื้นฐานจรวด
   - สถานะปัจจุบัน
   - ประวัติ Production Steps

### **3. แก้ไขข้อมูลจรวด**
**สิทธิ์:** Admin, Engineer เท่านั้น

1. ที่หน้ารายละเอียดจรวด คลิก **"Edit Rocket"**
2. แก้ไขข้อมูลที่ต้องการ
3. คลิก **"Update Rocket"**

### **4. อัพเดทสถานะจรวด**
**สิทธิ์:** ทุก Role

1. ที่หน้ารายละเอียดจรวด
2. เลือกสถานะใหม่จาก dropdown "Quick Status Update"
3. คลิก **"Update"**

**สถานะที่ใช้ได้:**
- New → Planning → Design → In Production → Testing → Completed
- On Hold (สามารถเซ็ตได้ทุกเวลา)

### **5. ลบจรวด**
**สิทธิ์:** Admin เท่านั้น

1. ที่หน้ารายละเอียดจรวด คลิก **"Delete Rocket"**
2. ยืนยันการลบในหน้าต่าง popup
3. คลิก **"Yes, Delete"** เพื่อยืนยัน

⚠️ **คำเตือน:** การลบจะไม่สามารถกู้คืนได้

---

## 📝 **Production Steps Tracking**

### **1. ดูประวัติ Production Steps**
**สิทธิ์:** ทุก Role

1. เข้าไปที่หน้ารายละเอียดจรวด
2. เลื่อนลงไปดูส่วน **"Production History"**
3. ดูรายการขั้นตอนที่ผ่านมาแล้ว:
   - ชื่อขั้นตอน
   - วันเวลาที่ทำ
   - ผู้ที่บันทึก
   - ข้อมูลเพิ่มเติม (ถ้ามี)

### **2. เพิ่ม Production Step**
**สิทธิ์:** ทุก Role

1. ที่หน้ารายละเอียดจรวด คลิก **"Add New Production Step"**
2. เลือก **Step Type** จาก dropdown:

**12 ขั้นตอนการผลิต:**
- **Design Review** - ตรวจสอบการออกแบบ
- **Material Preparation** - เตรียมวัสดุ
- **Tube Preparation** - เตรียมท่อจรวด
- **Propellant Mixing** - ผสมเชื้อเพลิง
- **Propellant Casting** - หล่อเชื้อเพลิง
- **Motor Assembly** - ประกอบมอเตอร์
- **Component Assembly** - ประกอบชิ้นส่วน
- **Quality Check** - ตรวจสอบคุณภาพ
- **System Test** - ทดสอบระบบ
- **Integration Test** - ทดสอบการทำงานร่วม
- **Final Inspection** - ตรวจสอบขั้นสุดท้าย
- **Launch Preparation** - เตรียมปล่อย

3. กรอก **Step Data (JSON)** ถ้าต้องการ:
```json
{
  "details": "รายละเอียดเพิ่มเติม",
  "duration": "30 minutes",
  "notes": "หมายเหตุ"
}
```

4. คลิก **"Add Production Step"**

### **3. ข้อมูล JSON ตัวอย่าง**

**Design Review:**
```json
{
  "reviewer": "วิศวกร สมชาย",
  "approved": true,
  "notes": "ผ่านการตรวจสอบเรียบร้อย"
}
```

**Quality Check:**
```json
{
  "inspector": "นาย ตรวจสอบ",
  "result": "PASS",
  "defects": [],
  "notes": "คุณภาพผ่านมาตรฐาน"
}
```

**System Test:**
```json
{
  "test_type": "Pressure Test",
  "pressure": "150 PSI",
  "result": "PASS",
  "duration": "45 minutes"
}
```

---

## ⚠️ **ข้อผิดพลาดที่พบบ่อย**

### **Login Issues**
- **ปัญหา:** "Invalid username or password"
- **วิธีแก้:** ตรวจสอบ username/password ให้ถูกต้อง

### **Permission Denied**
- **ปัญหา:** ไม่สามารถแก้ไข/ลบได้
- **วิธีแก้:** ตรวจสอบ Role ของคุณ - บางฟีเจอร์ใช้ได้เฉพาะ Admin/Engineer

### **Duplicate Serial Number**
- **ปัญหา:** "Serial number already exists"
- **วิธีแก้:** เปลี่ยน Serial Number ให้ไม่ซ้ำกับที่มีอยู่

### **JSON Format Error**
- **ปัญหา:** "Invalid JSON format"
- **วิธีแก้:** ตรวจสอบ JSON syntax ให้ถูกต้อง (ใช้ double quotes, ไม่มี comma ท้าย)

---

## 💡 **Tips การใช้งาน**

### **การตั้งชื่อ Serial Number**
- ใช้รูปแบบที่สม่ำเสมอ เช่น: `RKT-001`, `MARS-ALPHA-01`
- หลีกเลี่ยงเครื่องหมายพิเศษนอกจาก hyphen (-)
- ใช้ตัวเลขเรียงลำดับเพื่อความเป็นระเบียบ

### **การเขียน JSON Data**
- ใช้ field names ที่เข้าใจง่าย
- ระบุหน่วยในข้อมูลตัวเลข (เช่น "30 minutes", "150 PSI")
- เก็บข้อมูลสำคัญที่อาจต้องใช้ในอนาคต

### **การติดตาม Progress**
- เพิ่ม Production Steps ตามลำดับเวลาจริง
- ใส่รายละเอียดในแต่ละขั้นตอนให้ครบถ้วน
- อัพเดทสถานะจรวดเมื่อขั้นตอนสำคัญเสร็จ

---

## 📞 **การขอความช่วยเหลือ**

### **ปัญหาทางเทคนิค**
1. ลองรีเฟรชหน้าเว็บ
2. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต
3. ล็อกเอาต์แล้วล็อกอินใหม่

### **ปัญหาเกี่ยวกับข้อมูล**
1. ตรวจสอบรูปแบบข้อมูลที่กรอก
2. ตรวจสอบสิทธิ์การใช้งาน
3. อ่านข้อความ error ให้ละเอียด

---

**Last Updated:** June 30, 2025  
**Version:** 2.0  
**สำหรับคำถามเพิ่มเติม:** ติดต่อ Development Team
