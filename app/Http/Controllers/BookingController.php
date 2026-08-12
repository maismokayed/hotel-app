<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Coupon;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Enums\RoomStatus;
use App\Exceptions\BookingException;
use Exception;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $this->completeExpiredBookings();

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

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم جلب الحجوزات بنجاح',
                'en' => 'Bookings fetched successfully',
            ],
            'data' => BookingResource::collection($bookings),
        ], 200);
    }

    public function show(Booking $booking, Request $request)
    {
        $user = $request->user();

        $booking->load(['hotel', 'rooms', 'user']);

        $isOwner   = $booking->user_id === $user->id;
        $isAdmin   = $user->hasRole('admin');
        $isManager = $booking->hotel?->user_id === $user->id;

        if (! $isOwner && ! $isAdmin && ! $isManager) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'غير مصرح لك ببدء هذا الإجراء.',
                    'en' => 'You are not authorized to view this booking.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم جلب تفاصيل الحجز بنجاح',
                'en' => 'Booking details fetched successfully',
            ],
            'data' => new BookingResource($booking),
        ], 200);
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
                    throw new BookingException(
                        ar: 'هذا الفندق غير متاح حالياً.',
                        en: 'This hotel is currently unavailable.',
                    );
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
                        throw new BookingException(
                            ar: 'لا يوجد عدد كافٍ من الغرف المتاحة.',
                            en: 'Not enough available rooms.',
                        );
                    }

                    foreach ($availableRooms as $room) {
                        $selectedRoomIds[] = $room->id;
                    }
                }

                $checkIn  = now()->parse($data['check_in_date']);
                $checkOut = now()->parse($data['check_out_date']);
                $nights   = $checkIn->diffInDays($checkOut);

                $totalPrice = Room::whereIn('id', $selectedRoomIds)->get()
                    ->sum(fn($room) => $nights * $room->price_per_night);

                $discountAmount = 0;
                $couponId = null;
                $coupon = null;

                if (! empty($data['coupon_code'])) {
                    $coupon = Coupon::where('code', $data['coupon_code'])
                        ->lockForUpdate()
                        ->first();

                    if (! $coupon || ! $coupon->isValid()) {
                        throw new BookingException(
                            ar: 'الكوبون غير صالح أو منتهي الصلاحية.',
                            en: 'This coupon is invalid or has expired.',
                        );
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
                        throw new BookingException(
                            ar: 'رصيد المحفظة غير كافٍ لإتمام الحجز.',
                            en: 'Insufficient wallet balance to complete this booking.',
                        );
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

                $status = $data['payment_method'] === 'wallet' ? 'confirmed' : 'pending';

                $booking = Booking::create([
                    'user_id'          => $request->user()->id,
                    'hotel_id'         => $hotel->id,
                    'coupon_id'        => $couponId,
                    'guest_full_name'  => $data['guest_full_name'],
                    'guest_phone'      => $data['guest_phone'],
                    'check_in_date'    => $data['check_in_date'],
                    'check_out_date'   => $data['check_out_date'],
                    'status'           => $status,
                    'total_price'      => $totalPrice,
                    'discount_amount'  => $discountAmount,
                    'final_price'      => $finalPrice,
                    'number_of_guests' => $data['number_of_guests'],
                    'payment_method'   => $data['payment_method'],
                ]);

                $booking->rooms()->attach($selectedRoomIds);

                if ($coupon) {
                    $coupon->increment('used_count');
                }

                return $booking;
            });

            return response()->json([
                'success' => true,
                'message' => [
                    'ar' => 'تم إنشاء الحجز بنجاح',
                    'en' => 'Booking created successfully',
                ],
                'data' => new BookingResource($booking->load(['hotel', 'rooms', 'user'])),
            ], 201);
        } catch (BookingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->messages(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => $e->getMessage(),
                    'en' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $user = $request->user();

        if (! $user->hasRole('admin') && $booking->hotel?->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'غير مصرح لك بتعديل هذا الحجز.',
                    'en' => 'You are not authorized to update this booking.',
                ],
            ], 403);
        }

        $booking->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم تحديث الحجز بنجاح',
                'en' => 'Booking updated successfully',
            ],
            'data' => new BookingResource($booking->load(['hotel', 'rooms', 'user'])),
        ], 200);
    }

    public function cancel(Booking $booking, Request $request)
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && ! $user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'غير مصرح لك بهذا الإجراء.',
                    'en' => 'You are not authorized to perform this action.',
                ],
            ], 403);
        }

        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'لا يمكن إلغاء هذا الحجز.',
                    'en' => 'This booking cannot be cancelled.',
                ],
            ], 422);
        }

        $today = now()->startOfDay();
        $checkInDate = $booking->check_in_date->copy()->startOfDay();
        $checkOutDate = $booking->check_out_date->copy()->startOfDay();


        $daysUntilCheckIn = $today->diffInDays($checkInDate);
        if ($checkInDate->lt($today)) {
            $daysUntilCheckIn = -$daysUntilCheckIn;
        }

        $isLateCancellation = $daysUntilCheckIn <= 3;

        if (! $isLateCancellation) {
            try {
                DB::transaction(function () use ($booking) {
                    $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

                    if (! in_array($locked->status, ['pending', 'confirmed'])) {
                        throw new BookingException(
                            ar: 'تم إلغاء هذا الحجز مسبقاً.',
                            en: 'This booking has already been cancelled.',
                        );
                    }

                    if ($locked->payment_method === 'wallet') {
                        $owner  = $locked->user;
                        $wallet = Wallet::where('user_id', $owner->id)->lockForUpdate()->first();

                        if ($wallet) {
                            $wallet->increment('balance', $locked->final_price);

                            WalletTransaction::create([
                                'wallet_id'        => $wallet->id,
                                'user_id'          => $owner->id,
                                'amount'           => $locked->final_price,
                                'transaction_type' => 'credit',
                                'transaction_date' => now(),
                            ]);
                        }
                    }

                    if ($locked->coupon_id) {
                        Coupon::where('id', $locked->coupon_id)
                            ->where('used_count', '>', 0)
                            ->decrement('used_count');
                    }

                    $locked->update(['status' => 'cancelled']);
                });
            } catch (BookingException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->messages()
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => [
                    'ar' => 'تم إلغاء الحجز بنجاح، ولا يوجد أي خصم.',
                    'en' => 'Booking cancelled successfully, no fee applies.',
                ],
            ], 200);
        }

        $fee = round($booking->final_price * 0.40, 2);

        if (! $request->boolean('confirm')) {
            if ($booking->payment_method === 'wallet') {
                $refund = max(0, $booking->final_price - $fee);

                return response()->json([
                    'success' => true,
                    'requires_confirmation' => true,
                    'fee'                   => $fee,
                    'refund_amount'         => $refund,
                    'message' => [
                        'ar' => "الإلغاء بعد أقل من 3 أيام من موعد الوصول يترتب عليه غرامة قدرها {$fee}. سيتم استرجاع {$refund} إلى محفظتك. هل تريد المتابعة؟",
                        'en' => "Cancelling less than 3 days before check-in incurs a fee of {$fee}. {$refund} will be refunded to your wallet. Do you want to continue?",
                    ],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'requires_confirmation' => true,
                'fee'                   => $fee,
                'message' => [
                    'ar' => "الإلغاء بعد أقل من 3 أيام من موعد الوصول يترتب عليه خصم غرامة قدرها {$fee} من محفظتك. هل تريد المتابعة؟",
                    'en' => "Cancelling less than 3 days before check-in incurs a fee of {$fee}, which will be deducted from your wallet. Do you want to continue?",
                ],
            ], 200);
        }

        try {
            DB::transaction(function () use ($booking, $fee) {
                $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

                if (! in_array($locked->status, ['pending', 'confirmed'])) {
                    throw new BookingException(
                        ar: 'تم إلغاء هذا الحجز مسبقاً أو لم يعد قابلاً للإلغاء.',
                        en: 'This booking has already been cancelled or can no longer be cancelled.',
                    );
                }

                $owner  = $locked->user;
                $wallet = Wallet::where('user_id', $owner->id)->lockForUpdate()->first();

                if ($locked->payment_method === 'wallet') {
                    $refund = max(0, $locked->final_price - $fee);

                    $wallet->increment('balance', $refund);

                    WalletTransaction::create([
                        'wallet_id'        => $wallet->id,
                        'user_id'          => $owner->id,
                        'amount'           => $refund,
                        'transaction_type' => 'credit',
                        'transaction_date' => now(),
                    ]);
                } else {
                    if (! $wallet || $wallet->balance < $fee) {
                        throw new BookingException(
                            ar: 'لا يوجد رصيد كافٍ لتغطية غرامة الإلغاء. لم يتم إلغاء الحجز، يرجى تعبئة محفظتك أولاً.',
                            en: 'Insufficient wallet balance to cover the cancellation fee. The booking was not cancelled — please top up your wallet first.',
                        );
                    }

                    $wallet->decrement('balance', $fee);

                    WalletTransaction::create([
                        'wallet_id'        => $wallet->id,
                        'user_id'          => $owner->id,
                        'amount'           => $fee,
                        'transaction_type' => 'debit',
                        'transaction_date' => now(),
                    ]);
                }

                if ($locked->coupon_id) {
                    Coupon::where('id', $locked->coupon_id)
                        ->where('used_count', '>', 0)
                        ->decrement('used_count');
                }

                $locked->update(['status' => 'cancelled']);
            });
        } catch (BookingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->messages()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $booking->payment_method === 'wallet'
                ? [
                    'ar' => "تم إلغاء الحجز. تم خصم غرامة {$fee} واسترجاع الباقي إلى محفظتك.",
                    'en' => "Booking cancelled. A fee of {$fee} was deducted and the remainder was refunded to your wallet.",
                ]
                : [
                    'ar' => "تم إلغاء الحجز، وتم خصم {$fee} من محفظتك كغرامة إلغاء متأخر.",
                    'en' => "Booking cancelled, and {$fee} was deducted from your wallet as a late-cancellation fee.",
                ],
        ], 200);
    }

    private function completeExpiredBookings(): void
    {
        Booking::where('status', 'confirmed')
            ->where('check_out_date', '<', now())
            ->update(['status' => 'completed']);
    }
}
