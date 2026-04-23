# LanShout

<p align="center">
  <a href="https://github.com/lan-software/LanShout/actions/workflows/tests.yml"><img src="https://github.com/lan-software/LanShout/actions/workflows/tests.yml/badge.svg" alt="Tests" /></a>
  <a href="https://github.com/lan-software/LanShout/actions/workflows/frontend-tests.yml"><img src="https://github.com/lan-software/LanShout/actions/workflows/frontend-tests.yml/badge.svg" alt="Frontend Tests" /></a>
  <a href="https://github.com/lan-software/LanShout/actions/workflows/lint.yml"><img src="https://github.com/lan-software/LanShout/actions/workflows/lint.yml/badge.svg" alt="Linter" /></a>
  <a href="https://github.com/lan-software/LanShout/actions/workflows/docker-publish.yml"><img src="https://github.com/lan-software/LanShout/actions/workflows/docker-publish.yml/badge.svg" alt="Docker" /></a>
  <a href="https://codecov.io/gh/lan-software/LanShout"><img src="https://codecov.io/gh/lan-software/LanShout/graph/badge.svg" alt="Coverage" /></a>
</p>

LanShout is a satellite app in the Lan-Software platform. It integrates with **LanCore** for SSO, user directory, announcements, and role updates via the shared `lancore-client` package.

## Local Development Quick Start

LanShout runs inside **Laravel Sail** and connects to the **shared development infrastructure** provided by the `platform/` repository (PostgreSQL, Redis, Mailpit). Do not run a standalone database or mail container per app — all Lan\* apps share the same Postgres/Redis/Mailpit containers and join the external `lanparty` Docker network.

### Prerequisites

- Docker and Docker Compose
- The full monorepo checkout, with `LanCore/`, `LanShout/`, and `platform/` as sibling directories

### 1) Start the shared infrastructure (once)

From the monorepo root:

```bash
cd platform/dev
./setup.sh
```

This creates the external `lanparty` network and starts `infrastructure-pgsql` (port 5430), `infrastructure-redis` (port 6370), and `infrastructure-mailpit` (ports 1025 SMTP / 8021 UI). See [`platform/README.md`](../platform/README.md) for details.

### 2) Start LanCore

LanShout depends on LanCore for authentication and user data. Bring LanCore up first so its container (`lancore.test`) is resolvable on the `lanparty` network:

```bash
cd LanCore
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail artisan integrations:sync   # seeds the integration app rows + tokens
```

Copy the token minted for `lanshout` (visible in LanCore's integration admin UI, or via `vendor/bin/sail artisan tinker`) — you will paste it into LanShout's `.env` in the next step.

### 3) Start LanShout

```bash
cd LanShout
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm install
```

LanShout is then reachable at **http://localhost:82** (Vite on 5175). The frontend build runs automatically via the shared `platform/dev/dev-entrypoint.sh`, so no separate `npm run dev` on the host is needed.

### 4) Wire up the LanCore integration

Edit `.env` with the values from step 2. The `LANCORE_BASE_URL` is the browser-facing URL (used for SSO redirects); `LANCORE_INTERNAL_URL` is the in-network hostname used for server-to-server calls:

```env
LANCORE_ENABLED=true
LANCORE_BASE_URL=http://localhost
LANCORE_INTERNAL_URL=http://lancore.test
LANCORE_TOKEN=lci_...              # from LanCore integrations:sync
LANCORE_APP_SLUG=lanshout
LANCORE_CALLBACK_URL=${APP_URL}/auth/lancore/callback
LANCORE_WEBHOOK_SECRET=...          # matches LANSHOUT_ANNOUNCEMENT_WEBHOOK_SECRET on LanCore
```

Restart the container so the new env is picked up:

```bash
vendor/bin/sail restart
```

### Default test user

The seeder creates `test@example.com` / `password`.

### Important ports

| Service | URL |
|---------|-----|
| LanShout (browser) | http://localhost:82 |
| LanCore (browser, for SSO) | http://localhost |
| Mailpit UI | http://localhost:8021 |
| LanCore (from inside containers) | http://lancore.test |
| LanShout (from inside containers) | http://lanshout.test |

### Teardown

```bash
# Stop LanShout only
cd LanShout && vendor/bin/sail down

# Stop the whole platform (shared infra)
cd platform/dev && docker compose down
# Add -v to also destroy shared Postgres / Redis / Mailpit data
```

## Troubleshooting

- **`Could not resolve host: lancore.test`** from the LanShout container — LanCore isn't running, or its compose stack isn't on the `lanparty` network. Run `vendor/bin/sail up -d` in `LanCore/` and verify with `docker network inspect lanparty`.
- **`SQLSTATE[08006] could not translate host name "infrastructure-pgsql"`** — the shared infrastructure isn't running. Run `./platform/dev/setup.sh`.
- **401 from LanCore during SSO** — `LANCORE_TOKEN` in LanShout's `.env` doesn't match the token stored in LanCore. Re-run `vendor/bin/sail artisan integrations:sync` on LanCore and copy the fresh token.
- **Webhook signature mismatch** — `LANCORE_WEBHOOK_SECRET` on LanShout must equal `LANSHOUT_ANNOUNCEMENT_WEBHOOK_SECRET` (and/or `LANSHOUT_ROLES_WEBHOOK_SECRET`) on LanCore.
- **Vite manifest missing** — the shared entrypoint normally runs `npm run build` on boot; if it failed, run `vendor/bin/sail npm run build` manually.

## Legacy standalone setup

Earlier releases of LanShout included an app-local `docker-compose.yml` that bundled its own Postgres/Redis/MailHog and expected `php artisan serve` + `npm run dev` on the host. That flow has been removed — the file is preserved as `docker-compose.yml.orig` for reference only and is no longer supported.
