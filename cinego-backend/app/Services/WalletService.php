<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
  
    public function credit(User $user, float $amount, string $description, string $type = 'refund', $reference = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Số tiền phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $type, $reference) {
            $user->lockForUpdate();
            $user->wallet_balance += $amount;
            $user->save();

            return $this->log($user, $amount, $type, $description, $reference);
        });
    }

  
    public function debit(User $user, float $amount, string $description, string $type = 'withdraw', $reference = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Số tiền phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $type, $reference) {
            $user->lockForUpdate();

            if ($user->wallet_balance < $amount) {
                throw new \RuntimeException('Số dư ví không đủ.');
            }

            $user->wallet_balance -= $amount;
            $user->save();

            return $this->log($user, -$amount, $type, $description, $reference);
        });
    }

    
    private function log(User $user, float $amount, string $type, string $description, $reference = null): WalletTransaction
    {
        return WalletTransaction::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'type'           => $type,
            'description'    => $description,
            'balance_after'  => $user->wallet_balance,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id'   => $reference ? $reference->id : null,
        ]);
    }

    public function getBalance(User $user): float
    {
        return (float) $user->wallet_balance;
    }
}