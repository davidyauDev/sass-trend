<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\Users\UserInvitationNotification;
use Illuminate\Support\Facades\Password;
use Throwable;

final class SendUserInvitationAction
{
    public function handle(User $user): void
    {
        $token = Password::broker()->createToken($user);
        $url = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        try {
            $user->notify(new UserInvitationNotification($url));
        } catch (Throwable) {
            // El flujo queda preparado aunque el envío real no esté configurado.
        }
    }
}
