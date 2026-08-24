<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'amount'           => $this->amount,
            'transaction_type' => $this->transaction_type,
            'reason'           => $this->reason,
            'description'      => [
                'ar' => $this->descriptionAr(),
                'en' => $this->descriptionEn(),
            ],
            'transaction_date' => $this->transaction_date->format('Y-m-d H:i'),
        ];
    }
    private function descriptionAr(): string
    {
        return match ($this->reason) {
            'deposit' => 'عملية إيداع',
            'payment' => 'عملية دفع',
            'refund'  => 'عملية استرجاع',
            default   => $this->transaction_type === 'credit' ? 'عملية إيداع' : 'عملية دفع',
        };
    }

    private function descriptionEn(): string
    {
        return match ($this->reason) {
            'deposit' => 'Deposit',
            'payment' => 'Payment',
            'refund'  => 'Refund',
            default   => $this->transaction_type === 'credit' ? 'Deposit' : 'Payment',
        };
    }
}
