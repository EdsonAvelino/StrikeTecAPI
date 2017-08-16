<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetCodeEmail extends Mailable
{
    use Queueable, SerializesModels;

     /**
     * Customer instance.
     *
     * @var Customer
     */
    public $user;

    /**
     * 6 Digits Verification Code.
     *
     * @var Customer
     */
    public $code;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $code = '')
    {
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
        return $this->markdown('emails.reset_code')
                ->subject('Reset Code')
                ->with(['user' => $this->user, 'code' => $this->code]);
    }
}
