<?php

namespace Modules\Inventory\Entities;

use App\Models\UserInfo;
use App\Services\CustomIds;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalPurchaseOrder extends Model
{
    use SoftDeletes;

    protected $table = 'local_purchase_orders';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'lpo_number',
        'date',
        'supplier_id',
        'supplier_name',
        'subtotal',
        'vat_amount',
        'total_amount',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(LpoItem::class, 'lpo_id')->orderBy('line_number');
    }

    public function statusHistory()
    {
        return $this->hasMany(LpoStatusHistory::class, 'lpo_id')->orderBy('changed_at', 'desc');
    }

    public function createdBy()
    {
        return $this->belongsTo(UserInfo::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(UserInfo::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(UserInfo::class, 'approved_by');
    }

    /**
     * Accessors
     */
    public function getSupplierDisplayNameAttribute()
    {
        return $this->supplier_name ?? $this->supplier?->supplier_name ?? 'N/A';
    }

    public function getFormattedDateAttribute()
    {
        return $this->date?->format('d/m/Y');
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'KES ' . number_format($this->subtotal, 2);
    }

    public function getFormattedVatAmountAttribute()
    {
        return 'KES ' . number_format($this->vat_amount, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'KES ' . number_format($this->total_amount, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'secondary',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'completed' => 'info',
            'cancelled' => 'dark',
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    /**
     * Scopes
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    /**
     * Business Logic Methods
     */
    public function calculateTotals()
    {
        $subtotal = 0;
        $vatAmount = 0;

        foreach ($this->items as $item) {
            $subtotal += $item->total_price;
            $vatAmount += $item->vat_amount;
        }

        $this->subtotal = $subtotal;
        $this->vat_amount = $vatAmount;
        $this->total_amount = $subtotal + $vatAmount;

        return $this;
    }

    public function updateStatus($newStatus, $userId = null, $remarks = null)
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === 'approved' && !$this->approved_at) {
            $this->approved_by = $userId;
            $this->approved_at = now();
        }

        $this->save();

        // Log status change
        LpoStatusHistory::create([
            'id' => (new CustomIds())->generateId(),
            'lpo_id' => $this->id,
            'previous_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'changed_at' => now(),
            'remarks' => $remarks,
        ]);

        return $this;
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function canEdit()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canDelete()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lpo) {
            if (empty($lpo->lpo_number)) {
                $lpo->lpo_number = self::generateLpoNumber();
            }
        });

        static::deleting(function ($lpo) {
            // Log deletion as status change
            LpoStatusHistory::create([
                'id' => (new CustomIds())->generateId(),
                'lpo_id' => $lpo->id,
                'previous_status' => $lpo->status,
                'new_status' => 'deleted',
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'remarks' => 'LPO deleted',
            ]);
        });
    }

    /**
     * Generate unique LPO number
     */
    public static function generateLpoNumber()
    {
        $prefix = 'LPO';
        $date = now()->format('y');

        // Get the last LPO number for today
        $lastLpo = self::where('lpo_number', 'LIKE', "{$prefix}-{$date}%")
            ->orderBy('lpo_number', 'desc')
            ->first();

        if ($lastLpo) {
            // Extract sequence number and increment
            $lastNumber = intval(substr($lastLpo->lpo_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$date}{$newNumber}";
    }
}
