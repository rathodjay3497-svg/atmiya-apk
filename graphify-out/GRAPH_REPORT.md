# Graph Report - nodev1 + modelsv1 + design  (2026-05-29)

## Corpus Check
- 26 files · ~288,697 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 274 nodes · 481 edges · 16 communities detected
- Extraction: 77% EXTRACTED · 22% INFERRED · 1% AMBIGUOUS · INFERRED: 106 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Legacy PHP User Model|Legacy PHP User Model]]
- [[_COMMUNITY_Node.js User Service & Auth|Node.js User Service & Auth]]
- [[_COMMUNITY_PHP-to-Node Migration Plan|PHP-to-Node Migration Plan]]
- [[_COMMUNITY_Agenda & Resource Hub UI|Agenda & Resource Hub UI]]
- [[_COMMUNITY_Yuvak & Sabha Management UI|Yuvak & Sabha Management UI]]
- [[_COMMUNITY_Analytics & Directory Dashboard|Analytics & Directory Dashboard]]
- [[_COMMUNITY_Yuvak Profile Detail Form|Yuvak Profile Detail Form]]
- [[_COMMUNITY_Contacts, Sampark & Tasks UI|Contacts, Sampark & Tasks UI]]
- [[_COMMUNITY_Firebase Notifications (Node)|Firebase Notifications (Node)]]
- [[_COMMUNITY_Input Validation Helpers|Input Validation Helpers]]
- [[_COMMUNITY_Legacy PHP Firebase Notifier|Legacy PHP Firebase Notifier]]
- [[_COMMUNITY_JSON Response Helpers|JSON Response Helpers]]
- [[_COMMUNITY_Database Access Layer|Database Access Layer]]
- [[_COMMUNITY_Swagger API Docs|Swagger API Docs]]
- [[_COMMUNITY_Express Server Entry|Express Server Entry]]
- [[_COMMUNITY_File Upload Helper|File Upload Helper]]

## God Nodes (most connected - your core abstractions)
1. `User` - 66 edges
2. `execute()` - 61 edges
3. `User` - 59 edges
4. `sel()` - 36 edges
5. `exec()` - 25 edges
6. `one()` - 22 edges
7. `Yuvak (Member) Entity` - 14 edges
8. `avdyuvak nodev1` - 11 edges
9. `Atmiya Yuvak Profile/Menu Drawer` - 9 edges
10. `Daily Agenda Dashboard Screen` - 6 edges

## Surprising Connections (you probably didn't know these)
- `validateDate()` --calls--> `exec()`  [INFERRED]
  nodev1\src\helpers\validate.js → nodev1\src\services\user.service.js
- `jwtMiddleware()` --calls--> `exec()`  [INFERRED]
  nodev1\src\middleware\jwt.js → nodev1\src\services\user.service.js
- `isJwtValid()` --calls--> `exec()`  [INFERRED]
  nodev1\src\middleware\jwt.js → nodev1\src\services\user.service.js
- `firebaseSend()` --calls--> `sendToTopic()`  [INFERRED]
  nodev1\src\routes\user.routes.js → nodev1\src\helpers\firebase.js
- `Timeline Event Card (Join / View Details)` --shares_data_with--> `Sabha (Meeting/Event) Entity`  [INFERRED]
  design/real_time_community_dashboard_3/screen.png → design/real_time_community_dashboard_2/screen.png

## Hyperedges (group relationships)
- **PHP to Node.js library replacement migration** — readme_avdyuvak_nodev1, readme_php_avdyuvak_api, readme_express4, readme_firebase_admin, readme_exceljs [EXTRACTED 0.90]
- **Excel report generation via exceljs** — readme_exceljs, readme_yuvak_xl_report, readme_yuvak_sabha_report, readme_yuvak_xl_padhramni_report [EXTRACTED 0.85]
- **Community Dashboard Flow (analytics + real-time overview)** — analytics_growth_dashboard_screen, realtime_dashboard_screen, shared_dashboard_bottom_nav [INFERRED 0.85]
- **Member-Centric Data Model across directory and dashboards** — member_directory_list_member_card, analytics_growth_leaderboard, realtime_dashboard_recent_activity, shared_member_entity [INFERRED 0.80]
- **Community Dashboard / Resource Flow** — real_time_community_dashboard_3_screen, real_time_community_dashboard_4_screen, sabha_schedule_events_screen [INFERRED 0.80]
- **Shared Bottom Navigation Pattern** — real_time_community_dashboard_3_bottom_nav, real_time_community_dashboard_4_bottom_nav, sabha_schedule_events_bottom_nav [INFERRED 0.75]
- **Sabha App Main Navigation Flow (Members, Task, Sampark)** — img_contacts_atmiya_screen, img_task_list_screen, img_sampark_screen, img_bottom_nav_bar [INFERRED 0.80]
- **Member Contact and Outreach Pattern** — img_contact_card, img_contact_call_whatsapp_actions, img_sampark_contact_card [INFERRED 0.75]
- **Yuvak Member Management Flow** — img_525238_1_drawer, img_525238_2_yuvak_list, img_525238_2_yuvak_card, img_525238_2_contact_actions [INFERRED 0.80]
- **Sabha Session Attendance Tracking Flow** — img_525238_sabha_list, img_525238_sabha_entry, img_525238_attendance_counts, img_525238_sabha_search [INFERRED 0.80]

## Communities

### Community 0 - "Legacy PHP User Model"
Cohesion: 0.07
Nodes (2): execute(), User

### Community 1 - "Node.js User Service & Auth"
Cohesion: 0.08
Nodes (6): isJwtValid(), jwtMiddleware(), exec(), one(), sel(), User

