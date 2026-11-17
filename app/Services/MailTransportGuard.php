<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;

class MailTransportGuard
{
    public static function isSafeForOtp(): bool
    {
        $mailer = (string) config('mail.default', 'log');
        $transport = strtolower((string) config("mail.mailers.{$mailer}.transport", $mailer));

        if ($transport === '' || in_array($transport, ['log', 'failover'], true)) {
            return false;
        }

        if ($transport === 'array') {
            return Mail::getFacadeRoot() instanceof MailFake;
        }

        if (app()->isProduction() && $transport === 'smtp') {
            $host = strtolower(trim((string) config("mail.mailers.{$mailer}.host", '')));
            if (in_array($host, ['', 'mailpit', 'mailhog', 'localhost', '127.0.0.1'], true)) {
                return false;
            }
        }

        return true;
    }
}

