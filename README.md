# Movie Buddy

Movie Buddy helps adults find a companion for a public cinema screening. The product requirements, business rules, safety guidance, and design references are in [movie-buddy-docs/docs](./movie-buddy-docs/docs/).

## Current implementation

The first UI milestone is the join-request flow on both Expo mobile and responsive web. It uses Cyber Twilight styling and sample content. **The API and authentication are not implemented yet**; the visible submit action is a local preview and does not send or persist a request.

## Run the UI

### Web

```powershell
cd web
npm install
npm run dev
```

### Expo mobile

```powershell
cd mobile
npm install
npm start
```

Use Node.js 22.13.0 or newer with the current Expo SDK. The mobile app can be opened in an Expo development build or simulator. The browser UI is in `web/`; it is separate from the admin panel.

## Run the backend

```powershell
cd backend
$env:PHPRC = Join-Path $env:LOCALAPPDATA 'MovieBuddy\php-ext\mongodb-2.5.6\php.ini'
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

In `backend/.env`, keep the database password local and configure:

```dotenv
DB_CONNECTION=mongodb
DB_URI="mongodb+srv://<database-user>:<URL-encoded-password>@<atlas-cluster-host>/?retryWrites=true&w=majority&appName=MovieBuddy"
DB_DATABASE=movie_buddy
```

Replace the placeholders with the URI from Atlas **Drivers → PHP**. URL-encode special characters in the password. In Atlas, grant this database user access to the app database and allow your current public IP; avoid opening access to `0.0.0.0/0`. Never commit or paste the completed URI into chat.

Then verify the MongoDB PHP extension and Atlas connectivity before running migrations or the API:

```powershell
php --ri mongodb
php artisan mongodb:check
php artisan migrate
php artisan serve
```

The development machine's MongoDB PHP driver is installed in a user-local PHP configuration and is not included in the repository. Set `$env:PHPRC` to that configuration in each new PowerShell session before running Composer or Artisan. For another PHP installation, install the matching official MongoDB PHP extension and enable it in that runtime.

Configure `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` in `backend/.env` before requesting an OTP. The API sends a six-digit code by email, stores only its hash, expires it after 10 minutes, and removes expired records with `php artisan otp:purge` (also scheduled each minute by Laravel's scheduler). Run `php artisan schedule:work` to enable scheduled cleanup.

For tests, run `cd backend; php artisan test`. Tests use an in-memory SQLite database and a fake mailer; test models are selected separately from MongoDB models so local tests don't require Atlas credentials.

The local `.env` created during development may still be set to SQLite so the OTP tests can run without Atlas credentials. Switch it to `DB_CONNECTION=mongodb` and set `DB_URI` before launching the API against Atlas. MongoDB mode uses ObjectId-backed Eloquent models and a MongoDB-compatible Sanctum personal access token model. A live Atlas connection and ObjectId token round-trip must still be verified after configuring the real password and allowing the current IP.

## Project structure

- `mobile/` — Expo / React Native user app
- `web/` — responsive React/Vite user interface
- `backend/` — Laravel API scaffold; application code is not implemented yet
- `admin/` — admin interface scaffold; application code is not implemented yet
- `face-service/` — photo-verification service scaffold; application code is not implemented yet
- `movie-buddy-docs/` — product, architecture, safety, and UI specifications
