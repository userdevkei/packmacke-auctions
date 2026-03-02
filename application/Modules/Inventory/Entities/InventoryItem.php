<?php

namespace Modules\Inventory\Entities;

use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InventoryItem extends Model
{
    use softDeletes;

    protected $fillable = ['id', 'category_id', 'item_name', 'unit', 'status', 'user_id'];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(UserInfo::class, 'user_id');
    }

    // Dynamically get unit options with descriptive labels
    public static function getUnitOptions(): array
    {
        $enumValues = self::getEnumValues('inventory_items', 'unit');

        $unitLabels = [
            'm' => 'Meters',
            'ft' => 'Feet',
            'ltr' => 'Liters',
            'pcs' => 'Pieces',
            'kg' => 'Kilograms',
            'g' => 'Grams',
            'mg' => 'Milligrams',
            'lb' => 'Pounds',
            'oz' => 'Ounces',
            'ml' => 'Milliliters',
            'gal' => 'Gallons',
            'cm' => 'Centimeters',
            'mm' => 'Millimeters',
            'km' => 'Kilometers',
            'in' => 'Inches',
            'doz' => 'Dozen',
            'box' => 'Box',
            'pack' => 'Pack',
        ];

        return collect($enumValues)->mapWithKeys(function ($value) use ($unitLabels) {
            return [$value => $unitLabels[$value] ?? ucfirst($value)];
        })->toArray();
    }

    // Dynamically get status options
    public static function getStatusOptions(): array
    {
        $enumValues = self::getEnumValues('inventory_items', 'status');

        return collect($enumValues)->mapWithKeys(function ($value) {
            return [$value => ucfirst($value)];
        })->toArray();
    }

    // Helper method to extract enum values from database
    protected static function getEnumValues(string $table, string $column): array
    {
        $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'")[0]->Type;

        preg_match('/^enum\((.*)\)$/', $type, $matches);

        $enum = [];
        foreach (explode(',', $matches[1]) as $value) {
            $enum[] = trim($value, "'");
        }

        return $enum;
    }

    // Accessors for labels
    public function getUnitLabelAttribute(): string
    {
        return self::getUnitOptions()[$this->unit] ?? ucfirst($this->unit);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    // Optional: Status badge color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            default => 'gray',
        };
    }
}
