<?php

namespace App\Models;

use App\Domain\Enums\CorruptionSubjectType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CorruptionState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'intensity' => 'integer',
        'recorded_on' => 'date',
        'started_date' => 'date',
        'is_current' => 'boolean',
        'visible_signs' => 'array',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CorruptionEvent::class);
    }

    public function subjectDisplayName(): string
    {
        $resolved = $this->getAttribute('resolved_subject_name');
        if (is_string($resolved) && $resolved !== '') {
            return $resolved;
        }

        return $this->fallbackSubjectLabel();
    }

    /**
     * Batch-resolve morph subjects into human-readable names on each row.
     *
     * @param  EloquentCollection<int, self>|Collection<int, self>  $rows
     */
    public static function hydrateSubjectNames($rows): void
    {
        $grouped = Collection::make($rows)->groupBy('subject_type');
        $namesByType = [];

        foreach ($grouped as $type => $ofType) {
            $ids = $ofType->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
            $namesByType[$type] = self::lookupNamesForType((string) $type, $ids);
        }

        foreach ($rows as $row) {
            $type = (string) $row->subject_type;
            $id = (int) $row->subject_id;
            $name = $namesByType[$type][$id] ?? null;
            $row->setAttribute(
                'resolved_subject_name',
                $name !== null && $name !== ''
                    ? $name
                    : $row->fallbackSubjectLabel()
            );
        }
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private static function lookupNamesForType(string $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return match ($type) {
            CorruptionSubjectType::CHARACTER => Character::query()
                ->whereIn('id', $ids)
                ->get()
                ->mapWithKeys(fn (Character $c) => [$c->id => $c->displayName()])
                ->all(),
            CorruptionSubjectType::TERRITORY => Territory::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            CorruptionSubjectType::SETTLEMENT => Settlement::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            CorruptionSubjectType::MONASTERY => Monastery::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            CorruptionSubjectType::ARMY => Army::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            CorruptionSubjectType::HOUSEHOLD => DynastyHouse::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            CorruptionSubjectType::INSTITUTION => ReligiousOrder::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            default => [],
        };
    }

    private function fallbackSubjectLabel(): string
    {
        $type = (string) $this->subject_type;
        $id = (int) $this->subject_id;
        $label = match ($type) {
            CorruptionSubjectType::CHARACTER => 'Character',
            CorruptionSubjectType::TERRITORY => 'Territory',
            CorruptionSubjectType::SETTLEMENT => 'Settlement',
            CorruptionSubjectType::MONASTERY => 'Monastery',
            CorruptionSubjectType::ARMY => 'Army',
            CorruptionSubjectType::HOUSEHOLD => 'Household',
            CorruptionSubjectType::INSTITUTION => 'Institution',
            default => ucfirst($type !== '' ? $type : 'Subject'),
        };

        return "{$label} #{$id}";
    }
}
