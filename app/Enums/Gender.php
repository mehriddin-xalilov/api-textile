<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Unisex = 'unisex';
    case Kids = 'kids';
}
