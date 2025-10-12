<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Broadcast::channel('user.{uniqueUserId}', function ($user, $uniqueUserId) {
//     return $user->unique_user_id == $uniqueUserId;
// });

// Broadcast::channel('user.{uniqueUserId}', function ($user, $uniqueUserId) {
//     \Log::info('Broadcast auth user:', ['user' => $user]);
//     return true;
// });

Broadcast::channel('providerRegisteredNotification.{uniqueUserId}', function ($user, $uniqueUserId) {
    \Log::info('Broadcast auth user:', ['user' => $user ? $user->id : null, 'uniqueUserId' => $uniqueUserId]);
    return $user && $user->unique_user_id == $uniqueUserId;
});

// Broadcast::channel('public-channel', function () {
//     return true; // anyone can listen, no auth needed
// });


Broadcast::channel('public-channel', function () {
    return true; // Public channel — no auth required
});

Broadcast::channel('samplePrivateNotification.{uniqueUserId}', function ($user, $uniqueUserId) {
    // Only allow if authenticated user unique_user_id matches the channel param
    return $user->unique_user_id == $uniqueUserId;
});

Broadcast::channel('appointmentNotification.{uniqueUserId}', function ($user, $uniqueUserId) {
    \Log::info('Broadcast auth user:', ['user' => $user ? $user->id : null, 'uniqueUserId' => $uniqueUserId]);
    return $user && $user->unique_user_id == $uniqueUserId;
});

Broadcast::channel('commmonNotification.{uniqueUserId}', function ($user, $uniqueUserId) {
    \Log::info('Broadcast auth user:', ['user' => $user ? $user->id : null, 'uniqueUserId' => $uniqueUserId]);
    return $user && $user->unique_user_id == $uniqueUserId;
});