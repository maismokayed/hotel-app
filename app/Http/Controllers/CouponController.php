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

    // عرض كوبون واحد
    public function show(Coupon $coupon)
    {
        return new CouponResource($coupon);
    }

    // إنشاء كوبون جديد
    public function store(StoreCouponRequest $request)
    {
        $coupon = Coupon::create($request->validated());
        return (new CouponResource($coupon))->response()->setStatusCode(201);
    }

    // تعديل كوبون
    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->validated());
        return new CouponResource($coupon);
    }

    // حذف كوبون
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(['message' => 'تم حذف الكوبون بنجاح.']);
    }

}
