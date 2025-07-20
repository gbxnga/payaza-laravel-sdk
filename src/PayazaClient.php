<?php

declare(strict_types=1);

namespace PayazaSdk;

use Illuminate\Http\Client\Factory as HttpFactory;
use PayazaSdk\Contracts\{PayazaClientContract, Resources};
use PayazaSdk\Http\Middleware\InjectHeaders;

final class PayazaClient implements PayazaClientContract
{
    private readonly HttpFactory $http;

    public function __construct(
        private readonly string $token,
        private readonly Enums\Environment $env,
        ?HttpFactory $http = null
    ) {
        $this->http = $http?->timeout(config('payaza.timeout'))
            ?: (new HttpFactory())->timeout(config('payaza.timeout'));

        $this->http->withMiddleware(new InjectHeaders(
            token:   $this->token,
            tenant:  $this->env->value
        ));
    }

    public function cards(): Resources\CardsContract
    {
        return new Resources\Cards($this->http, $this->env);
    }

    public function payouts(): Resources\PayoutsContract
    {
        return new Resources\Payouts($this->http, $this->env);
    }

    public function accounts(): Resources\AccountsContract
    {
        return new Resources\Accounts($this->http, $this->env);
    }
}