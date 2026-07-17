<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = $request->user();

        $query = Booking::with(['room.hotel', 'user']);

        if (! $user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('room.hotel', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            });
        }

        $bookings = $query->latest()->get();

        return BookingResource::collection($bookings);
    }

    public function show(Booking $booking, Request $request)
    {
        $user = $request->user();

        $isOwner   = $booking->user_id === $user->id;
        $isAdmin   = $user->hasRole('admin');
        $isManager = $booking->room?->hotel?->user_id === $user->id;

        if (! $isOwner && ! $isAdmin && ! $isManager) {
            return response()->json(['message' => 'غير مصرح لك.'], 403);
        }

        return new BookingResource($booking->load(['room.hotel', 'user']));
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $request) {


            $room = Room::where('id', $data['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$room->hotel || !$room->hotel->is_active) {
                return response()->json([
                    'message' => 'This hotel is currently unavailable.',
                ], 422);
            }


            $isAvailable = !Booking::where('room_id', $room->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($data) {
                    $query->where('check_in_date', '<', $data['check_out_date'])
                        ->where('check_out_date', '>', $data['check_in_date']);
                })
                ->lockForUpdate()
                ->exists();


            if (!$isAvailable) {
                return response()->json([
                    'message' => 'الغرفة غير متاحة في هذه الفترة.'
                ], 422);
            }


            $checkIn = now()->parse($data['check_in_date']);
            $checkOut = now()->parse($data['check_out_date']);

            $nights = $checkIn->diffInDays($checkOut);
            $totalPrice = $nights * $room->price_per_night;


            $discountAmount = 0;
            $couponId = null;
            $coupon = null;


            if (!empty($data['coupon_code'])) {

                $coupon = Coupon::where('code', $data['coupon_code'])
                    ->lockForUpdate()
                    ->first();

                if (!$coupon || !$coupon->isValid()) {
                    return response()->json([
                        'message' => 'الكوبون غير صالح أو منتهي الصلاحية.'
                    ], 422);
                }


                if ($coupon->discount_type === 'percentage') {
                    $discountAmount = $totalPrice * ($coupon->discount_value / 100);
                } else {
                    $discountAmount = $coupon->discount_value;
                }

                $couponId = $coupon->id;
            }


            $finalPrice = max(0, $totalPrice - $discountAmount);


            if ($data['payment_method'] === 'wallet') {

                $wallet = $request->user()->wallet;

                if (!$wallet || $wallet->balance < $finalPrice) {
                    return response()->json([
                        'message' => 'رصيد المحفظة غير كافٍ لإتمام الحجز.'
                    ], 422);
                }


                $wallet->decrement('balance', $finalPrice);


                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $request->user()->id,
                    'amount' => $finalPrice,
                    'transaction_type' => 'debit',
                    'transaction_date' => now(),
                ]);
            }


            $booking = Booking::create([
                ...$data,
                'user_id' => $request->user()->id,
                'room_id' => $room->id,
                'coupon_id' => $couponId,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
            ]);



            // زيادة استخدام الكوبون فقط بعد نجاح إنشاء الحجز
            if ($coupon) {
                $coupon->increment('used_count');
            }


            return (new BookingResource(
                $booking->load(['room.hotel', 'user'])
            ))
                ->response()
                ->setStatusCode(201);
        });
    }
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $booking->update($request->validated());
        return new BookingResource($booking->load(['room.hotel', 'user']));
    }

    public function cancel(Booking $booking, Request $request)
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json(['message' => 'غير مصرح لك بهذا الإجراء.'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز.'], 422);
        }

        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'تم إلغاء الحجز بنجاح.']);
    }
}
