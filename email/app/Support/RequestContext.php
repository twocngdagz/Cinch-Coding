<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class RequestContext
{
    public static function getRequestId(): string
    {
        $requestId = request()->attributes->get('request_id');

        if (is_string($requestId) && $requestId !== '') {
            return $requestId;
        }

        return (string) Str::uuid();
    }
}
