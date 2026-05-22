# EPICs & User Stories — Learn Academy Platform

## EPIC 1 — Course Content Parser

**E1-US1:** As a content creator, I can place numbered files in a folder hierarchy and the system builds a structured course model.  
**E1-US2:** As a content creator, I can create optional `.conf.txt` files to override lesson title and layout.  
**E1-US3:** As a content creator, if no `.conf.txt` exists, one is auto-created with the title derived from the highest-priority filename.  
**E1-US4:** As a content creator, I can use `{{img:filename.jpg}}` in markdown to embed images from the same lesson.  
**E1-US5:** As a content creator, PDF/DOCX/ZIP files are automatically placed in a "Sources" panel.  
**E1-US6:** As a content creator, sub-lessons with letter suffixes (001a, 001b) are parsed in correct order.

---

## EPIC 2 — Static Course Generator

**E2-US1:** As a content creator, I can run the CLI and get a self-contained HTML/CSS/JS course bundle.  
**E2-US2:** As a student, I can view the course in any browser with no server.  
**E2-US3:** As a student, my progress (completed lessons) is saved in localStorage per course.  
**E2-US4:** As a student, my settings (theme, font size, playback speed, subtitles) persist in localStorage.  
**E2-US5:** As a student, video lessons show playback speed control and subtitle toggle.  
**E2-US6:** As a student, I can resume a video from where I left off (timestamp saved in localStorage).

---

## EPIC 3 — Authentication & Access Control

**E3-US1:** As a user, I can register with email and password.  
**E3-US2:** As a user, I can log in and log out.  
**E3-US3:** As an admin, I can grant a user access to a course (full or per-section).  
**E3-US4:** As an admin, I can set an expiry date for access (default: 1 year from grant date).  
**E3-US5:** As a user, I can browse all courses without logging in (listing and detail pages).  
**E3-US6:** As a user, locked lessons show a padlock icon; clicking prompts login or purchase.  
**E3-US7:** As an admin, I can revoke access from a user at any time.

---

## EPIC 4 — Dynamic PHP Backend

**E4-US1:** As a content creator, I can generate a PHP-backed course from the CLI.  
**E4-US2:** As a student, my progress is stored on the server and syncs across devices.  
**E4-US3:** As a student, my settings are stored on the server and sync across devices.  
**E4-US4:** As a developer, the API returns course structure as JSON (`GET /api/course/{slug}`).  
**E4-US5:** As a developer, the API provides progress endpoints (`GET /api/progress`, `POST /api/progress`).

---

## EPIC 5 — Multilingual UI

**E5-US1:** As a student, I can switch the interface language between English and Spanish.  
**E5-US2:** As a student, my language preference is saved to my account.  
**E5-US3:** As a visitor, the interface defaults to my browser language (EN or ES).  
**E5-US4:** As a developer, all UI strings use the `t('key')` helper — no hardcoded UI strings.

---

## EPIC 6 — Payment Integration

**E6-US1:** As a user, I can purchase access to a course via Stripe (test mode initially).  
**E6-US2:** As a user, I can purchase access via PayPal.  
**E6-US3:** After successful payment, my access is automatically activated for 1 year.  
**E6-US4:** As an admin, I can view payment history per user and per course.  
**E6-US5:** As a developer, Stripe and PayPal webhooks confirm payment and create the enrollment record.

---

## EPIC 7 — Comments & Moderation

**E7-US1:** As a student, I can post a comment on any lesson.  
**E7-US2:** As a student, my comment shows immediately as "(Pending moderation)" — visible only to me.  
**E7-US3:** As an admin, I see a badge showing the count of pending comments awaiting review.  
**E7-US4:** As an admin, I can approve or reject pending comments from a moderation panel.  
**E7-US5:** Approved comments are visible to all enrolled students on that lesson.  
**E7-US6:** As a student, I can reply to an approved comment.  
**E7-US7:** Replies require moderation using the same pending flow.  
**E7-US8:** As a student, I can see my own pending replies with a "(Pending moderation)" label.

---

## EPIC 8 — User Dashboard & Statistics

**E8-US1:** As a student, I can see my overall progress across all enrolled courses (% completed).  
**E8-US2:** As a student, I can see a breakdown of completed lessons per course section.  
**E8-US3:** As a student, I can view my evaluation results (score, date, course, lesson).  
**E8-US4:** As a student, I can see my active course enrollments with their expiry dates.  
**E8-US5:** As an admin, I can view progress and statistics for any user.

---

## EPIC 9 — Web Editor

**E9-US1:** As a content creator, I can point the editor to a source directory and see the full course structure.  
**E9-US2:** As a content creator, I can edit lesson configuration (.conf.txt fields) via a form — no manual file editing required.  
**E9-US3:** As a content creator, I can upload and delete files within any lesson.  
**E9-US4:** As a content creator, I can add or remove sections and lessons.  
**E9-US5:** As a content creator, I can reorder sections and lessons via drag-and-drop.  
**E9-US6:** As a content creator, I can preview any lesson as it would appear in the generated course.  
**E9-US7:** As a content creator, I can trigger static or dynamic course generation directly from the editor UI.

---

## EPIC 10 — Course Access & Enrollment Model

**E10-US1:** As a user, all courses are browsable (listing + detail) without authentication.  
**E10-US2:** As a user, locked content shows a visual padlock indicator but no content is revealed.  
**E10-US3:** After purchase or admin grant, a user's enrollment is valid for 1 year.  
**E10-US4:** As an admin, I can unlock the full course or specific sections for a user.  
**E10-US5:** Expired access shows a renewal prompt on locked lessons instead of the content.

---

## Implementation Dependency Graph

```
Phase 0: README + EPICS + Schema + i18n scaffolds
Phase 1: Parser + Data Model          (E1)
Phase 2: Static Generator + CLI       (E2) → depends on Phase 1
Phase 3: Auth + DB Schema             (E3) → depends on Phase 1
Phase 4: Dynamic Backend API          (E4) → depends on Phase 3
Phase 5: Frontend + i18n              (E5) → depends on Phase 4
Phase 6: Access Control UI            (E10) → depends on Phase 3, 5
Phase 7: Payments                     (E6) → depends on Phase 3
Phase 8: Comments + Moderation        (E7) → depends on Phase 4, 5
Phase 9: Dashboard + Statistics       (E8) → depends on Phase 4, 5
Phase 10: Web Editor                  (E9) → depends on Phase 1, 3
```
