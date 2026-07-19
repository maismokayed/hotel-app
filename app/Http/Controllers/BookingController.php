<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Coupon;
use App\Models\WalletTransaction;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Enums\RoomStatus;
use Exception;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::with(['hotel', 'rooms', 'user']);

        if (! $user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('hotel', function ($q2) use ($user) {
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

        $booking->load(['hotel', 'rooms', 'user']);

        $isOwner   = $booking->user_id === $user->id;
        $isAdmin   = $user->hasRole('admin');
        $isManager = $booking->hotel?->user_id === $user->id;

        if (! $isOwner && ! $isAdmin && ! $isManager) {
            return response()->json(['message' => 'غير مصرح لك.'], 403);
        }

        return new BookingResource($booking);
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        $requestedRooms = collect($data['rooms'])
            ->groupBy('type')
            ->map(fn($group) => $group->sum('quantity'));

        try {
            $booking = DB::transaction(function () use ($data, $request, $requestedRooms) {

                $hotel = Hotel::where('id', $data['hotel_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $hotel->is_active) {
                    throw new Exception('This hotel is currently unavailable.');
                }

                $selectedRoomIds = [];

                foreach ($requestedRooms as $type => $quantity) {
                    $availableRooms = Room::where('hotel_id', $hotel->id)
                        ->where('type', $type)
                        ->where('status', RoomStatus::AVAILABLE->value)
                        ->whereDoesntHave('bookings', function ($q) use ($data) {
                            $q->where('status', '!=', 'cancelled')
                                ->where('check_in_date', '<', $data['check_out_date'])
                                ->where('check_out_date', '>', $data['check_in_date']);
                        })
                        ->lockForUpdate()
                        ->limit($quantity)
                        ->get();

                    if ($availableRooms->count() < $quantity) {
                        throw new Exception('Not enough available rooms.');
                    }

                    foreach ($availableRooms as $room) {
                        $selectedRoomIds[] = $room;
                    }
                }

                $checkIn  = now()->parse($data['check_in_date']);
                $checkOut = now()->parse($data['check_out_date']);
                $nights   = $checkIn->diffInDays($checkOut);

                $totalPrice = collect($selectedRoomIds)
                    ->sum(fn($room) => $nights * $room->price_per_night);

                $discountAmount = 0;
                $couponId = null;
                $coupon = null;

                if (! empty($data['coupon_code'])) {
                    $coupon = Coupon::where('code', $data['coupon_code'])
                        ->lockForUpdate()
                        ->first();

                    if (! $coupon || ! $coupon->isValid()) {
                        throw new Exception('الكوبون غير صالح أو منتهي الصلاحية.');
                    }

                    $discountAmount = $coupon->discount_type === 'percentage'
                        ? $totalPrice * ($coupon->discount_value / 100)
                        : $coupon->discount_value;

                    $couponId = $coupon->id;
                }

                $finalPrice = max(0, $totalPrice - $discountAmount);

                if ($data['payment_method'] === 'wallet') {
                    $wallet = $request->user()->wallet;

                    if (! $wallet || $wallet->balance < $finalPrice) {
                        throw new Exception('رصيد المحفظة غير كافٍ لإتمام الحجز.');
                    }

                    $wallet->decrement('balance', $finalPrice);

                    WalletTransaction::create([
                        'wallet_id'         => $wallet->id,
                        'user_id'           => $request->user()->id,
                        'amount'            => $finalPrice,
                        'transaction_type'  => 'debit',
                        'transaction_date'  => now(),
                    ]);
                }

                $booking = Booking::create([
                    'user_id'          => $request->user()->id,
                    'hotel_id'         => $hotel->id,
                    'coupon_id'        => $couponId,
                    'check_in_date'    => $data['check_in_date'],
                    'check_out_date'   => $data['check_out_date'],
                    'status'           => 'pending',
                    'total_price'      => $totalPrice,
                    'discount_amount'  => $discountAmount,
                    'final_price'      => $finalPrice,
                    'number_of_guests' => $data['number_of_guests'],
                    'payment_method'   => $data['payment_method'],
                ]);

                $booking->rooms()->attach(collect($selectedRoomIds)->pluck('id'));

                if ($coupon) {
                    $coupon->increment('used_count');
                }

                return $booking;
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return (new BookingResource(
            $booking->load(['hotel', 'rooms', 'user'])
        ))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $booking->update($request->validated());
        return new BookingResource($booking->load(['hotel', 'rooms', 'user']));
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
