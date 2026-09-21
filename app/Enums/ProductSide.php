<?php

namespace App\Enums;

enum ProductSide: string
{
    case Front = 'front';
    case Back = 'back';
    case LeftSleeve = 'left_sleeve';
    case RightSleeve = 'right_sleeve';
}
