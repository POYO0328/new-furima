<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'sold_item_id',
        'user_id',
        'message',
    ];

    /**
     * このチャットが属する取引（sold_item）を取得
     */
    public function soldItem()
    {
        // 第二引数で外部キーを明示的に指定する！
        return $this->belongsTo(\App\Models\SoldItem::class, 'sold_item_id', 'id');
    }


    /**
     * メッセージ送信者（ユーザー）を取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
