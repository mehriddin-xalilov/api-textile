<?php

namespace App\Enums;

enum DesignStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Archived = 'archived';
}
