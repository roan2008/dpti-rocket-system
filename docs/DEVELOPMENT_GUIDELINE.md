# 📘 Development Guideline

This document describes the workflow and active sprint plan for the DPTI Rocket System project.

## 🚀 Development Workflow

1. **Requirement Review** – Gather user stories and create tasks.
2. **Backlog Grooming** – Prioritize items and estimate effort.
3. **Sprint Planning** – Select tasks for the upcoming two‑week sprint.
4. **Implementation** – Develop features in small increments following the SoC structure.
5. **Code Review** – Review each merge request for style and security.
6. **Testing** – Run the PHP linter (`php -l`) and execute manual feature tests.
7. **Documentation** – Update `docs/` whenever a feature is completed.
8. **Deployment** – Deploy to staging, run UAT, then push to production.

## 📅 Sprint Plan (July–August 2025)

| Sprint | Focus | Key Tasks | Status |
|-------|-------|-----------|---------|
| **Sprint 1** (Jul 14–Jul 27) | Stabilization | Bug fixes for production step tracking, minor UI tweaks | ✅ **COMPLETED** |
| **Current** (Jul 15) | Template Management | **MAJOR FIXES:** Template edit fields display, update operations, error handling | ✅ **COMPLETED** |
| **Sprint 2** (Jul 28–Aug 10) | Approval Workflow | Engineer approval screens, approval history logging | 🔄 **UPCOMING** |
| **Sprint 3** (Aug 11–Aug 24) | User Management | Admin user list, role editing, password reset | 📋 **PLANNED** |
| **Sprint 4** (Aug 25–Sep 7) | Reporting & Notifications | PDF/CSV exports, email alerts | 📋 **PLANNED** |
| **Sprint 5** (Sep 8–Sep 14) | Optimization & Release | Query tuning, final testing, deployment prep | 📋 **PLANNED** |

## 🎯 **Latest Achievements (July 15, 2025)**

### **Template Management System - FULLY RESOLVED** ✅
- **Fixed:** Template edit fields not displaying (JavaScript variable conflicts)
- **Fixed:** Template update failures (database row count logic)
- **Fixed:** Error redirect loops (template ID preservation)
- **Improved:** User experience with stay-in-edit mode after updates
- **Tested:** Comprehensive testing across all 9 templates (100% success rate)

**Impact:** Template management is now fully operational, enabling seamless creation and editing of production step templates.

Sprint progress is reviewed daily and the plan will be updated as milestones are reached.
