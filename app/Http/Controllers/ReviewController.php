<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Booking;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;

class ReviewController extends Controller
{
    public function index($hotelId)
    {
        $reviews = Review::with('user')
            ->where('hotel_id', $hotelId)
            ->latest()
            ->get();

        return ReviewResource::collection($reviews);
    }

    public function store(StoreReviewRequest $request)
    {
        $data = $request->validated();

        $booking = Booking::findOrFail($data['booking_id']);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بهذا الإجراء.'], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'يمكنك التقييم فقط بعد اكتمال الحجز.'], 422);
        }

        if ($booking->room->hotel_id !== $data['hotel_id']) {
            return response()->json(['message' => 'الحجز لا ينتمي لهذا الفندق.'], 422);
        }

        if (Review::where('booking_id', $data['booking_id'])->exists()) {
            return response()->json(['message' => 'لقد قمت بتقييم هذا الحجز مسبقاً.'], 422);
        }

        $review = Review::create([
            ...$data,
            'user_id'     => $request->user()->id,
            'review_date' => now()->toDateString(),
        ]);
        return (new ReviewResource($review->load('user')))->response()->setStatusCode(201);
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return response()->json(['message' => 'تم حذف التقييم بنجاح.']);
    }
}
