<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\SoldItem;
use App\Mail\SellerRatedNotification;

class TradeController extends Controller
{
    public function complete(Request $request, SoldItem $sold_item)
    {
        $rating = (int) $request->input('rating');
        $user = Auth::user();

        // 評価値チェック
        if ($rating < 1 || $rating > 5) {
            return redirect()->back()->with('error', '正しい評価を入力してください。');
        }

        // 現在ログインしているユーザーが購入者 or 出品者か判定
        $isBuyer = $sold_item->user_id === $user->id ?? false;
        $isSeller = $sold_item->item->user_id === $user->id;

        // ★ 購入者が評価した場合
        if ($isBuyer) {
            $sold_item->seller_rating = $rating;
            $sold_item->status = 'buyer_rated';
        }
        // ★ 出品者が評価した場合
        elseif ($isSeller) {
            if ($sold_item->status !== 'buyer_rated') {
                return redirect()->back()->with('error', '購入者が評価を完了してから評価できます。');
            }
            $sold_item->buyer_rating = $rating;
            $sold_item->status = 'completed';
        } else {
            return redirect()->back()->with('error', 'この取引に対する評価権限がありません。');
        }

        $sold_item->save();

         if ($isBuyer) {
            // ✅ 出品者に通知メール送信
            $seller = $sold_item->item->user;
            Mail::to($seller->email)->send(new SellerRatedNotification($sold_item, $user));
        }


        // 完了したら商品一覧へ
        return redirect('http://localhost')->with('success', '評価を登録しました！');
    }
}
