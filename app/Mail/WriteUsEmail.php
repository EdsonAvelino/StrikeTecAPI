<?php

namespace App\Mail;

use App\WriteUs;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class WriteUsEmail extends Mailable
{

    use Queueable,
        SerializesModels;

    /**
     * Customer instance.
     *
     * @var Customer
     */
    public $email;

    /**
     * Customer instance.
     *
     * @var subject
     */
    public $subject;

    /**
     * Customer instance.
     *
     * @var message
     */
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $subject, $message)
    {
        //echo $email;die;
        $this->email = $email;
        $this->subject = $subject;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->markdown('emails.write_us')
                        ->subject('Write us : query')
                        ->with(['user' => $this->email, 'message' => $this->message, 'subject' => $this->subject]);
    }

}
