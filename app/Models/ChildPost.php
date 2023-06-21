<?php

namespace App\Models;

class ChildPost extends Post 
{
    protected $table = 'posts';
    protected $translationForeignKey = 'post_id';
}