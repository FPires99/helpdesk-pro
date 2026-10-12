<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketNotification;
use App\Notifications\TicketReplied;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for a fictional company, "Northwind Logistics".
 * Every demo account uses the password "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42);
        fake()->seed(42);

        $password = Hash::make('password');
        $person = fn (string $name, string $email, Role $role, string $department) => User::create([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'department' => $department,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $admin = $person('Alex Morgan', 'admin@helpdesk.test', Role::Admin, 'IT');
        $technicians = collect([
            $person('Sam Patel', 'tech@helpdesk.test', Role::Technician, 'IT'),
            $person('Rita Costa', 'rita@helpdesk.test', Role::Technician, 'IT'),
        ]);
        $employees = collect([
            $person('Jamie Walker', 'employee@helpdesk.test', Role::Employee, 'Sales'),
            $person('Priya Shah', 'priya@helpdesk.test', Role::Employee, 'Finance'),
            $person('Tom Ellis', 'tom@helpdesk.test', Role::Employee, 'Operations'),
            $person('Sofia Ramos', 'sofia@helpdesk.test', Role::Employee, 'Marketing'),
            $person('Daniel Kim', 'daniel@helpdesk.test', Role::Employee, 'HR'),
            $person('Laura Bennett', 'laura@helpdesk.test', Role::Employee, 'Sales'),
            $person('Marco Silva', 'marco@helpdesk.test', Role::Employee, 'Operations'),
            $person('Hannah Price', 'hannah@helpdesk.test', Role::Employee, 'Finance'),
        ]);
        $staff = $technicians->push($admin);

        $categories = collect([
            ['Hardware', 'hardware', 'cpu'],
            ['Software', 'software', 'app'],
            ['Network & Wi-Fi', 'network', 'wifi'],
            ['Accounts & Access', 'access', 'key'],
            ['Email & Calendar', 'email', 'mail'],
            ['Printers', 'printers', 'printer'],
        ])->mapWithKeys(fn ($c) => [$c[1] => Category::create(['name' => $c[0], 'slug' => $c[1], 'icon' => $c[2]])]);

        // [category, priority, subject, description, technician reply]
        $scenarios = [
            ['access', 'high', 'Locked out of my account after password change', 'I changed my password this morning and now Windows says my account is locked. I can\'t log in to my laptop at all.', 'Your account was locked after several attempts with the old password saved on your phone. I\'ve unlocked it; please update the password in the Outlook app on your phone too.'],
            ['network', 'high', 'Wi-Fi keeps disconnecting in meeting room 2', 'The Wi-Fi drops every few minutes in meeting room 2, which is a problem during client video calls.', 'The access point in that room was on an old firmware version and overloaded. I\'ve updated it and moved it to a less crowded channel.'],
            ['printers', 'medium', 'Printer on 2nd floor shows "offline"', 'The HP printer next to Finance shows as offline on my laptop and nothing prints.', 'The printer had picked up a new IP address from DHCP. I\'ve given it a reserved address and re-added it on your laptop.'],
            ['email', 'medium', 'Outlook keeps asking for my password', 'Since yesterday Outlook pops up a password prompt every few minutes, even after I type it correctly.', 'Old saved credentials were conflicting with the new sign-in. I cleared them from Credential Manager and reset your Outlook profile.'],
            ['hardware', 'medium', 'Laptop battery drains very fast', 'My laptop goes from 100% to empty in about an hour, even when I\'m only using email.', 'The battery health report shows it is at 41% of its original capacity. A replacement battery is ordered; I\'ll swap it when it arrives.'],
            ['software', 'low', 'Request: install Adobe Acrobat Pro', 'I need to edit and sign PDFs for contracts. Could Adobe Acrobat Pro be installed on my laptop?', 'Approved by your manager. Acrobat Pro is installed and licensed to your account.'],
            ['access', 'medium', 'New starter needs accounts set up', 'We have a new team member starting on Monday who needs a laptop, email and access to the Sales shared drive.', 'Account created, added to the Sales group and the laptop is imaged. Login details will be handed over on Monday morning.'],
            ['network', 'urgent', 'VPN not connecting when working from home', 'The VPN client fails with "connection timed out" at home, so I can\'t reach any internal systems.', 'Your home router was blocking the VPN port. I switched your client to the backup TCP profile and it connects now.'],
            ['hardware', 'low', 'Second monitor not detected', 'My second monitor stays black when I plug in the docking station.', 'The docking station needed a firmware update and the display cable was loose. Both monitors are working now.'],
            ['software', 'medium', 'Excel crashes when opening large file', 'Excel freezes and closes whenever I open the monthly sales report (about 40 MB).', 'A faulty add-in was causing the crash. I disabled it and updated Office to the latest version.'],
            ['email', 'high', 'Suspicious email asking to "verify my account"', 'I received an email that looks like Microsoft asking me to verify my account through a link. I didn\'t click it.', 'Thanks for reporting it. It was a phishing email; we\'ve blocked the sender and removed it from every mailbox.'],
            ['printers', 'low', 'Scanner not sending to email', 'The scan-to-email button on the copier says "send failed".', 'The copier\'s mail relay password had expired. I updated the SMTP settings and test scans are arriving.'],
            ['access', 'low', 'Need access to the Marketing shared drive', 'I\'m helping on a campaign and need read access to the Marketing folder.', 'Read-only access granted via the Marketing-Read group. Sign out and back in to see the drive.'],
            ['hardware', 'high', 'Laptop won\'t turn on', 'My laptop doesn\'t turn on at all. The charging light doesn\'t come on either.', 'The power adapter was faulty. I\'ve replaced it and the laptop is working.'],
            ['software', 'medium', 'Teams has no sound in calls', 'I can see people in Teams calls but I can\'t hear anything.', 'Teams was set to output audio to a disconnected headset. Changed the default device and tested a call.'],
            ['network', 'medium', 'Very slow internet in warehouse office', 'Web pages take ages to load from the warehouse office PCs.', 'A faulty network switch port was running at 10 Mbps. Moved the uplink to a working port and speeds are back to normal.'],
            ['email', 'low', 'Set up out-of-office for holiday', 'I\'m going on holiday next week. How do I set up an automatic reply?', 'Sent you a short guide and set the automatic reply for your dates as requested.'],
            ['access', 'urgent', 'MFA not working on new phone', 'I got a new phone and the Authenticator app has no account, so I can\'t sign in to anything.', 'Reset your MFA registration after confirming your identity by phone. Re-registered the Authenticator app with you.'],
            ['hardware', 'medium', 'Keyboard keys sticking', 'Several keys on my laptop keyboard stick or type twice.', 'Swapped the laptop for a loan device while the keyboard is replaced under warranty.'],
            ['software', 'low', 'Request: update Chrome', 'Chrome says it\'s out of date and asks me to relaunch, but I don\'t have permission.', 'Updated Chrome and enabled automatic updates through policy for all company laptops.'],
            ['printers', 'medium', 'Printing comes out blank', 'Pages from the Sales printer come out completely blank.', 'The toner cartridge still had its protective seal. Removed it and printed a test page.'],
            ['network', 'low', 'Can\'t connect to guest Wi-Fi for visitor', 'A client visiting today can\'t connect to the guest Wi-Fi with the usual password.', 'The guest password rotates weekly. Sent the current password and added it to the reception notice.'],
            ['access', 'medium', 'Leaver: disable account for departing employee', 'An employee in Operations leaves on Friday. Please disable their access and forward their email to their manager.', null],
            ['hardware', 'urgent', 'Reception PC showing blue screen', 'The reception computer keeps crashing with a blue screen, so visitors can\'t sign in.', null],
            ['software', 'medium', 'Accounting software won\'t start after update', 'After last night\'s update the accounting software shows an error and closes.', null],
            ['email', 'medium', 'Shared mailbox not showing in Outlook', 'I was given access to the sales@ mailbox but it doesn\'t appear in Outlook.', null],
            ['printers', 'high', 'Label printer in warehouse jammed', 'The shipping label printer is jammed and we can\'t send today\'s orders.', null],
            ['network', 'high', 'No network on desk 14', 'The PC on desk 14 has no network connection. Other desks are fine.', null],
            ['hardware', 'low', 'Request: new headset', 'My headset microphone stopped working. Could I get a replacement?', null],
            ['software', 'low', 'How do I share a OneDrive folder externally?', 'I need to share a folder with an external accountant. What\'s the safe way to do this?', null],
            ['access', 'high', 'Can\'t access payroll system', 'I get "access denied" when opening the payroll system, which I need for month-end.', null],
            ['email', 'urgent', 'CEO\'s emails not being delivered', 'Emails from the CEO to an important client are bouncing back with a delivery error.', null],
        ];

        // Give the demo employee a realistic mix: one waiting on them, one in progress, some resolved.
        $demoEmployee = $employees->first();
        $forDemo = [
            'Shared mailbox not showing in Outlook' => TicketStatus::Waiting,
            'Request: new headset' => TicketStatus::InProgress,
            'Outlook keeps asking for my password' => null,
            'Second monitor not detected' => null,
            'Request: update Chrome' => null,
        ];

        foreach ($scenarios as [$category, $priority, $subject, $description, $reply]) {
            $isDemo = array_key_exists($subject, $forDemo);
            $requester = $isDemo ? $demoEmployee : $employees->slice(1)->random();
            $sla = TicketPriority::from($priority)->slaHours();

            if ($reply === null) {
                // Active tickets: mostly recent; roughly a third are past their target time.
                $status = $forDemo[$subject] ?? collect([TicketStatus::Open, TicketStatus::Open, TicketStatus::InProgress, TicketStatus::Waiting])->random();
                $createdAt = Carbon::now()->subMinutes(mt_rand(20, (int) ($sla * 60 * 1.4)));
            } else {
                // Finished tickets: spread over the last month, a few this week.
                $status = mt_rand(0, 3) === 0 ? TicketStatus::Closed : TicketStatus::Resolved;
                $createdAt = Carbon::now()->subDays(mt_rand(1, 28))->setTime(mt_rand(8, 17), mt_rand(0, 59));
            }

            $assignee = $status === TicketStatus::Open && mt_rand(0, 1) ? null : $staff->random();

            $ticket = new Ticket([
                'user_id' => $requester->id,
                'assignee_id' => $assignee?->id,
                'category_id' => $categories[$category]->id,
                'subject' => $subject,
                'description' => $description,
                'priority' => TicketPriority::from($priority),
                'status' => $status,
            ]);
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $createdAt;
            // History is written below with realistic timestamps, so skip the automatic events.
            Ticket::withoutEvents(fn () => $ticket->save());
            $this->event($ticket, $requester, TicketEvent::CREATED, null, null, $createdAt);

            $responder = $assignee ?? $staff->random();
            // First response 10-90 minutes after opening, but never in the future.
            $first = $createdAt->copy()->addMinutes(mt_rand(10, 90));
            if ($first->isFuture()) {
                $first = $createdAt->copy()->addSeconds((int) ($createdAt->diffInSeconds(now()) / 2));
            }

            if ($assignee) {
                $this->event($ticket, $assignee, TicketEvent::ASSIGNEE, null, (string) $assignee->id, $first);
            }
            if ($status !== TicketStatus::Open) {
                $this->event($ticket, $responder, TicketEvent::STATUS, 'open', 'in_progress', $first);
                $this->comment($ticket, $responder, 'Thanks, I\'m looking into this now.', $first);
            }
            if ($status === TicketStatus::Waiting) {
                $askedAt = $first->copy()->addSeconds((int) ($first->diffInSeconds(now()) / 2));
                $this->comment($ticket, $responder, 'Could you let me know when you\'re at your desk so I can connect remotely?', $askedAt);
                $this->event($ticket, $responder, TicketEvent::STATUS, 'in_progress', 'waiting', $askedAt);
            }
            if ($status === TicketStatus::InProgress) {
                $this->comment($ticket, $responder, 'Checked the event logs; narrowing down the cause.', $first->copy()->addSeconds((int) ($first->diffInSeconds(now()) / 2)), internal: true);
            }
            if ($reply !== null) {
                $resolvedAt = $first->copy()->addMinutes(mt_rand(30, (int) ($ticket->priority->slaHours() * 60 * 1.2)));
                $this->comment($ticket, $responder, $reply, $resolvedAt);
                $this->event($ticket, $responder, TicketEvent::STATUS, 'in_progress', 'resolved', $resolvedAt);
                $this->comment($ticket, $requester, 'Working now, thank you!', $resolvedAt->copy()->addMinutes(mt_rand(5, 60)));
                $ticket->forceFill(['resolved_at' => $resolvedAt, 'updated_at' => $resolvedAt])->saveQuietly();
                if ($status === TicketStatus::Closed) {
                    $this->event($ticket, $responder, TicketEvent::STATUS, 'resolved', 'closed', $resolvedAt->copy()->addHours(2));
                }
            }
        }

        $this->call(ArticleSeeder::class);

        // A screenshot of the error on one ticket, to show attachments.
        Storage::disk(Attachment::DISK)->deleteDirectory('attachments');
        $buggy = Ticket::where('subject', 'like', 'Accounting software%')->first();
        if ($buggy && function_exists('imagecreatetruecolor')) {
            $path = "attachments/{$buggy->id}/accounting-error.png";
            Storage::disk(Attachment::DISK)->put($path, $this->errorScreenshot());
            Attachment::create([
                'ticket_id' => $buggy->id,
                'user_id' => $buggy->user_id,
                'original_name' => 'accounting-error.png',
                'path' => $path,
                'mime_type' => 'image/png',
                'size' => Storage::disk(Attachment::DISK)->size($path),
            ]);
        }

        // A few unread notifications for the demo accounts.
        $tech = $technicians->first();
        foreach (Ticket::active()->latest()->take(3)->get() as $ticket) {
            $this->notify($tech, new TicketCreated($ticket), $ticket->created_at);
        }
        foreach (Ticket::where('assignee_id', $tech->id)->active()->get() as $ticket) {
            $this->notify($tech, new TicketAssigned($ticket, $admin), $ticket->created_at->copy()->addMinutes(5));
        }
        foreach ($demoEmployee->tickets()->with('comments.author')->get() as $ticket) {
            $reply = $ticket->comments->first(fn ($c) => $c->author->isStaff() && ! $c->is_internal);
            if ($reply) {
                $this->notify($demoEmployee, new TicketReplied($reply), $reply->created_at, read: ! $ticket->status->isActive());
            }
        }
    }

    /** Draws a simple Windows-style error dialog as a PNG. */
    private function errorScreenshot(): string
    {
        $img = imagecreatetruecolor(640, 260);
        $white = imagecolorallocate($img, 255, 255, 255);
        $grey = imagecolorallocate($img, 240, 240, 240);
        $border = imagecolorallocate($img, 200, 200, 200);
        $text = imagecolorallocate($img, 30, 30, 30);
        $red = imagecolorallocate($img, 220, 50, 50);
        $blue = imagecolorallocate($img, 0, 103, 192);

        imagefill($img, 0, 0, $white);
        imagefilledrectangle($img, 0, 0, 639, 34, $grey);
        imageline($img, 0, 34, 639, 34, $border);
        imagestring($img, 4, 14, 10, 'Accounting Suite', $text);
        imagefilledellipse($img, 60, 110, 46, 46, $red);
        imagestring($img, 5, 54, 102, 'X', $white);
        imagestring($img, 5, 100, 88, 'The application failed to start.', $text);
        imagestring($img, 3, 100, 112, 'Error 0xc000007b: a required component (MSVCP140.dll)', $text);
        imagestring($img, 3, 100, 128, 'could not be loaded after the latest update.', $text);
        imagefilledrectangle($img, 520, 200, 620, 232, $blue);
        imagestring($img, 4, 557, 208, 'OK', $white);
        imagerectangle($img, 0, 0, 639, 259, $border);

        ob_start();
        imagepng($img);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    /** Store an in-app notification directly (no email) so the demo bell has something in it. */
    private function notify(User $user, TicketNotification $notification, Carbon $at, bool $read = false): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $notification::class,
            'data' => $notification->toArray($user),
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function event(Ticket $ticket, User $actor, string $type, ?string $from, ?string $to, Carbon $at): void
    {
        $event = new TicketEvent(['user_id' => $actor->id, 'type' => $type, 'from' => $from, 'to' => $to]);
        $event->ticket_id = $ticket->id;
        $event->created_at = $at;
        $event->updated_at = $at;
        $event->save();
    }

    private function comment(Ticket $ticket, User $author, string $body, Carbon $at, bool $internal = false): void
    {
        $comment = new Comment([
            'user_id' => $author->id,
            'body' => $body,
            'is_internal' => $internal,
        ]);
        $comment->ticket_id = $ticket->id;
        $comment->created_at = $at;
        $comment->updated_at = $at;
        $comment->save();
    }
}
