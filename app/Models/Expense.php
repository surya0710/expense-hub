<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMode;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Storage\ReceiptStorageManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Expense extends Model implements HasMedia
{
    use BelongsToCompany;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'submitted_by',
        'date',
        'amount',
        'currency',
        'category_id',
        'cost_center_id',
        'vendor_name',
        'payment_mode',
        'wallet_id',
        'gst_percent',
        'gst_amount',
        'description',
        'tags',
        'reimbursable',
        'status',
        'current_approval_step',
        'approval_due_at',
        'payout_batch_id',
        'reimbursed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'gst_percent' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'tags' => 'array',
            'reimbursable' => 'boolean',
            'status' => ExpenseStatus::class,
            'payment_mode' => PaymentMode::class,
            'current_approval_step' => 'integer',
            'approval_due_at' => 'datetime',
            'reimbursed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $disk = app(ReceiptStorageManager::class)->writeDisk();

        $this->addMediaCollection('receipts')
            ->useDisk($disk)
            ->acceptsMimeTypes(config('receipts.allowed_mimes'));
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PettyCashWallet::class, 'wallet_id');
    }

    public function payoutBatch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class);
    }

    public function receiptUrl(?Media $media = null): ?string
    {
        $media ??= $this->getFirstMedia('receipts');

        if (! $media) {
            return null;
        }

        return app(ReceiptStorageManager::class)->temporaryUrl($media);
    }

    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpenseApproval::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [ExpenseStatus::Draft, ExpenseStatus::Rejected]);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->can('expense.view.all')) {
            return $query;
        }

        return $query->where($query->getModel()->qualifyColumn('submitted_by'), $user->id);
    }
}
