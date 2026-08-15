<?php

namespace Tests\Unit\Hell;

use App\Domain\Hell\Enums\DemonCategory;
use App\Domain\Hell\TaxonomyCatalog;
use PHPUnit\Framework\TestCase;

final class TaxonomyCatalogTest extends TestCase
{
    public function test_catalog_loads_required_categories_and_named_uniques(): void
    {
        $catalog = TaxonomyCatalog::load(DemonicThreatHarness::dataDir().'/taxonomy.json');

        foreach (DemonCategory::all() as $category) {
            $this->assertContains($category, $catalog->categories());
        }

        $this->assertTrue($catalog->hasTemplate('whispering_tempter'));
        $this->assertTrue($catalog->hasNamed('apollyon_the_unmaker'));
        $this->assertTrue($catalog->hasFaction('locust_host'));
        $this->assertTrue($catalog->sharesTheme('mammon_the_gilded', 'greed'));
        $this->assertSame('never', $catalog->respawnPolicy('apollyon_the_unmaker'));
        $this->assertSame('lore_only', $catalog->respawnPolicy('mammon_the_gilded'));
        $this->assertSame('corrupter', $catalog->named('mammon_the_gilded')['strategy']);
        $this->assertSame('cult_organizer', $catalog->named('the_whisper_in_the_nave')['strategy']);
        $this->assertSame('military_invader', $catalog->named('apollyon_the_unmaker')['strategy']);
    }

    public function test_new_template_is_usable_without_engine_code_change(): void
    {
        $catalog = TaxonomyCatalog::load(DemonicThreatHarness::dataDir().'/taxonomy.json');
        $catalog->hydrate([
            'categories' => array_map(fn ($k) => ['key' => $k], DemonCategory::all()),
            'themes' => $catalog->themes(),
            'templates' => array_merge(array_values($catalog->templates()), [[
                'key' => 'ash_wraith',
                'category' => 'lesser',
                'themes' => ['despair'],
                'capabilities' => ['haunt'],
                'rank_weight' => 9,
            ]]),
            'named' => array_values($catalog->namedAll()),
            'factions' => array_values($catalog->factions()),
        ]);

        $this->assertSame(9, $catalog->rankWeight('ash_wraith'));
        $this->assertTrue($catalog->sharesTheme('ash_wraith', 'despair'));
    }

    public function test_unknown_entity_is_rejected(): void
    {
        $catalog = TaxonomyCatalog::load(DemonicThreatHarness::dataDir().'/taxonomy.json');
        $this->expectException(\InvalidArgumentException::class);
        $catalog->entity('not_a_demon');
    }
}
