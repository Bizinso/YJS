<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'icon',
        'order',
        'status',
    ];

    // Parent Menu
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    // Children / Submenus
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }
}
