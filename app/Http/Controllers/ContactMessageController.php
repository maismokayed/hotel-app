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

    public function index()
    {
        $contactMessages = ContactMessage::latest()->paginate(15);

        return response()->json([
            'message' => 'تم جلب رسائل التواصل بنجاح',
            'data' => $contactMessages,
        ]);
    }

    public function update(ContactMessage $contactMessage)
    {
        $contactMessage->update([
            'is_resolved' => true,
        ]);

        return response()->json([
            'message' => 'تم تحديث حالة الرسالة بنجاح',
            'data' => $contactMessage,
        ]);
    }
}
