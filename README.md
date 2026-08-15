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
php artisan diesirae:validate-world lys-1348
php artisan serve --port=8766
```

Inspect: `/dev/inspect`

## Tests

```bash
php artisan test
```

Tests use `dies_irae_game_testing` only.

## Docs

- `docs/architecture/DIES_IRAE_ARCHITECTURE.md`
- `docs/architecture/CHARACTER_CAREERS.md`
- `docs/dies_irae/FEUDALISM_REUSE_AUDIT.md`
- `docs/dies_irae/DIES_IRAE_DOMAIN_MAP.md`
- `docs/dies_irae/RECONSTRUCTION_PLAN.md`
