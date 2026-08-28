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

### Provence vertical slice (`provence-1347`)

Playable slice world: `provence-1347` (County of Salon, November 1347). Demo login: `lord@diesirae.test` / `password`.

`diesirae:seed-slice` rebuilds that world only. Fixture world `lys-1348` remains for kernel validation (`php artisan diesirae:validate-world lys-1348`); it is not the playable login.

### Europa 1347 campaign (`europa-1347`)

Campaign play world (Europe, 1 October 1347). Seed with an archetype (default `king`):

```bash
php artisan diesirae:seed-1347
# or: php artisan diesirae:seed-1347 count
# rebuild that world only: php artisan diesirae:seed-1347 king --force
```

Each run creates one player login for the chosen archetype. Credentials come from `config/campaign.php` (`demo_users`). Password for all is `password`:

| Archetype | Email |
| --- | --- |
| `king` | `king@diesirae.test` |
| `duke` | `duke@diesirae.test` |
| `count` | `count@diesirae.test` |
| `minor_lord` | `lord@europa.diesirae.test` |
| `bishop` | `bishop@diesirae.test` |
| `prince_bishop` | `princebishop@diesirae.test` |
| `holy_order` | `hospital@diesirae.test` |

Override with `--email=` / `--password=` if needed. The seeder prints the login it created.

**Slug collision:** both `diesirae:seed-1347` (campaign / archetype play) and `diesirae:seed-historical` (historical map pack under `database/data/europa/1347/`) target world slug `europa-1347`. Whichever runs first owns that slug; the other skips. Use `diesirae:seed-1347 … --force` to rebuild the campaign world only. Do not confuse either with `diesirae:seed-slice` (`provence-1347`).

- Campaign seed (play + archetype login): `php artisan diesirae:seed-1347`
- Historical seed (integrity / map pack only): `php artisan diesirae:seed-historical` then `php artisan diesirae:validate-world --historical`

More detail: `docs/world/1347_WORLD_SEED.md`.

Inspect: `/dev/inspect`

## Tests

```bash
php artisan test
```

Tests use `dies_irae_game_testing` only.

## Docs

- `docs/DIES_IRAE_VERTICAL_SLICE_STATUS.md` (playable loop, login, seed-slice)
- `docs/world/1347_WORLD_SEED.md` (Europa 1347 historical pack vs campaign seed)
- `docs/architecture/DIES_IRAE_ARCHITECTURE.md`
- `docs/architecture/CHARACTER_CAREERS.md`
- `docs/dies_irae/FEUDALISM_REUSE_AUDIT.md`
- `docs/dies_irae/DIES_IRAE_DOMAIN_MAP.md`
- `docs/dies_irae/RECONSTRUCTION_PLAN.md`
