<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| Register the channels for your application.
| @param User $user
| @param int $id
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    $verify = $user->id === $id;
    return $verify;
});


Broadcast::channel('upload', function (User $user) {
    $verify = $user->can('create own EGI');
    Log::channel('upload')->info('Route: Channels. Channels: upload. Action: $verify: ' . $verify);
    // Verifica se l'utente ha i permessi per ascoltare il canale
    return $verify;
});

Broadcast::channel('preUpload', function (User $user) {
    $verify = $user->can('create own EGI');
    Log::channel('upload')->info('Route: Channels. Channel: preUpload. Action: $verify: ' . $verify);
    // Verifica se l'utente ha i permessi per ascoltare il canale
    return $verify;
});
