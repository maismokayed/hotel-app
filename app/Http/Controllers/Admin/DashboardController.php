<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'bookings'  => $this->bookingStats(),
            'revenue'   => $this->revenueStats(),
            'hotels'    => $this->hotelStats(),
            'rooms'     => $this->roomStats(),
            'wallet'    => $this->walletStats(),
            'users'     => $this->userStats(),
        ]);
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
        $occupied  = Room::where('status', 'occupied')->count();

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
}
