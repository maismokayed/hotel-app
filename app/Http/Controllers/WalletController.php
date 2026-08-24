<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Http\Requests\WalletDepositRequest;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = $request->user()->wallet;

        if (!$wallet) {

            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'المحفظة غير موجودة.',
                    'en' => 'Wallet not found.',
                ],
            ], 404);
        }

        return new WalletResource($wallet);
    }

    public function deposit(WalletDepositRequest $request)
    {
        $user   = $request->user();
        $wallet = $user->wallet;

        DB::transaction(function () use ($wallet, $user, $request) {
            $wallet->increment('balance', $request->amount);

            WalletTransaction::create([
                'wallet_id'        => $wallet->id,
                'user_id'          => $user->id,
                'amount'           => $request->amount,
                'transaction_type' => 'credit',
                'reason'           => 'deposit',
                'transaction_date' => now(),
            ]);
        });

        return new WalletResource($wallet->fresh());
    }

    public function transactions(Request $request)
    {
        $wallet = $request->user()->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'المحفظة غير موجودة.',
                    'en' => 'Wallet not found.',
                ],
            ], 404);
        }

        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $transactions = $wallet->transactions()->latest()->paginate($perPage);

        return WalletTransactionResource::collection($transactions);
    }
}
