<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
}
