# avdyuvak nodev1

Node.js port of the original Slim PHP avdyuvak API (in `../v1`, `../routersv1`, `../modelsv1`). Routes, request/response shapes, and SQL behavior mirror the PHP code 1:1 so the existing APK can swap base URLs without code changes.

## Stack

- Express 4 (HTTP server)
- Sequelize + mysql2 (existing MySQL schema, raw queries preserved from `modelsv1/User.php`)
- jsonwebtoken (HS256, same secret/expiry as PHP)
- firebase-admin (replaces `modelsv1/firebase.php` manual OAuth dance)
- exceljs (replaces PhpSpreadsheet for `yuvakXlReport` / `yuvakSabhaReport` / `yuvakXlPadhramniReport`)
- multer (profile JPGs land in `../profiles/<yid>.jpg` to match PHP)

## Setup

```
cp .env.example .env       # then fill in DB_PASSWORD and FIREBASE_SA_PATH
npm install
npm start                  # node server.js
```

Place the Firebase service-account JSON at the path you set in `FIREBASE_SA_PATH`. The PHP version reads `modelsv1/atmiyayuvak.json` — copying that file is the simplest migration.

## Endpoint list

All endpoints from `routersv1/user.router.php` are exposed at the same paths and verbs. JWT passthrough mirrors `src/middleware.php`. See the plan file at `~/.claude/plans/replace-all-the-php-ticklish-nygaard.md` for the full list and porting notes.

## Verification

1. `npm start` connects to MySQL and binds on `PORT`.
2. POST `/login` with valid creds returns the same `{ error, ...row, isMentor, token }` shape as PHP.
3. Diff JSON for `/getYuvakByTeam`, `/getSabhaByKK`, `/getAttYuvak`, `/getUpcomingBday` against the PHP server pointed at the same DB.
4. `/yuvakXlReport` writes to `../report/<kk_id>.xlsx`.
5. `/sendAllNotification` delivers to the matching FCM topic.

## Cutover

Point the proxy/vhost serving `/v1/*` at this Node service. PHP code is left intact for one release as rollback.
