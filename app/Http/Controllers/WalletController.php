<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Http\Requests\WalletDepositRequest;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;

class WalletController extends Controller
{
 public function show(Request $request)
{
    $wallet = $request->user()->wallet;

    if (!$wallet) {
        return response()->json(['message' => 'المحفظة غير موجودة.'], 404);
    }

    return new WalletResource($wallet);
}

    public function deposit(WalletDepositRequest $request)
    {
        $user   = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);
        }

        $wallet->increment('balance', $request->amount);

        WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'user_id'          => $user->id,
            'amount'           => $request->amount,
            'transaction_type' => 'credit',
            'transaction_date' => now(),
        ]);

        return new WalletResource($wallet->fresh());
    }

  public function transactions(Request $request)
{
    $wallet = $request->user()->wallet;

    if (!$wallet) {
        return response()->json(['message' => 'المحفظة غير موجودة.'], 404);
    }

    $transactions = $wallet->transactions()->latest()->get();
    return WalletTransactionResource::collection($transactions);
}
}
