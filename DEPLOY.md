# Deploying to Render + MySQL

Render has no native PHP runtime, so the app runs in a Docker container.
Render also has no MySQL service, so the database lives somewhere MySQL is
hosted.

## 1. Put the project on GitHub

```bash
git init
git add .
git commit -m "BuyHub"
git remote add origin https://github.com/YOU/buyhub.git
git push -u origin main
```

## 2. Create the remote MySQL database

Pick any MySQL provider (Aiven, Railway, PlanetScale, freemysqlhosting,
...). Whatever they give you, you need five values:

| value | like |
|---|---|
| host | `abc123.aivencloud.com` |
| port | `16921` |
| database | `marketplace` |
| user | `avnadmin` |
| password | `...` |

Import the app's schema into it (your project's `database.sql`):

```bash
mysql -h HOST -P PORT -u USER -p < database.sql
```

Leave `database.sql` OUT of any future Render deployments that rebuild the DB.

## 3. Create a Render Web Service

1. Dashboard → **New + → Web Service**
2. Connect your GitHub repo
3. Environment: **Docker**  (because of the Dockerfile in the project root)
4. Instance type: pick the free/starter one
5. Click **Advanced** → **Add Environment Variable` for each:

| Key | Value |
|---|---|
| `DB_HOST` | your MySQL host |
| `DB_PORT` | your MySQL port |
| `DB_NAME` | `marketplace` |
| `DB_USER` | your MySQL user |
| `DB_PASS` | your MySQL password |
| `DB_SSL` | `true` (for Aiven/Railway; use `false` for local-test-style providers) |
| `APP_DEBUG` | `false` when you ship |

6. Click **Create Web Service**. Render builds the Docker image and starts
   Apache. A public URL appears when the build log reads "Your service is
   live".

## 4. Verify

- Open the Render URL — the home page should load.
- Visit `/login.php` and log in with a demo account (`aisyah@example.com` / `password123`).
- Add an item — `/uploads` must be writable, which the Dockerfile already handles.

## 5. Uploads caveat

Render's filesystem is **ephemeral**: uploaded photos disappear on every
redeploy. Render Disks can persist `/var/www/html/uploads` for real
production use; it's fine for a school project to accept the limitation.
