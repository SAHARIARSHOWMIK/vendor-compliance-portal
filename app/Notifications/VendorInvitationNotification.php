<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Vendor $vendor,
        public readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = url("/vendor-portal/accept-invitation/{$this->token}");

        return (new MailMessage)
            ->subject("You have been invited to the Vendor Compliance Portal")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been invited to submit compliance documents for **{$this->vendor->name}** on our Vendor Compliance Portal.")
            ->line("Please click the button below to accept the invitation and complete your vendor profile.")
            ->action('Accept Invitation & Get Started', $acceptUrl)
            ->line("This invitation link will remain valid until you accept it.")
            ->line("If you did not expect this invitation, you can safely ignore this email.")
            ->salutation("The Compliance Team");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id'   => $this->vendor->id,
            'vendor_name' => $this->vendor->name,
            'token'       => $this->token,
        ];
    }
}
