<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Traits\ModelTrait;
use App\Models\Inventory\CountingProduct;
use App\Models\Common\Term;
use App\Repositories\Eloquent\Common\TermRepository;

class Counting extends Model
{
    use ModelTrait;
    
    public $table = 'inventory_countings';
    protected $guarded = [];
    protected $appends = ['status_name'];


    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\InventoryCountingObserver::class);
    }
    
    
    // Relation
    
    public function counting_products()
    {
        return $this->hasMany(CountingProduct::class, 'counting_id', 'id');
    }


    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_code', 'code');
    }


    // public function status()
    // {
    //     $code = $this->status_code;
        
    //     return $this->belongsTo(Term::class, 'status_code', 'code')->where('taxonomy_code', 'common_form_status')->withDefault(function () use ($code) {
    //         return Term::getByCodeAndTaxonomyCode($code, 'common_form_status');
    //     });
    // }

    public function total(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    public function statusName(): Attribute
    {
        return Attribute::make(
            //get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->status_code, 'common_form_status') ?? ' - ',
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->status_code, 'common_form_status') ?? '',
        );
    }

    // Attribute

    public function formDateYmd(): Attribute
    {
        if(empty($this->id) && empty($this->form_date)) {
            $newValue = Carbon::now()->format('Y-m-d');
        }
        else if(!empty($this->form_date)){
            $newValue = Carbon::parse($this->form_date)->format('Y-m-d');
        } 

        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }
    
}
