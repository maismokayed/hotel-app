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
        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم إنشاء الكوبون بنجاح.',
                'en' => 'Coupon created successfully.',
            ],
            'data' => new CouponResource($coupon),
        ], 201);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());
        return new CouponResource($coupon);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'تم حذف الكوبون بنجاح.',
                'en' => 'Coupon deleted successfully.',
            ],
        ], 200);
    }

    public function check(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'الكوبون غير موجود',
                    'en' => 'Coupon not found',
                ],
                'data' => [
                    'valid' => false,
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
                'success' => false,
                'message' => $reason,
                'data' => [
                    'valid' => false,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'ar' => 'الكوبون صالح للاستخدام',
                'en' => 'Coupon is valid',
            ],
            'data' => [
                'valid'          => true,
                'code'           => $coupon->code,
                'discount_type'  => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
        ], 200);
    }
}
