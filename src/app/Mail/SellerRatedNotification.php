<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SoldItem;
use App\Models\User;

class SellerRatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $sold_item;
    public $buyer;

    public function __construct(SoldItem $sold_item, User $buyer)
    {
        $this->sold_item = $sold_item;
        $this->buyer = $buyer;
    }

    public function build()
    {
        return $this->subject('【受取評価完了通知】購入者から評価が届きました')
                    ->markdown('emails.seller-rated');
    }
}
