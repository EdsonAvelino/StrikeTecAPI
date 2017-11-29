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
    public $code;

    /**
     * Message.
     *
     * @var name
     */
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $code, $name)
    {

        $this->sub = $subject;
        $this->code = $code;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.password')
                        ->subject($this->sub)
                        ->with(['name' => $this->name, 'code' => $this->code]);
    }

}
