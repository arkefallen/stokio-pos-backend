<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Validate that the request contains valid JSON.
     * Prevents "Empty Payload" issues caused by syntax errors (e.g. trailing commas).
     */
    protected function ensureValidJson(\Illuminate\Http\Request $request)
    {
        $content = $request->getContent();

        if (!empty($content)) {
            // Check for JSON syntax errors
            json_decode($content);

            if (json_last_error() !== JSON_ERROR_NONE) {
                abort(response()->json([
                    'message' => 'Invalid JSON format. Please check for trailing commas or syntax errors.',
                ], 400));
            }
        }
    }

    /**
     * Ensure that the data array is not empty.
     * Useful for UPDATE requests where all fields are optional.
     */
    protected function ensureDataNotEmpty(array $data)
    {
        if (empty($data)) {
            abort(response()->json([
                'message' => 'No valid data provided for update.',
            ], 422));
        }
    }
}
