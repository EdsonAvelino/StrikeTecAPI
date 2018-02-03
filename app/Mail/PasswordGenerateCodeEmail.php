<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordGenerateCodeEmail extends Mailable
{

    use Queueable,
        SerializesModels;

    /**
     * Subject
     *
     * @var sub
     */
    public $sub;

    /**
     * Password code
     *
     * @var code
     */
    public $code;

    /**
     * \App\User
     *
     * @var user
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $user, $code)
    {
        $this->sub = $subject;
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.welcome')
                        ->subject($this->sub)
                        ->with(['user' => $this->user, 'code' => $this->code]);
    }
}