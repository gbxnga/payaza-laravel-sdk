<?php

declare(strict_types=1);

namespace PayazaSdk\Exceptions;

use Exception;

final class PayazaException extends Exception
{
    public function __construct(
        string $message = 'Payaza API error',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?array $responseData = null
    ) {
        // If we have response data, append it to the message for better debugging
        if ($this->responseData) {
            $jsonPayload = json_encode($this->responseData, JSON_PRETTY_PRINT);
            $message .= "\n\nAPI Response:\n" . $jsonPayload;
        }
        
        parent::__construct($message, $code, $previous);
    }
}