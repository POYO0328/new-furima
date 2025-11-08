<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'item_id',
        'sending_postcode',
        'sending_address',
        'sending_building'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    // public function item()
    // {
    //     return $this->belongsTo('App\Models\Item');
    // }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // 購入者（Buyer）
    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 出品者（Seller） → Item 経由で取得
    public function seller()
    {
        return $this->hasOneThrough(
            User::class,
            Item::class,
            'id',        // Item.id
            'id',        // User.id
            'item_id',   // SoldItem.item_id
            'user_id'    // Item.user_id（出品者）
        );
    }
}
