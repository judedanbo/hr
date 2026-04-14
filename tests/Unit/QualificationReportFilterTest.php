<?php

namespace Tests\Unit;

use App\DataTransferObjects\QualificationReportFilter;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class QualificationReportFilterTest extends TestCase
{
    public function test_from_request_maps_all_known_fields(): void
    {
        $req = Request::create('/x', 'GET', [
            'unit_id' => '5',
            'department_id' => '2',
            'level' => 'masters',
            'status' => 'approved',
            'year_from' => '2010',
            'year_to' => '2020',
            'gender' => 'F',
            'job_category_id' => '3',
            'institution' => 'Legon',
            'course' => 'Audit',
        ]);

        $f = QualificationReportFilter::fromRequest($req);

        $this->assertSame(5, $f->unitId);
        $this->assertSame(2, $f->departmentId);
        $this->assertSame('masters', $f->level);
        $this->assertSame('approved', $f->status);
        $this->assertSame(2010, $f->yearFrom);
        $this->assertSame(2020, $f->yearTo);
        $this->assertSame('F', $f->gender);
        $this->assertSame(3, $f->jobCategoryId);
        $this->assertSame('Legon', $f->institution);
        $this->assertSame('Audit', $f->course);
    }

    public function test_missing_fields_become_null(): void
    {
        $f = QualificationReportFilter::fromRequest(Request::create('/x', 'GET', []));
        $this->assertNull($f->unitId);
        $this->assertNull($f->yearFrom);
    }

    public function test_to_query_array_drops_nulls(): void
    {
        $f = QualificationReportFilter::fromArray(['level' => 'masters']);
        $this->assertSame(['level' => 'masters'], $f->toQueryArray());
    }

    public function test_cache_key_is_deterministic(): void
    {
        $a = QualificationReportFilter::fromArray(['unit_id' => 5, 'level' => 'masters']);
        $b = QualificationReportFilter::fromArray(['level' => 'masters', 'unit_id' => 5]);
        $this->assertSame($a->cacheKey(), $b->cacheKey());
    }

    public function test_with_unit_id_returns_new_instance_with_updated_unit(): void
    {
        $f = QualificationReportFilter::fromArray(['level' => 'masters']);
        $scoped = $f->withUnitId(7);
        $this->assertNull($f->unitId, 'Original instance should be unchanged');
        $this->assertSame(7, $scoped->unitId);
        $this->assertSame('masters', $scoped->level);
    }
}
