<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('notificaciones.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('sucursal.{branchId}', function ($user, $branchId) {
    return $user->is_super_admin || (string) $user->branch_id === (string) $branchId;
});
