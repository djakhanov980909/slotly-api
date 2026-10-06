<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Это время уже занято или недоступно. Выберите другое.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
