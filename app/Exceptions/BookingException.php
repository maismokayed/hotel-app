<?php

namespace App\Exceptions;

use Exception;

/**
 * A validation/business-rule error for the booking flow that carries both
 * an Arabic and an English message, so the API response can hand the
 * frontend both at once and let it show whichever matches the current
 * site language.
 */
class BookingException extends Exception
{
    public function __construct(
        public readonly string $ar,
        public readonly string $en,
    ) {
        parent::__construct($en);
    }

    public function messages(): array
    {
        return [
            'ar' => $this->ar,
            'en' => $this->en,
        ];
    }
}