### Community 2 - "PHP-to-Node Migration Plan"
Cohesion: 0.09
Nodes (22): modelsv1/atmiyayuvak.json, avdyuvak nodev1, exceljs, Express 4 HTTP server, firebase-admin, modelsv1/firebase.php, jsonwebtoken (HS256), POST /login endpoint (+14 more)

### Community 3 - "Agenda & Resource Hub UI"
Cohesion: 0.11
Nodes (22): Bottom Navigation (Dashboard / Groups / Document / More), Daily Timeline (Events List), Weekly Date Selector, Timeline Event Card (Join / View Details), Quick Notes Input, Daily Agenda Dashboard Screen, User Greeting (Tirth Patel), Bottom Navigation (Dashboard / Groups / Document / More) (+14 more)

### Community 4 - "Yuvak & Sabha Management UI"
Cohesion: 0.13
Nodes (19): Atmiya Yuvak Profile/Menu Drawer, Birthday Message Menu Item, Broadcast Message Menu Item, Change Password Menu Item, Download Report Menu Item, Logout Menu Item, Search Other Yuvak Menu Item, Set My Location Menu Item (+11 more)

### Community 5 - "Analytics & Directory Dashboard"
Cohesion: 0.19
Nodes (17): Analytics & Growth Dashboard Screen, KPI Gauges (Sabha Attendance %, Task Completion Rate), Member Leaderboard, Member Growth Chart (Last 30 Days), Add Member Floating Action Button, Bottom Nav (Directory, Sabha, Sampark, More), Contact Actions (Call, Chat, WhatsApp), Member Card (name, group, stats, contact actions) (+9 more)

### Community 6 - "Yuvak Profile Detail Form"
Cohesion: 0.13
Nodes (16): Address, Back Navigation, City / Village, Country, Date of Birth, Delete Action (toolbar), Father Name, Phone Number (+8 more)

### Community 7 - "Contacts, Sampark & Tasks UI"
Cohesion: 0.18
Nodes (15): Bottom Navigation Bar (Members/Reports/Sampark/Task), Add Contact Floating Button, Call and WhatsApp Quick Actions, Member Contact Card, Atmiya Contacts List Screen, Contact Search Bar (by name, number), Contact Status Counters (183/18/93), Till Today's Birthdays Section (+7 more)

### Community 8 - "Firebase Notifications (Node)"
Cohesion: 0.33
Nodes (3): init(), sendToTopic(), firebaseSend()

### Community 9 - "Input Validation Helpers"
Cohesion: 0.29
Nodes (1): validateDate()

### Community 10 - "Legacy PHP Firebase Notifier"
Cohesion: 0.5
Nodes (1): FirebaseNotifier

### Community 11 - "JSON Response Helpers"
Cohesion: 0.83
Nodes (3): coerce(), isNumericString(), sendJson()

### Community 12 - "Database Access Layer"
Cohesion: 0.67
Nodes (0): 

### Community 13 - "Swagger API Docs"
Cohesion: 1.0
Nodes (0): 

### Community 14 - "Express Server Entry"
Cohesion: 1.0
Nodes (0): 

### Community 15 - "File Upload Helper"
Cohesion: 1.0
Nodes (0): 

## Ambiguous Edges - Review These
- `Member Directory List Screen` → `Sabha (Meeting/Event) Entity`  [AMBIGUOUS]
  design/member_directory_list/screen.png · relation: references
- `Bottom Navigation (Dashboard / Groups / Document / More)` → `Bottom Navigation (with Sabha tab)`  [AMBIGUOUS]
  design/sabha_schedule_events/screen.png · relation: semantically_similar_to
- `Task Card (title, assignee, group, due date)` → `Sampark Person Card with Group/Karyakar`  [AMBIGUOUS]
  design/whatsapp_image_2026_05_26_at_19.52.37_1.jpeg/screen.png · relation: shares_data_with

## Knowledge Gaps
- **54 isolated node(s):** `Express 4 HTTP server`, `jsonwebtoken (HS256)`, `multer`, `modelsv1/User.php`, `modelsv1/firebase.php` (+49 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **Thin community `Swagger API Docs`** (2 nodes): `swagger.js`, `setupSwagger()`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Express Server Entry`** (1 nodes): `server.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `File Upload Helper`** (1 nodes): `upload.js`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Member Directory List Screen` and `Sabha (Meeting/Event) Entity`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Bottom Navigation (Dashboard / Groups / Document / More)` and `Bottom Navigation (with Sabha tab)`?**
  _Edge tagged AMBIGUOUS (relation: semantically_similar_to) - confidence is low._
- **What is the exact relationship between `Task Card (title, assignee, group, due date)` and `Sampark Person Card with Group/Karyakar`?**
  _Edge tagged AMBIGUOUS (relation: shares_data_with) - confidence is low._
- **Why does `User` connect `Node.js User Service & Auth` to `Firebase Notifications (Node)`?**
  _High betweenness centrality (0.047) - this node is a cross-community bridge._
- **Why does `execute()` connect `Legacy PHP User Model` to `Database Access Layer`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **Are the 60 inferred relationships involving `execute()` (e.g. with `.checkLogin()` and `.changePassword()`) actually correct?**
  _`execute()` has 60 INFERRED edges - model-reasoned connections that need verification._
- **Are the 3 inferred relationships involving `exec()` (e.g. with `validateDate()` and `jwtMiddleware()`) actually correct?**
  _`exec()` has 3 INFERRED edges - model-reasoned connections that need verification._