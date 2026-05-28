# Avdyuvak

An application ecosystem for yuvak data management, profiles, and attendance. This repository contains both the legacy Slim PHP API and the new, high-performance Express Node.js API.

---

## Repository Structure

- `nodev1/`: Node.js port of the server (Express + Sequelize).
- `db/`: Database migrations and raw SQL backup dumps.
- `v1/`, `routersv1/`, `modelsv1/`, `src/`: Legacy PHP codebase.
- `profiles/`: Directory where user profile JPGs are stored.
- `report/`: Output directory for generated Excel reports.

---

## Quick Start (Node.js Server)

Follow these steps to set up the database and run the server locally on Windows.

### 1. Database Installation and Configuration

The Node.js application is configured to run on a local MariaDB or MySQL instance.

1. **Install MariaDB/MySQL** via PowerShell:
   ```powershell
   winget install --id MariaDB.Server --source winget --silent --accept-package-agreements --accept-source-agreements --custom "SERVICENAME=MySQL PORT=3306 PASSWORD=3690@Pjda$"
   ```
2. **Create the Database and User**:
   Open a MySQL client or run the following SQL command:
   ```sql
   CREATE DATABASE IF NOT EXISTS `atmiykkq_atmiyayuvakk` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER IF NOT EXISTS 'atmiykkq'@'localhost' IDENTIFIED BY '3690@Pjda$';
   GRANT ALL PRIVILEGES ON `atmiykkq_atmiyayuvakk`.* TO 'atmiykkq'@'localhost';
   FLUSH PRIVILEGES;
   ```
3. **Import Database Dump**:
   Import the backup dump file located at `db/atmiykkq_atmiyayuvakk-25-05-2026.sql`:
   ```powershell
   & "C:\Program Files\MariaDB 12.2\bin\mysql.exe" -u atmiykkq -p3690@Pjda$ atmiykkq_atmiyayuvakk -e "source db/atmiykkq_atmiyayuvakk-25-05-2026.sql"
   ```

---

### 2. Node.js Application Setup

1. Navigate to the Node.js application directory:
   ```bash
   cd nodev1
   ```
2. Configure the environment variables in `.env`:
   ```env
   PORT=8080
   DB_HOST=localhost
   DB_USER=atmiykkq
   DB_PASSWORD=3690@Pjda$
   DB_NAME=atmiykkq_atmiyayuvakk
   JWT_SECRET=rKc53w39uH59326Fe4Ky
   JWT_EXPIRES_IN=365d
   ENCRYPTION_KEY=haridham
   FIREBASE_SA_PATH=./firebase-service-account.json
   FIREBASE_PROJECT_ID=avdyuva-dadb4
   PROFILES_DIR=../profiles
   ```
3. Install the dependencies:
   ```bash
   npm install
   ```

---

### 3. Running the Server

#### Development Mode
To start the Express server in development mode:
```bash
npm start
```
The server will run on `http://localhost:8080`.

#### Testing the Server
Verify connection and auth endpoints using curl or a client:
```bash
curl -X POST http://localhost:8080/login \
     -H "Content-Type: application/json" \
     -d "{\"username\": \"test_user\", \"password\": \"test_pass\"}"
```

#### API Documentation
Access the interactive Swagger UI API documentation locally:
- **URL**: [http://localhost:8080/api-docs](http://localhost:8080/api-docs)

