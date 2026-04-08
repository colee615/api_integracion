<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

trait FormatsIntegrationResponses
{
    protected function trans(string $key, array $replace = []): string
    {
        return __($key, $replace);
    }

    protected function validationErrorResponse(Validator $validator, string $code, string $message): JsonResponse
    {
        $errors = collect($validator->errors()->messages())
            ->map(function (array $messages, string $field): array {
                preg_match('/\.(\d+)\./', $field, $matches);

                $attribute = str_contains($field, '.')
                    ? substr($field, (int) strrpos($field, '.') + 1)
                    : $field;

                return [
                    'field' => $field,
                    'record_index' => isset($matches[1]) ? (int) $matches[1] : null,
                    'attribute' => $attribute,
                    'messages' => array_values($messages),
                ];
            })
            ->values();

        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'error_count' => $errors->count(),
            'errors' => $errors,
        ], 422);
    }

    protected function notFoundResponse(string $code, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
        ], 404);
    }
}
