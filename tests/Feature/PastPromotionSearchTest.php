<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The Past promotions list counts the promotions of active staff by rank and
 * year. Its search box matches a rank name or a year, and whatever is typed
 * there must reach the database as a value, never as part of the query.
 */
class PastPromotionSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    private Job $auditor;

    private Job $clerk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('view all past promotions');

        $this->auditor = Job::factory()->create(['name' => 'Principal Auditor']);
        $this->clerk = Job::factory()->create(['name' => 'Records Clerk']);

        $this->promote($this->staffWithStatus(EmployeeStatusEnum::Active), $this->auditor, '2020-03-01');
        $this->promote($this->staffWithStatus(EmployeeStatusEnum::Active), $this->clerk, '2021-03-01');
    }

    public function test_list_without_a_search_counts_promotions_by_rank_and_year(): void
    {
        $this->assertSame(
            ['Records Clerk 2021' => 1, 'Principal Auditor 2020' => 1],
            $this->promotionsListed($this->search(null)),
        );
    }

    public function test_search_by_year_lists_only_that_year(): void
    {
        $this->assertSame(
            ['Principal Auditor 2020' => 1],
            $this->promotionsListed($this->search('2020')),
        );
    }

    public function test_search_by_rank_name_lists_only_that_rank(): void
    {
        $this->assertSame(
            ['Records Clerk 2021' => 1],
            $this->promotionsListed($this->search('Records Clerk')),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function searchesContainingQuotes(): array
    {
        return [
            'a surname with an apostrophe' => ["O'Brien"],
            'a single quote on its own' => ["'"],
            'a year followed by a single quote' => ["2020'"],
            'a backslash before a single quote' => ["\\'"],
            'a double quote' => ['"'],
        ];
    }

    #[DataProvider('searchesContainingQuotes')]
    public function test_search_containing_a_quote_renders_the_page(string $search): void
    {
        $response = $this->search($search)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Promotion/Index')
                ->where('filters.search', $search));

        $this->assertSame([], $this->promotionsListed($response));
    }

    public function test_search_cannot_break_out_of_the_year_comparison_to_list_everything(): void
    {
        $this->assertSame(
            [],
            $this->promotionsListed($this->search("2020%' OR '%'='")),
        );
    }

    public function test_search_by_year_does_not_count_staff_who_are_no_longer_active(): void
    {
        $retired = $this->staffWithStatus(EmployeeStatusEnum::Retired);
        $this->promote($retired, $this->auditor, '2020-03-01');
        $this->promote($retired, $this->clerk, '2019-03-01');

        $this->assertSame(
            ['Records Clerk 2021' => 1, 'Principal Auditor 2020' => 1],
            $this->promotionsListed($this->search(null)),
        );
        $this->assertSame(
            ['Principal Auditor 2020' => 1],
            $this->promotionsListed($this->search('2020')),
        );
        $this->assertSame([], $this->promotionsListed($this->search('2019')));
    }

    private function search(?string $search): TestResponse
    {
        return $this->actingAs($this->viewer)
            ->get(route('promotion.index', ['search' => $search]));
    }

    /**
     * The rows the page was given, in the order it shows them.
     *
     * @return array<string, int> promotions counted, keyed by rank name and year
     */
    private function promotionsListed(TestResponse $response): array
    {
        $response->assertOk();

        return collect($response->inertiaPage()['props']['promotions']['data'])
            ->mapWithKeys(fn (array $row) => [
                "{$row['job_name']} {$row['year']}" => $row['april'] + $row['october'],
            ])
            ->all();
    }

    private function staffWithStatus(EmployeeStatusEnum $status): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => $status,
            'start_date' => now()->subYears(10),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }

    private function promote(InstitutionPerson $staff, Job $rank, string $date): void
    {
        $staff->ranks()->attach($rank->id, ['start_date' => $date]);
    }
}
