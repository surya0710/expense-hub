<?php

namespace App\Services\PettyCash;

use App\Enums\PettyCashTransactionType;
use App\Models\Expense;
use App\Models\PettyCashWallet;
use App\Models\User;
use App\Notifications\PettyCashLowBalanceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PettyCashService
{
    public function createWallet(User $user, array $data): PettyCashWallet
    {
        if (! empty($data['custodian_id'])) {
            $this->assertEligibleCustodian((int) $data['custodian_id'], $user->company_id);
        }

        $opening = (float) $data['opening_balance'];

        return PettyCashWallet::query()->create([
            'company_id' => $user->company_id,
            'name' => $data['name'],
            'site' => $data['site'] ?? null,
            'custodian_id' => $data['custodian_id'] ?? null,
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'currency' => $data['currency'] ?? 'INR',
            'low_balance_threshold_percent' => $data['low_balance_threshold_percent'] ?? 20,
            'is_active' => true,
        ]);
    }

    public function topUp(PettyCashWallet $wallet, User $user, float $amount, ?string $note = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Top-up amount must be greater than zero.');
        }

        DB::transaction(function () use ($wallet, $user, $amount, $note) {
            $wallet = PettyCashWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $newBalance = (float) $wallet->current_balance + $amount;

            $wallet->update(['current_balance' => $newBalance]);

            $wallet->transactions()->create([
                'type' => PettyCashTransactionType::Credit,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'note' => $note ?? 'Wallet top-up',
                'created_by' => $user->id,
            ]);
        });
    }

    public function debitForExpense(Expense $expense, User $actor): void
    {
        if ($expense->payment_mode->value !== 'petty_cash' || ! $expense->wallet_id) {
            return;
        }

        DB::transaction(function () use ($expense, $actor) {
            $wallet = PettyCashWallet::query()->lockForUpdate()->findOrFail($expense->wallet_id);

            if ((float) $wallet->current_balance < (float) $expense->amount) {
                throw new \InvalidArgumentException('Insufficient petty cash balance.');
            }

            $newBalance = (float) $wallet->current_balance - (float) $expense->amount;
            $wallet->update(['current_balance' => $newBalance]);

            $wallet->transactions()->create([
                'expense_id' => $expense->id,
                'type' => PettyCashTransactionType::Debit,
                'amount' => $expense->amount,
                'balance_after' => $newBalance,
                'note' => 'Expense '.$expense->code,
                'created_by' => $actor->id,
            ]);

            $this->checkLowBalance($wallet);
        });
    }

    public function validateBalance(PettyCashWallet $wallet, float $amount): void
    {
        if ((float) $wallet->current_balance < $amount) {
            throw new \InvalidArgumentException(
                'Insufficient petty cash balance. Available: ₹'.number_format($wallet->current_balance, 2)
            );
        }
    }

    public function updateCustodian(PettyCashWallet $wallet, User $actor, int $custodianId): PettyCashWallet
    {
        $this->assertEligibleCustodian($custodianId, $wallet->company_id);

        $wallet->update(['custodian_id' => $custodianId]);

        return $wallet->fresh(['custodian']);
    }

    /**
     * Set wallet balance to an exact amount (e.g. after physical count / correction).
     */
    public function setBalance(PettyCashWallet $wallet, User $user, float $newBalance, ?string $note = null): void
    {
        if ($newBalance < 0) {
            throw new \InvalidArgumentException('Balance cannot be negative.');
        }

        DB::transaction(function () use ($wallet, $user, $newBalance, $note) {
            $wallet = PettyCashWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $current = (float) $wallet->current_balance;
            $difference = round($newBalance - $current, 2);

            if ($difference === 0.0) {
                return;
            }

            $wallet->update(['current_balance' => $newBalance]);

            $wallet->transactions()->create([
                'type' => $difference > 0 ? PettyCashTransactionType::Credit : PettyCashTransactionType::Debit,
                'amount' => abs($difference),
                'balance_after' => $newBalance,
                'note' => $note ?? 'Balance adjusted',
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * Roles that can view and operate petty cash wallets.
     *
     * @return list<string>
     */
    public function custodianRoles(): array
    {
        return ['manager', 'admin', 'accountant', 'owner'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function eligibleCustodians(int $companyId)
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->role($this->custodianRoles())
            ->with('roles')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function isEligibleCustodian(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole($this->custodianRoles());
    }

    protected function assertEligibleCustodian(int $userId, int $companyId): void
    {
        $custodian = User::query()
            ->where('company_id', $companyId)
            ->whereKey($userId)
            ->where('is_active', true)
            ->role($this->custodianRoles())
            ->first();

        if (! $custodian) {
            throw new \InvalidArgumentException('Custodian must be an active Manager, Admin, Accountant, or Owner with petty cash access.');
        }
    }

    public function reconcile(PettyCashWallet $wallet, User $user, float $physicalCount, ?string $note = null): array
    {
        $systemBalance = (float) $wallet->current_balance;
        $difference = round($physicalCount - $systemBalance, 2);
        $tolerance = config('expense.petty_cash_reconcile_tolerance', 100);

        return [
            'system_balance' => $systemBalance,
            'physical_count' => $physicalCount,
            'difference' => $difference,
            'within_tolerance' => abs($difference) <= $tolerance,
            'note' => $note,
            'reconciled_by' => $user->id,
            'reconciled_at' => now(),
        ];
    }

    protected function checkLowBalance(PettyCashWallet $wallet): void
    {
        $wallet->refresh();

        if (! $wallet->isLowBalance()) {
            return;
        }

        $recipients = collect([$wallet->custodian])
            ->merge(
                User::query()
                    ->where('company_id', $wallet->company_id)
                    ->role(['owner', 'admin'])
                    ->get()
            )
            ->filter()
            ->unique('id');

        Notification::send($recipients, new PettyCashLowBalanceNotification($wallet));
    }
}
