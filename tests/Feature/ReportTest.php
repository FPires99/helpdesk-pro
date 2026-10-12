<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_staff_only(): void
    {
        $this->actingAs(User::factory()->create())->get('/reports')->assertForbidden();
        $this->actingAs(User::factory()->technician()->create())->get('/reports')->assertOk();
    }

    public function test_report_calculates_resolution_time_and_target(): void
    {
        $tech = User::factory()->technician()->create(['name' => 'Rita Costa']);
        // Urgent target is 2h: one resolved in 1h (on target), one in 4h (late).
        Ticket::factory()->create(['priority' => 'urgent', 'assignee_id' => $tech->id, 'status' => TicketStatus::Resolved, 'created_at' => now()->subHours(10), 'resolved_at' => now()->subHours(9)]);
        Ticket::factory()->create(['priority' => 'urgent', 'assignee_id' => $tech->id, 'status' => TicketStatus::Resolved, 'created_at' => now()->subHours(10), 'resolved_at' => now()->subHours(6)]);

        $this->actingAs($tech)->get('/reports')
            ->assertOk()
            ->assertSee('Rita Costa')
            ->assertSee('2.5h')
            ->assertSee('50%');
    }

    public function test_csv_export_respects_filters_and_is_staff_only(): void
    {
        Ticket::factory()->create(['subject' => 'Printer jam', 'priority' => 'high']);
        Ticket::factory()->create(['subject' => 'VPN down', 'priority' => 'low']);

        $this->actingAs(User::factory()->create())->get('/tickets/export')->assertForbidden();

        $csv = $this->actingAs(User::factory()->technician()->create())
            ->get('/tickets/export?priority=high')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Printer jam', $csv);
        $this->assertStringNotContainsString('VPN down', $csv);
    }

    public function test_csv_export_neutralises_formulas(): void
    {
        Ticket::factory()->create(['subject' => '=HYPERLINK("http://evil.test")']);

        $csv = $this->actingAs(User::factory()->technician()->create())->get('/tickets/export')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
