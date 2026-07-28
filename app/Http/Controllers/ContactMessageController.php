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
            'success' => true,
            'message' => [
                'ar' => 'تم إرسال رسالتك بنجاح',
                'en' => 'Your message has been sent successfully',
            ],
            'data' => $contactMessage,
        ], 201);
    }

    public function index()
    {
        $contactMessages = ContactMessage::latest()->paginate(15);

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم جلب رسائل التواصل بنجاح',
                'en' => 'Contact messages fetched successfully',
            ],
            'data' => $contactMessages,
        ], 200);
    }

    public function update(ContactMessage $contactMessage)
    {
        $contactMessage->is_resolved = true;
        $contactMessage->save();

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم تحديث حالة الرسالة بنجاح',
                'en' => 'Message status updated successfully',
            ],
            'data' => $contactMessage,
        ], 200);
    }
}
