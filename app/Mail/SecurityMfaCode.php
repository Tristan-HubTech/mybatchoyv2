<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityMfaCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $actionLabel = 'session recovery'
    ) {
    }

    public function build(): self
    {
        return $this->subject('MyBatchoy security verification code')
            ->view('emails.security-mfa-code');
    }
}

