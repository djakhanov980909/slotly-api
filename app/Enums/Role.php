<?php

namespace App\Enums;

enum Role: string
{
    case Client = 'client';
    case Specialist = 'specialist';
    case Admin = 'admin';
}
