<?php

function canUser($key)
{
    $user = auth()->user();

    if (!$user) return false;

    return $user->hasPermission($key);
}
