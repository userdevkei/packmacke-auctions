<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ItemCategory extends Model
{
    use softDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'type', 'category_name', 'status', 'user_id'];

    // Dynamically get enum values from database
    public static function getTypeOptions(): array
    {
        $enumValues = self::getEnumValues('item_categories', 'type');

        return collect($enumValues)->mapWithKeys(function ($value) {
            return [$value => ucfirst($value)];
        })->toArray();
    }

    public static function getStatusOptions(): array
    {
        $enumValues = self::getEnumValues('item_categories', 'status');

        return collect($enumValues)->mapWithKeys(function ($value) {
            return [$value => ucfirst($value)];
        })->toArray();
    }

    // Helper method to extract enum values from database
    protected static function getEnumValues(string $table, string $column): array
    {
        // Remove DB::raw() - just pass the string directly
        $type = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'")[0]->Type;

        preg_match('/^enum\((.*)\)$/', $type, $matches);

        $enum = [];
        foreach (explode(',', $matches[1]) as $value) {
            $enum[] = trim($value, "'");
        }

        return $enum;
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return self::getTypeOptions()[$this->type] ?? ucfirst($this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }
}
