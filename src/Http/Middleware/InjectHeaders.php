<?php

declare(strict_types=1);

namespace PayazaSdk\Http\Middleware;

use Closure;
use Illuminate\Http\Client\Request;

final class InjectHeaders
{
    public function __construct(
        private string $token,
        private string $tenant
    ) {}

    public function __invoke(Request $request, Closure $next)
    {
        $request->withHeaders([
            'Authorization' => "Payaza {$this->token}",
            'x-TenantID'    => $this->tenant,
            'User-Agent'    => 'payaza-sdk/1.0'
        ]);

        return $next($request);
    }
}