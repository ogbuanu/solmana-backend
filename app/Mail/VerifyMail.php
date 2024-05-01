<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class VerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data ;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {

        $this->data = $data;
    }

        public function envelope(): Envelope{

      $mail_from = config('mail.from.address');
      $name = config('mail.from.name');

        return new Envelope(
            from: new Address($mail_from,$name),
            subject: $this->data['subject'],
        );
    }

        public function content(): Content
    {
        return new Content(
            view: 'email.verify',
            with: $this->data,
        );
    }
}
