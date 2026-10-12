<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Knowledge base articles for common IT problems.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authors = User::staff()->get();
        $category = fn (string $slug) => Category::where('slug', $slug)->value('id');

        $articles = [
            ['access', 'Reset your password or unlock your account', <<<'MD'
If Windows says your account is **locked** or your password has **expired**, you can usually fix it yourself in a couple of minutes.

## Reset your password
1. On another device, go to the self-service password reset page.
2. Enter your work email and complete the verification (Authenticator app or text message).
3. Choose a new password: at least 12 characters, not used before.
4. Lock your laptop (**Windows + L**) and sign in with the new password while connected to the office network or VPN.

## After changing your password
Update it everywhere it is saved, or your account may lock again:
- **Outlook and Teams on your phone**: they will ask you to sign in again
- **Wi-Fi** on your phone, if you use your work account for it
- Any **mapped drives** that show a red X

> Still locked out after 15 minutes? Raise a ticket and choose *Accounts & Access*.
MD],
            ['email', 'Outlook keeps asking for your password', <<<'MD'
A repeating password prompt usually means Outlook is holding on to an **old saved password**.

1. Close Outlook completely.
2. Open **Credential Manager** from the Start menu and select **Windows Credentials**.
3. Remove every entry that mentions *MicrosoftOffice*, *Outlook* or *msteams*.
4. Restart your computer and open Outlook. Sign in once when asked.

If the prompt keeps coming back, check that your laptop's **date and time** are correct (Settings → Time & language), then raise a ticket.
MD],
            ['network', 'Connect to the VPN when working from home', <<<'MD'
You need the VPN to reach shared drives and internal systems from outside the office.

1. Connect to your home Wi-Fi first and check that websites load.
2. Open **GlobalProtect** from the system tray (bottom-right, near the clock).
3. Click **Connect** and approve the request in the Authenticator app.

## If it says "connection timed out"
- Restart your home router, then try again.
- Try a different network, such as a phone hotspot. If that works, your home router is blocking the VPN.
- Switch to the **Backup (TCP)** gateway in GlobalProtect settings.

Raise a ticket with a screenshot of the error if none of these work.
MD],
            ['printers', 'Add an office printer to your laptop', <<<'MD'
1. Make sure you are in the office or connected to the VPN.
2. Open **Settings → Bluetooth & devices → Printers & scanners**.
3. Click **Add device** and wait for the list to load.
4. Choose the printer for your floor, e.g. *PRN-2F-FINANCE*.

## Printer shows "offline"
- Check the printer's screen for paper jams or empty trays.
- Remove the printer from your laptop and add it again.
- If several people are affected, raise a ticket so we can check the printer itself.
MD],
            ['email', 'How to spot and report a phishing email', <<<'MD'
Phishing emails try to trick you into clicking a link or sharing your password.

## Warning signs
- Urgent language: *"Your account will be closed today"*
- A sender address that doesn't match the company it claims to be from
- Links that show a strange address when you hover over them
- Unexpected attachments, especially *.zip*, *.html* or Office files asking to *enable content*

## What to do
1. **Don't click** any links or open attachments.
2. Use the **Report → Phishing** button in Outlook.
3. If you already clicked or entered your password, **change your password now** and raise an *Urgent* ticket.
MD],
            ['access', 'Set up the Authenticator app on a new phone', <<<'MD'
Multi-factor authentication (MFA) is linked to your phone. When you change phones, you need to move it.

**Before you reset your old phone:**
1. Sign in to your account security page from a computer.
2. Choose **Add sign-in method → Authenticator app**.
3. Install Microsoft Authenticator on the new phone and scan the QR code.
4. Approve the test notification, then remove the old phone from the list.

Already reset your old phone and can't sign in? Raise an *Urgent* ticket. IT will verify your identity by phone and reset your MFA.
MD],
            ['software', 'No sound or microphone in Teams calls', <<<'MD'
1. In Teams, click your profile picture → **Settings → Devices**.
2. Under **Speaker** and **Microphone**, choose your headset or laptop audio, not a device you've unplugged.
3. Click **Make a test call** to check both.

## Still nothing?
- Check the mute switch on your headset cable.
- Unplug the headset, wait 10 seconds and plug it back in.
- Restart Teams: right-click the icon in the system tray → **Quit**, then open it again.
MD],
            ['software', 'Share a OneDrive folder with someone outside the company', <<<'MD'
Use OneDrive sharing instead of emailing attachments, so you can stop access later.

1. Right-click the folder in OneDrive and choose **Share**.
2. Click **Settings** (the cog) and choose **Specific people**.
3. Untick **Allow editing** unless they need to change files.
4. Set an **expiration date**.
5. Enter their email address and click **Send**.

They will receive a code by email to open the folder. Never use *Anyone with the link* for client or personal data.
MD],
        ];

        foreach ($articles as $i => [$slug, $title, $body]) {
            $article = Article::create([
                'category_id' => $category($slug),
                'user_id' => $authors[$i % $authors->count()]->id,
                'title' => $title,
                'body' => $body,
                'is_published' => true,
            ]);
            $article->forceFill(['views' => mt_rand(15, 140), 'helpful_count' => mt_rand(2, 30)])->saveQuietly();
        }
    }
}
