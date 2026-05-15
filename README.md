# Med Direct — Backend

Laravel 13 API with Sanctum bearer tokens, **platform roles**, and **clinic/lab staff abilities**.

## Docker (recommended)

From the **repository root** (`medical/`):

```bash
cp .env.docker.example .env.docker
docker compose up --build
docker compose exec backend php artisan db:seed   # demo users & organization
```

API: http://localhost:8000. See the [root README](../README.md) for MySQL, CORS/proxy, and troubleshooting.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## Roles (`users.role`)

| Role | Meaning |
|------|---------|
| `patient` | B2C portal user (default on `POST /api/register`). |
| `admin` | Platform operator; bypasses organization ability checks. |
| `staff` | Clinic/lab workforce; access is scoped per **organization** and **abilities** on `organization_members`. |

`role` is **not** mass-assignable from the API (only set in application code / seeders).

## Organizations & abilities

- **`organizations`**: clinic or lab (`type`: `clinic` \| `lab`).
- **`organization_members`**: links a `staff` user to an organization. **`is_owner`** means full abilities (stored `abilities` JSON ignored for permission checks).
- **Abilities** are string flags (see `App\Authorization\OrganizationAbilities`), e.g. `canCreatePatient`, `canEditLabResults`, `canManageStaff`, etc.

Login / `GET /api/user` returns `user.organizations[]` with `is_owner` and merged **`abilities`** booleans so the frontend can hide sections.

### Middleware (route aliases)

| Alias | Usage |
|-------|--------|
| `role:admin` | Requires `users.role === admin`. |
| `org.member` | Admin, or member of `{organization}` in the route. |
| `org.ability:canCreatePatient` | Admin, or member with that ability (owners always pass). |

Example in `routes/api.php`:

```php
Route::post('/organizations/{organization}/patients', ...)
    ->middleware(['auth:sanctum', 'org.ability:'.\App\Authorization\OrganizationAbilities::CAN_CREATE_PATIENT]);
```

### Managing staff abilities

`PATCH /api/organizations/{organization}/members/{memberUser}/abilities`  
Auth: `org.member`. Caller must be **platform admin**, **organization owner**, or have **`canManageStaff`**. Cannot change an **owner’s** abilities unless you are a platform admin.

Body: `{ "abilities": { "canCreatePatient": true, ... } }` — keys are merged with existing stored abilities; omitted keys keep their previous values.

## API routes (summary)

| Method | Path | Auth |
|--------|------|------|
| `POST` | `/api/register` | — |
| `POST` | `/api/login` | — |
| `GET` | `/api/user` | Bearer |
| `POST` | `/api/logout` | Bearer |
| `GET` | `/api/admin/ping` | Bearer + `role:admin` |
| `GET` | `/api/organizations/{organization}` | Bearer + `org.member` |
| `POST` | `/api/organizations/{organization}/patients` | Bearer + `org.ability:canCreatePatient` (placeholder) |
| `PATCH` | `/api/organizations/{organization}/members/{memberUser}/abilities` | Bearer + `org.member` + manage rules above |

## Seeded demo accounts (`password`)

| Email | Role | Notes |
|-------|------|--------|
| `admin@med-direct.local` | admin | `/api/admin/ping` |
| `test@example.com` | patient | B2C |
| `owner@demo-clinic.local` | staff | Owner of **Demo Clinic & Lab** |
| `tech@demo-clinic.local` | staff | Limited abilities (no `canCreatePatient`) |

After `db:seed`, organization slug: **`demo-clinic`**.
