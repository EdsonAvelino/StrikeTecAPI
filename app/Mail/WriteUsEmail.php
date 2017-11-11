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
     * Email.
     *
     * @var email
     */
    public $email;

    /**
     * Subject.
     *
     * @var sub
     */
    public $sub;

    /**
     * Message.
     *
     * @var msg
     */
    public $msg;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $subject, $message)
    {
        $this->email = $email;
        $this->sub = $subject;
        $this->msg = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.write_us')
                        ->subject('Write Us: Query')
                        ->with(['email' => $this->email, 'sub' => $this->sub, 'msg' => $this->msg]);
    }

}
