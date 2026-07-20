<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string|null $roleTitle Nhãn role chèn vào tiêu đề/nội dung mail (vd "giáo viên").
     *                               Null => wording chung "Kích hoạt tài khoản".
     */
    public function __construct(
        public readonly User $user,
        public readonly string $token,
        public readonly ?string $roleTitle = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = 'Kích hoạt tài khoản' . ($this->roleTitle !== null ? ' ' . $this->roleTitle : '');

        return new Envelope(
            subject: $subject . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-invitation',
            with: [
                'userName' => $this->user->name,
                'roleTitle' => $this->roleTitle,
                'activationUrl' => route('account.activate.show', [
                    'token' => $this->token,
                    'email' => $this->user->email,
                ]),
                'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
            ],
        );
    }
}
