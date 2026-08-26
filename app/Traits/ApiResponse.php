<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success($data = null, array $message = null, int $status = 200)
    {
        $response = ['success' => true];
        if ($message) {
            $response['message'] = $message;
        }
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    protected function error(array $message, int $status = 422, $data = null)
    {
        $response = ['success' => false, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }
}
