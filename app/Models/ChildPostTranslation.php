<?php

use Illuminate\Database\Eloquent\Model;

class ChildPostTranslation extends Model 
{
    protected $table = 'post_translations';
    public $timestamps = false;
    protected $fillable = ['title', 'content'];  
}