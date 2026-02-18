# Local Development Setup — Laragon + SvelteKit

This guide explains how to run the **krpano-cms** locally using **Laragon** (Apache + PHP) for the backend API and **SvelteKit dev server** (`npm run dev`) for the frontend.

## Architecture Overview

```
Browser (localhost:5173)
   │
   ├── /api/*      ──proxy──▶  Laragon Apache (localhost:80)  ──▶  public_html/api/*.php
   ├── /projekt/*  ──proxy──▶  Laragon Apache (localhost:80)  ──▶  .htaccess rewrite ──▶ auth_proxy.php
   └── everything else        SvelteKit dev server (Vite HMR)
```

- **Vite dev server** (port `5173`) serves the SvelteKit frontend with hot-reload.
- **Laragon Apache** (port `80`) serves the PHP API and protected project files.
- Vite's built-in proxy forwards `/api` and `/projekt` requests to Apache.

---

## Prerequisites

- [Laragon](https://laragon.org/) installed (Full or Lite — must include Apache + PHP 8.x)
- [Node.js](https://nodejs.org/) v18+ and npm

---

## Step 1 — Clone & Install

```bash
git clone <your-repo-url> krpano-cms
cd krpano-cms/public_html
npm install
```

---

## Step 2 — Configure Laragon

### 2.1 — Create a Virtual Host pointing to `public_html`

1. Open Laragon → **Menu** → **Apache** → **sites-enabled** → **Add new**
2. Or manually create a `.conf` file in `C:\laragon\etc\apache2\sites-enabled\`:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/Users/AdrianLarek/Documents/Github/krpano-cms/public_html"
    ServerName krpano-cms.test

    <Directory "C:/Users/AdrianLarek/Documents/Github/krpano-cms/public_html">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
```

> **Important:** `DocumentRoot` must point to the `public_html` folder, **not** the repo root.  
> `AllowOverride All` is required so the `.htaccess` rewrite rules work.

### 2.2 — Update the hosts file (auto or manual)

Laragon usually does this automatically. If not, add this to `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1   krpano-cms.test
```

### 2.3 — Enable required PHP extensions

Open Laragon → **Menu** → **PHP** → **extensions** and ensure these are enabled:

- `zip`
- `mbstring`
- `openssl`

### 2.4 — Restart Apache

Click **Stop All** then **Start All** in Laragon and verify `http://krpano-cms.test` responds (you should see the SvelteKit build output or a directory listing).

---

## Step 3 — Configure the Vite Proxy

The file `public_html/vite.config.js` contains a proxy that forwards API calls to the PHP backend. Update the target to match your Laragon setup:

```js
// vite.config.js
export default defineConfig({
  plugins: [tailwindcss(), sveltekit()],
  server: {
    proxy: {
      "/api": "http://krpano-cms.test",
      "/projekt": "http://krpano-cms.test",
    },
  },
});
```

| Option | Value                    | Why                           |
| ------ | ------------------------ | ----------------------------- |
| Target | `http://krpano-cms.test` | Laragon's Apache virtual host |

> **Alternative:** If you prefer not to use a virtual host, you can proxy straight to `http://localhost` (Laragon's default), but a virtual host keeps things cleaner and avoids conflicts with other projects.

---

## Step 4 — `secure_config/` and `secure_projects/` (Critical)

The PHP API reads and writes data in two directories that live **outside** `public_html` (and therefore outside Apache's `DocumentRoot`). This is by design — they must **not** be web-accessible.

```
krpano-cms/               ← repo root
├── public_html/           ← Apache DocumentRoot
├── secure_config/         ← admin credentials + project registry
│   ├── admin_creds.php    ← username & password hash
│   └── projects_data.php  ← tracks all groups, projects, tokens, folder IDs
└── secure_projects/       ← actual tour files (HTML, XML, panos, etc.)
    └── g_xxxxxxxx/        ← group folder
        └── p_xxxxxxxx/    ← project folder (tour.html, tour.xml, panos/, …)
```

The PHP code resolves these paths from `public_html/api/`:

```php
// data_helper.php
realpath(__DIR__ . '/../../secure_projects')  // → krpano-cms/secure_projects
realpath(__DIR__ . '/../../secure_config')    // → krpano-cms/secure_config
```

### 4.1 — Make sure the directories exist

These should already be in the repo. If not:

```bash
cd krpano-cms
mkdir secure_config
mkdir secure_projects
```

### 4.2 — Check PHP `open_basedir` (important!)

Laragon's default `php.ini` may restrict PHP to only access files within certain directories (`open_basedir`). Since `secure_config` and `secure_projects` are **outside** `DocumentRoot`, you must ensure PHP is allowed to read them.

1. Open Laragon → **Menu** → **PHP** → **php.ini**
2. Search for `open_basedir`
3. Either:
   - **Disable it** (recommended for local dev): comment it out with `;`
     ```ini
     ;open_basedir =
     ```
   - **Or add the repo root** to the allowed paths:
     ```ini
     open_basedir = "C:\laragon\www;C:\Users\AdrianLarek\Documents\Github\krpano-cms"
     ```
4. **Restart Apache** after changing `php.ini`.

### 4.3 — Verify PHP can access the directories

Visit `http://krpano-cms.test/api/check_auth.php` in your browser.

- ✅ `{"authenticated": false}` — PHP can read `secure_config/admin_creds.php`, all is working.
- ❌ A PHP warning about `realpath()` or `file_exists()` — `open_basedir` is blocking access, go back to step 4.2.

---

## Step 5 — Run both servers

### Terminal 1 — Laragon

Just make sure Laragon is running (Apache started).

### Terminal 2 — SvelteKit

```bash
cd krpano-cms/public_html
npm run dev
```

Open **`http://localhost:5173`** in your browser. The SvelteKit app will load, and all `/api/*` and `/projekt/*` requests will be proxied to Laragon's Apache.

---

## Verifying the setup

1. **Check PHP backend:** Visit `http://krpano-cms.test/api/check_auth.php` — you should get:

   ```json
   { "authenticated": false }
   ```

2. **Check proxy:** Visit `http://localhost:5173/api/check_auth.php` — same JSON response, but served through Vite's proxy.

3. **Login test:** `POST` to `http://localhost:5173/api/login.php` with the admin credentials.

---

## Troubleshooting

| Problem                             | Solution                                                                        |
| ----------------------------------- | ------------------------------------------------------------------------------- |
| `ECONNREFUSED` on `/api/*`          | Apache is not running or the proxy target URL is wrong in `vite.config.js`      |
| `403 Forbidden`                     | `AllowOverride All` is missing in the Apache vhost config                       |
| `404` on API endpoints              | `DocumentRoot` is not pointing to `public_html`                                 |
| PHP errors about missing extensions | Enable `zip` in Laragon → PHP → Extensions                                      |
| `secure_projects` not found         | Make sure the directories exist at the repo root level                          |
| Sessions not persisting             | Ensure cookies are not blocked; API and proxy must share the same domain origin |

---

## Quick Reference

| Component                | URL                               | Port      |
| ------------------------ | --------------------------------- | --------- |
| SvelteKit (frontend)     | `http://localhost:5173`           | 5173      |
| Laragon Apache (backend) | `http://krpano-cms.test`          | 80        |
| API proxy (via Vite)     | `http://localhost:5173/api/*`     | 5173 → 80 |
| Project files proxy      | `http://localhost:5173/projekt/*` | 5173 → 80 |
