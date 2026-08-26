<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(
            [
                'bookings' => $this->bookingStats(),
                'revenue'  => $this->revenueStats(),
                'hotels'   => $this->hotelStats(),
                'rooms'    => $this->roomStats(),
                'wallet'   => $this->walletStats(),
                'users'    => $this->userStats(),
                'charts'   => [
                    'monthly_booking_growth' => $this->monthlyBookingGrowth(),
                    'hotels_by_city'         => $this->hotelsByCity(),
                    'top_hotels'             => $this->topHotels(),
                    'user_distribution'      => $this->userDistribution(),
                ],
            ],
            [
                'ar' => 'تم جلب بيانات لوحة التحكم بنجاح.',
                'en' => 'Dashboard data fetched successfully.',
            ]
        );
    }
    private function bookingStats(): array
    {
        $total     = Booking::count();
        $today     = Booking::whereDate('created_at', today())->count();
        $thisMonth = Booking::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $byStatus = Booking::select('status', DB::raw('count(*) as count'))
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

    private function revenueStats(): array
    {
        $total     = Booking::whereIn('status', ['confirmed', 'completed'])->sum('final_price');
        $thisMonth = Booking::whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('final_price');

        $byPayment = Booking::whereIn('status', ['confirmed', 'completed'])
            ->select('payment_method', DB::raw('sum(final_price) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $totalDiscount = Booking::sum('discount_amount');

        return [
            'total'          => round($total, 2),
            'this_month'     => round($thisMonth, 2),
            'by_payment'     => [
                'wallet' => round($byPayment['wallet'] ?? 0, 2),
                'cash'   => round($byPayment['cash']   ?? 0, 2),
            ],
            'total_discount_given' => round($totalDiscount, 2),
        ];
    }

    private function hotelStats(): array
    {
        return [
            'total'    => Hotel::count(),
            'active'   => Hotel::where('is_active', true)->count(),
            'inactive' => Hotel::where('is_active', false)->count(),
        ];
    }

    private function roomStats(): array
    {
        $total     = Room::count();
        $available = Room::where('status', 'available')->count();
        $occupied  = Room::where('status', 'booked')->count();

        $occupancyRate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

        $byType = Room::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        return [
            'total'          => $total,
            'available'      => $available,
            'occupied'       => $occupied,
            'occupancy_rate' => $occupancyRate . '%',
            'by_type'        => $byType,
        ];
    }

    private function walletStats(): array
    {
        $totalCredit = WalletTransaction::where('transaction_type', 'credit')->sum('amount');
        $totalDebit  = WalletTransaction::where('transaction_type', 'debit')->sum('amount');
        return [
            'total_credit' => round($totalCredit, 2),
            'total_debit'  => round($totalDebit, 2),
        ];
    }
    private function userStats(): array
    {
        return [
            'total'      => User::count(),
            'this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    private function monthlyBookingGrowth(): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        $grouped = Booking::where('created_at', '>=', $start)
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

    private function hotelsByCity(): array
    {
        return Hotel::join('cities', 'hotels.city_id', '=', 'cities.id')
            ->select('cities.name_ar as city', DB::raw('count(hotels.id) as count'))
            ->groupBy('cities.id', 'cities.name_ar')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => ['city' => $row->city, 'count' => (int) $row->count])
            ->all();
    }

    private function topHotels(): array
    {
        return Booking::join('hotels', 'bookings.hotel_id', '=', 'hotels.id')
            ->select('hotels.id', 'hotels.name_ar', DB::raw('count(bookings.id) as bookings_count'))
            ->groupBy('hotels.id', 'hotels.name_ar')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'hotel_id' => $row->id,
                'name'     => $row->name_ar,
                'bookings' => (int) $row->bookings_count,
            ])
            ->all();
    }

    private function userDistribution(): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get()
            ->map(fn($row) => ['role' => $row->name, 'count' => (int) $row->count])
            ->all();
    }
}
