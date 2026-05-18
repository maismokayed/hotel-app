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
        return new WalletResource($request->user()->wallet);
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
                'transaction_date' => now(),
            ]);
        });

        return new WalletResource($wallet->fresh());
    }

    public function transactions(Request $request)
    {
        $transactions = $request->user()->wallet->transactions()->latest()->get();
        return WalletTransactionResource::collection($transactions);
    }
}