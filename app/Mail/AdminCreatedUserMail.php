<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminCreatedUserMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $email,
        public string $temporaryPassword,
        public string $resetUrl,
        public string $role,
    ) {
    }

    public function build(): self {
        return $this
            ->subject('Acceso a tu cuenta')
            ->view('emails.admin-created-user');
    }

}
