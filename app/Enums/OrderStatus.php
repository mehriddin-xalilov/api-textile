<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Printing = 'printing';
    case Sewing = 'sewing';
    case Ready = 'ready';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** Ruxsat etilgan o'tishlar: holat mashinasi. */
    public function transitions(): array
    {
        return match ($this) {
            self::New => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Printing, self::Sewing, self::Cancelled],
            self::Printing => [self::Sewing, self::Ready, self::Cancelled],
            self::Sewing => [self::Ready, self::Cancelled],
            self::Ready => [self::Shipped],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->transitions() === [];
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'Yangi',
            self::Confirmed => 'Tasdiqlangan',
            self::Printing => 'Bosilmoqda',
            self::Sewing => 'Tikilmoqda',
            self::Ready => 'Tayyor',
            self::Shipped => "Jo'natildi",
            self::Delivered => 'Yetkazildi',
            self::Cancelled => 'Bekor qilindi',
        };
    }
}
