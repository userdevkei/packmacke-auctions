<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LpoItem extends Model
{

    protected $table = 'lpo_items';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'lpo_id',
        'item_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
        'is_vatable',
        'vat_rate',
        'vat_amount',
        'gross_amount',
        'item_notes',
        'line_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_vatable' => 'boolean',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'line_number' => 'integer',
    ];

    /**
     * Relationships
     */
    public function lpo()
    {
        return $this->belongsTo(LocalPurchaseOrder::class, 'lpo_id');
    }

    public function item()
    {
        return $this->belongsTo(LpoItem::class, 'item_id');
    }

    public function uom()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }

    /**
     * Accessors
     */
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 3) . ' ' . $this->unit;
    }

    public function getFormattedUnitPriceAttribute()
    {
        return 'KES ' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'KES ' . number_format($this->total_price, 2);
    }

    public function getFormattedVatAmountAttribute()
    {
        return 'KES ' . number_format($this->vat_amount, 2);
    }

    public function getFormattedGrossAmountAttribute()
    {
        return 'KES ' . number_format($this->gross_amount, 2);
    }

    public function getVatStatusAttribute()
    {
        return $this->is_vatable ? "VAT {$this->vat_rate}%" : 'Non-VAT';
    }

    /**
     * Business Logic Methods
     */
    public function calculateAmounts()
    {
        // Calculate total price
        $this->total_price = $this->quantity * $this->unit_price;

        // Calculate VAT if applicable
        if ($this->is_vatable) {
            $this->vat_amount = $this->total_price * ($this->vat_rate / 100);
        } else {
            $this->vat_amount = 0;
        }

        // Calculate gross amount
        $this->gross_amount = $this->total_price + $this->vat_amount;

        return $this;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Set default VAT rate if not set
            if (empty($item->vat_rate)) {
                $item->vat_rate = 16.00;
            }

            // Calculate amounts before creating
            $item->calculateAmounts();

            // Auto-set line number if not provided
            if (empty($item->line_number)) {
                $lastItem = self::where('lpo_id', $item->lpo_id)
                    ->orderBy('line_number', 'desc')
                    ->first();

                $item->line_number = $lastItem ? $lastItem->line_number + 1 : 1;
            }
        });

        static::updating(function ($item) {
            // Recalculate amounts when updating
            $item->calculateAmounts();
        });

        static::saved(function ($item) {
            // Update parent LPO totals whenever an item is saved
            $item->lpo->calculateTotals()->save();
        });

        static::deleted(function ($item) {
            // Update parent LPO totals when an item is deleted
            if ($item->lpo) {
                $item->lpo->calculateTotals()->save();
            }
        });
    }
}
