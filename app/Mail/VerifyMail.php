<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data =[];

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {

        $this->data = $data;
    }

        /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //appName,domain,username,activateLink,appMail
        return $this->view('email.verify')->subject($this->data['subject']);
    }
}
