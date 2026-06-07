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

}
