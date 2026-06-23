# Project Tracker App

A React frontend + PHP backend project tracker with support for:
- Authentication via JWT
- Role-based user management
- Project and activity planning
- Weekly actual hours entry
- Dashboard and reports
- MySQL schema ready for import via phpMyAdmin

## Folder structure

- `backend/api/` — PHP REST API and configuration
- `frontend/` — React application built with Vite
- `db/` — MySQL schema and seed scripts

## Backend Setup (PHP / Apache)

1. Copy `backend/api/.env.example` to `backend/api/.env` and update values:

```ini
DB_HOST=127.0.0.1
DB_NAME=project_tracker
DB_USER=root
DB_PASS=
JWT_SECRET=change_this_secret
JWT_EXPIRE=28800
```

2. Place `backend/api/` under an Apache site or alias, or use XAMPP/WAMP with DocumentRoot pointed to `backend/api/`.
3. Ensure `mod_rewrite` is enabled so `.htaccess` can route requests to `index.php`.
4. Import the database schema using phpMyAdmin:
   - Import `db/schema.sql`
   - Import `db/seed.sql`

5. Access API endpoints at:
   - `POST /api/login`
   - `GET /api/me`
   - `GET /api/users`
   - `GET /api/projects`
   - `GET /api/weekly-actuals?week_start_date=2026-06-17`
   - `GET /api/lookups`
   - `GET /api/reports`

## Frontend Setup (React)

1. Navigate to `frontend/`
2. Install dependencies:

```bash
cd frontend
npm install
```

3. Copy `.env.example` to `.env` and update the API base URL if needed:

```env
VITE_API_BASE_URL=http://localhost/backend/api
```

4. Start the development server:

```bash
npm run dev
```

5. Open the application in your browser using the Vite development URL.

## Authentication

- Login uses email and password.
- Passwords are stored hashed using PHP `password_hash()`.
- JWT tokens are issued from `/api/login` and consumed by React.

## Key features

- Admin user management with group membership and roles
- Active/inactive user state
- Project creation with phase/type/status/priority
- Activity planning under projects with weekly planned hours
- Weekly actuals entry per user, week, activity
- Report endpoints for project and user summaries

## Sample data

Seed data includes:
- Admin: `admin@company.local`
- Manager: `manager@company.local`
- User: `user@company.local`
- One sample project and two activities

## Notes

- The backend returns structured JSON responses:

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

- The React application uses protected routes and auth context.
- Weekly actual records are unique by `user_id + activity_id + week_start_date`.
