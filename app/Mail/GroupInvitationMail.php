<?php

namespace App\Mail;

use App\Models\Group;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Group      $group,
        public Invitation $invitation,
        public string     $inviterName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->inviterName} invited you to join {$this->group->name} on Spliqo");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.group-invitation', with: [
            'group'       => $this->group,
            'invitation'  => $this->invitation,
            'inviterName' => $this->inviterName,
            'acceptUrl'   => route('invite.accept', $this->invitation->token),
        ]);
    }
}
