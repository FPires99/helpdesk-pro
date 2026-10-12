<?php

namespace Tests\Unit;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketRulesTest extends TestCase
{
    public function test_higher_priorities_have_shorter_target_times(): void
    {
        $hours = array_map(fn (TicketPriority $p) => $p->slaHours(), TicketPriority::cases());

        $this->assertSame([72, 24, 8, 2], $hours);
    }

    public function test_only_open_in_progress_and_waiting_count_as_active(): void
    {
        $active = array_filter(TicketStatus::cases(), fn (TicketStatus $s) => $s->isActive());

        $this->assertSame([TicketStatus::Open, TicketStatus::InProgress, TicketStatus::Waiting], array_values($active));
        $this->assertSame(TicketStatus::active(), array_values($active));
    }

    public function test_every_status_and_priority_has_a_label_and_badge(): void
    {
        foreach ([...TicketStatus::cases(), ...TicketPriority::cases()] as $case) {
            $this->assertNotSame('', $case->label());
            $this->assertStringContainsString('ring-', $case->badge());
        }
    }
}
