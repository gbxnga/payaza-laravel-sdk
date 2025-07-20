<?php

declare(strict_types=1);

namespace PayazaSdk;

use Illuminate\Http\Client\Factory as HttpFactory;
use PayazaSdk\Contracts\PayazaClientContract;
use PayazaSdk\Contracts\Resources;
use PayazaSdk\Resources as ResourceClasses;
use PayazaSdk\Http\Middleware\InjectHeaders;

final class PayazaClient implements PayazaClientContract
{
    private readonly HttpFactory $http;

    public function __construct(
        private readonly string $token,
        private readonly Enums\Environment $env,
        ?HttpFactory $http = null
    ) {
        $this->http = $http ?: app(HttpFactory::class);

        $this->http->globalOptions([
            'timeout' => config('payaza.timeout', 24)
        ]);

        $this->http->withMiddleware(new InjectHeaders(
            token:   $this->token,
            tenant:  $this->env->value
        ));
    }

    public function cards(): Resources\CardsContract
    {
        return new ResourceClasses\Cards($this->http, $this->env);
    }

    public function payouts(): Resources\PayoutsContract
    {
        return new ResourceClasses\Payouts($this->http, $this->env);
    }

    public function accounts(): Resources\AccountsContract
    {
        return new ResourceClasses\Accounts($this->http, $this->env);
    }
}