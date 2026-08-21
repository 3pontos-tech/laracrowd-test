<?php

namespace Platform\Placements\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Platform\Placements\Models\Placement;

class PlacementStepFailedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Placement $placement,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'PlacementStepFailed');
    }

    public function content(): Content
    {
        return new Content(htmlString: sprintf(
            '<p>Placement %s is now %s.</p>',
            $this->placement->slug,
            $this->placement->status->value,
        ));
    }
}
