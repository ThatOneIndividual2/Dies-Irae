# Dies Irae

Persistent medieval apocalypse grand-strategy / roleplay simulation. Own Laravel app. Feudalism is a reference donor only.

## Stack

PHP 8.0+, Laravel 9, MySQL `dies_irae_game` (tests: `dies_irae_game_testing`).

## Setup

```bash
composer install
cp .env.example .env
# set DB_* to dies_irae_game / bfth
php artisan key:generate
php artisan migrate --seed
php artisan diesirae:seed-slice
php artisan serve --port=8766
```

Playable slice world: `provence-1347` (County of Salon, November 1347). Demo login: `lord@diesirae.test` / `password`.

`diesirae:seed-slice` rebuilds that world only. Fixture world `lys-1348` remains for kernel validation (`php artisan diesirae:validate-world lys-1348`); it is not the playable login.

Inspect: `/dev/inspect`

## Tests

```bash
php artisan test
```

Tests use `dies_irae_game_testing` only.

## Docs

- `docs/DIES_IRAE_VERTICAL_SLICE_STATUS.md` (playable loop, login, seed-slice)
- `docs/architecture/DIES_IRAE_ARCHITECTURE.md`
- `docs/architecture/CHARACTER_CAREERS.md`
- `docs/dies_irae/FEUDALISM_REUSE_AUDIT.md`
- `docs/dies_irae/DIES_IRAE_DOMAIN_MAP.md`
- `docs/dies_irae/RECONSTRUCTION_PLAN.md`
