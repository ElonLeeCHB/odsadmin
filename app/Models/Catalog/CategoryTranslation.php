<?php

namespace App\Models\Catalog;

//use Illuminate\Database\Eloquent\Model;
use App\Models\Common\TermTranslation;

class CategoryTranslation extends TermTranslation
{
    public $timestamps = false;
    public $table = 'term_translations';
    public $foreign_key = 'term_id'; // 必須，因為 category 跟 term 字串不一樣
    protected $fillable = ['name', 'short_name'];
    protected $guarded = [];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
