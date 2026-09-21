<?php

namespace App\Enums;

/** Izoh holati: moderatsiyadan o'tgani saytda ko'rinadi. */
enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
