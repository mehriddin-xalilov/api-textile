<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Payme = 'payme';
    case Click = 'click';
    case Uzum = 'uzum';
}
