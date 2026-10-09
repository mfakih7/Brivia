<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Closed = 'closed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InReview => 'In review',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Closed => 'Closed',
            self::Spam => 'Spam',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::InReview, self::Contacted, self::Spam],
            self::InReview => [self::Contacted, self::Qualified, self::Closed, self::Spam],
            self::Contacted => [self::Qualified, self::Closed, self::Spam],
            self::Qualified => [self::Closed],
            self::Closed, self::Spam => [self::InReview],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Closed, self::Spam], true);
    }
}
