<?php

namespace Tests\Unit\Services;

use App\Models\Unit;
use App\Services\UnitHierarchy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private UnitHierarchy $hierarchy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hierarchy = new UnitHierarchy;
    }

    public function test_leaf_unit_returns_only_its_own_id(): void
    {
        $leaf = Unit::factory()->create();

        $ids = $this->hierarchy->descendantIds($leaf);

        $this->assertSame([$leaf->id], $ids);
    }

    public function test_descendant_ids_walks_three_levels(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id]);
        $grandchild = Unit::factory()->create(['unit_id' => $child->id]);
        $greatgrand = Unit::factory()->create(['unit_id' => $grandchild->id]);

        $ids = $this->hierarchy->descendantIds($root);

        sort($ids);
        $expected = [$root->id, $child->id, $grandchild->id, $greatgrand->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function test_ended_units_are_excluded(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $active = Unit::factory()->create(['unit_id' => $root->id]);
        Unit::factory()->ended()->create(['unit_id' => $root->id]);

        $ids = $this->hierarchy->descendantIds($root);

        sort($ids);
        $expected = [$root->id, $active->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function test_grouped_by_child_buckets_subtrees(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $childA = Unit::factory()->create(['unit_id' => $root->id]);
        $childB = Unit::factory()->create(['unit_id' => $root->id]);
        $grandA = Unit::factory()->create(['unit_id' => $childA->id]);
        $grandB = Unit::factory()->create(['unit_id' => $childB->id]);

        $groups = $this->hierarchy->descendantIdsGroupedByChild($root);

        $this->assertArrayHasKey($childA->id, $groups);
        $this->assertArrayHasKey($childB->id, $groups);
        sort($groups[$childA->id]);
        sort($groups[$childB->id]);
        $this->assertSame([$childA->id, $grandA->id], $groups[$childA->id]);
        $this->assertSame([$childB->id, $grandB->id], $groups[$childB->id]);
    }

    public function test_grouped_by_child_includes_leaf_child_bucket(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $leafChild = Unit::factory()->create(['unit_id' => $root->id]);

        $groups = $this->hierarchy->descendantIdsGroupedByChild($root);

        $this->assertSame([$leafChild->id => [$leafChild->id]], $groups);
    }

    public function test_grouped_by_child_excludes_ended_children(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $active = Unit::factory()->create(['unit_id' => $root->id]);
        Unit::factory()->ended()->create(['unit_id' => $root->id]);

        $groups = $this->hierarchy->descendantIdsGroupedByChild($root);

        $this->assertSame([$active->id], array_keys($groups));
    }

    public function test_grouped_by_child_returns_empty_for_root_without_children(): void
    {
        $root = Unit::factory()->create();

        $groups = $this->hierarchy->descendantIdsGroupedByChild($root);

        $this->assertSame([], $groups);
    }
}
