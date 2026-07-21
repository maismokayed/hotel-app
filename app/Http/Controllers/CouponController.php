<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Http\Resources\CouponResource;


class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->get();
        return CouponResource::collection($coupons);
    }

    public function show(Coupon $coupon)
    {
        return new CouponResource($coupon);
    }

    public function store(StoreCouponRequest $request)
    {
        $coupon = Coupon::create($request->validated());
        return (new CouponResource($coupon))->response()->setStatusCode(201);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());
        return new CouponResource($coupon);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['message' => 'تم حذف الكوبون بنجاح.']);
    }

    public function check(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'valid'   => false,
                'message' => [
                    'ar' => 'الكوبون غير موجود',
                    'en' => 'Coupon not found',
                ],
            ], 404);
        }

        if (!$coupon->isValid()) {
            $reason = [
                'ar' => 'الكوبون غير صالح للاستخدام',
                'en' => 'Coupon is not valid',
            ];

            if (!$coupon->is_active) {
                $reason = [
                    'ar' => 'الكوبون غير مفعل',
                    'en' => 'Coupon is not active',
                ];
            } elseif ($coupon->expires_at && $coupon->expires_at->isPast()) {
                $reason = [
                    'ar' => 'انتهت صلاحية الكوبون',
                    'en' => 'Coupon has expired',
                ];
            } elseif ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
                $reason = [
                    'ar' => 'تم استنفاد عدد مرات استخدام الكوبون',
                    'en' => 'Coupon usage limit has been reached',
                ];
            }

            return response()->json([
                'valid'   => false,
                'message' => $reason,
            ]);
        }

        return response()->json([
            'valid'   => true,
            'message' => [
                'ar' => 'الكوبون صالح للاستخدام',
                'en' => 'Coupon is valid',
            ],
            'data' => [
                'code'           => $coupon->code,
                'discount_type'  => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
        ]);
    }
}
