<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Coupon;
use App\Models\WalletTransaction;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['room', 'user'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return BookingResource::collection($bookings);
    }

    public function show(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
    return response()->json(['message' => 'غير مصرح لك.'], 403);
}
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $room = Room::findOrFail($data['room_id']);


        $isAvailable = !Booking::where('room_id', $room->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($data) {
               $query->where('check_in_date', '<', $data['check_out_date'])
      ->where('check_out_date', '>', $data['check_in_date']);
            })
            ->exists();
        if (!$isAvailable) {
            return response()->json(['message' => 'الغرفة غير متاحة في هذه الفترة.'], 422);
        }

      
        $checkIn  = now()->parse($data['check_in_date']);
        $checkOut = now()->parse($data['check_out_date']);
        $nights   = $checkIn->diffInDays($checkOut);
        $totalPrice = $nights * $room->price_per_night;

        $discountAmount = 0;
        $couponId = null;

        if (!empty($data['coupon_id'])) {
            $coupon = Coupon::find($data['coupon_id']);

            if (!$coupon || !$coupon->isValid()) {
                return response()->json(['message' => 'الكوبون غير صالح أو منتهي الصلاحية.'], 422);
            }

            if ($coupon->discount_type === 'percentage') {
                $discountAmount = $totalPrice * ($coupon->discount_value / 100);
            } else {
                $discountAmount = $coupon->discount_value;
            }

            $coupon->increment('used_count');
            $couponId = $coupon->id;
        }

        $finalPrice = max(0, $totalPrice - $discountAmount);

        $paymentMethod = $data['payment_method'];

if ($paymentMethod === 'wallet') {
    $wallet = $request->user()->wallet;

    if (!$wallet || $wallet->balance < $finalPrice) {
        return response()->json([
            'message' => 'رصيد المحفظة غير كافٍ لإتمام الحجز.',
        ], 422);
    }

    $wallet->decrement('balance', $finalPrice);

    WalletTransaction::create([
        'wallet_id'        => $wallet->id,
        'user_id'          => $request->user()->id,
        'amount'           => $finalPrice,
        'transaction_type' => 'debit',
        'transaction_date' => now(),
    ]);
}

       $booking = Booking::create([
    ...$data,
    'user_id'         => $request->user()->id,
    'room_id'         => $room->id,
    'coupon_id'       => $couponId,
    'status'          => 'pending',
    'total_price'     => $totalPrice,
    'discount_amount' => $discountAmount,
    'final_price'     => $finalPrice,
]);

        return (new BookingResource($booking->load(['room', 'user'])))->response()->setStatusCode(201);
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $booking->update($request->validated());
        return new BookingResource($booking->load(['room', 'user']));
    }

    public function cancel(Booking $booking, Request $request)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بهذا الإجراء.'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز.'], 422);
        }

        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'تم إلغاء الحجز بنجاح.']);
    }
}
