<?php

namespace Modules\Admin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Signatory extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'signatory_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $date = 'deleted_at';
    protected $fillable = ['signatory_id', 'user_id', 'department_id', 'created_by', 'status', 'signature'];

    protected static function newFactory()
    {
        return \Modules\Admin\Database\factories\SignatoryFactory::new();
    }
}
