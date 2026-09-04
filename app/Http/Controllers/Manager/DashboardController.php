<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * لوحة تحكم المدير: نفس شكل استجابة لوحة تحكم الأدمن (success/message/data)
     * لكن كل الإحصائيات مقتصرة على الفنادق التابعة للمدير المسجّل دخوله فقط.
     */
    public function index(): JsonResponse
    {
        $managerId = auth()->id();

        return $this->success(
            [
                'hotels'   => $this->hotelStats($managerId),
                'bookings' => $this->bookingStats($managerId),
                'revenue'  => $this->revenueStats($managerId),
                'charts'   => [
                    'monthly_booking_growth' => $this->monthlyBookingGrowth($managerId),
                ],
            ],
            [
                'ar' => 'تم جلب بيانات لوحة تحكم المدير بنجاح.',
                'en' => 'Manager dashboard data fetched successfully.',
            ]
        );
    }

    /**
     * IDs الفنادق التابعة لهذا المدير فقط.
     */
    private function hotelIds(int $managerId)
    {
        return Hotel::where('user_id', $managerId)->pluck('id');
    }

    private function hotelStats(int $managerId): array
    {
        return [
            'total'    => Hotel::where('user_id', $managerId)->count(),
            'active'   => Hotel::where('user_id', $managerId)->where('is_active', true)->count(),
            'inactive' => Hotel::where('user_id', $managerId)->where('is_active', false)->count(),
        ];
    }

    private function bookingStats(int $managerId): array
    {
        $hotelIds = $this->hotelIds($managerId);

        $total     = Booking::whereIn('hotel_id', $hotelIds)->count();
        $today     = Booking::whereIn('hotel_id', $hotelIds)
            ->whereDate('created_at', today())
            ->count();
        $thisMonth = Booking::whereIn('hotel_id', $hotelIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $byStatus = Booking::whereIn('hotel_id', $hotelIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total'      => $total,
            'today'      => $today,
            'this_month' => $thisMonth,
            'by_status'  => [
                'pending'   => $byStatus['pending']   ?? 0,
                'confirmed' => $byStatus['confirmed'] ?? 0,
                'cancelled' => $byStatus['cancelled'] ?? 0,
                'completed' => $byStatus['completed'] ?? 0,
            ],
        ];
    }

    private function revenueStats(int $managerId): array
    {
        $hotelIds = $this->hotelIds($managerId);

        $total = Booking::whereIn('hotel_id', $hotelIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('final_price');

        $thisMonth = Booking::whereIn('hotel_id', $hotelIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('final_price');

        $byPayment = Booking::whereIn('hotel_id', $hotelIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->select('payment_method', DB::raw('sum(final_price) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return [
            'total'      => round($total, 2),
            'this_month' => round($thisMonth, 2),
            'by_payment' => [
                'wallet' => round($byPayment['wallet'] ?? 0, 2),
                'cash'   => round($byPayment['cash']   ?? 0, 2),
            ],
        ];
    }

    private function monthlyBookingGrowth(int $managerId): array
    {
        $hotelIds = $this->hotelIds($managerId);

        $start = now()->subMonths(5)->startOfMonth();

        $grouped = Booking::whereIn('hotel_id', $hotelIds)
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn($booking) => $booking->created_at->format('Y-m'));

        return collect(range(5, 0))->map(function ($i) use ($grouped) {
            $date = now()->subMonths($i)->startOfMonth();
            $key  = $date->format('Y-m');

            return [
                'month' => $date->translatedFormat('F Y'),
                'count' => $grouped->get($key, collect())->count(),
            ];
        })->values()->all();
    }
}
