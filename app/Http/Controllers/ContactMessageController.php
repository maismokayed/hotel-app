<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $contactMessage = ContactMessage::create($request->validated());

        return response()->json([
            'message' => 'تم إرسال رسالتك بنجاح',
            'data' => $contactMessage,
        ], 201);
    }
}
