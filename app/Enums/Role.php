<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'employee';
    case Technician = 'technician';
    case Admin = 'admin';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
