<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;
use App\Models\User;
use App\Models\Item;
use App\Models\SoldItem;
use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function profile()
    {

        $profile = Profile::where('user_id', Auth::id())->first();

        return view('profile', compact('profile'));
    }

    public function updateProfile(ProfileRequest $request)
    {

        $img = $request->file('img_url');
        if (isset($img)) {
            $img_url = Storage::disk('local')->put('public/img', $img);
        } else {
            $img_url = '';
        }

        $profile = Profile::where('user_id', Auth::id())->first();
        if ($profile) {
            $profile->update([
                'user_id' => Auth::id(),
                'img_url' => $img_url,
                'postcode' => $request->postcode,
                'address' => $request->address,
                'building' => $request->building
            ]);
        } else {
            Profile::create([
                'user_id' => Auth::id(),
                'img_url' => $img_url,
                'postcode' => $request->postcode,
                'address' => $request->address,
                'building' => $request->building
            ]);
        }

        User::find(Auth::id())->update([
            'name' => $request->name
        ]);

        return redirect('/');
    }

    public function mypage(Request $request)
    {
        $user = User::find(Auth::id());
        $page = $request->page ?? 'sell'; // ← デフォルトを 'sell' にしておくと安全

        // 未読メッセージ総数の算出
        $totalUnreadCount = \App\Models\Chat::whereHas('soldItem', function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('item', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            })->whereIn('status', ['trading', 'buyer_rated']);
        })
            ->where('is_read', false)
            ->where('user_id', '!=', $user->id)
            ->count();

        // ✅ 評価計算（購入者＋出品者両方）
        $buyerRatings = \App\Models\SoldItem::where('user_id', $user->id)
        ->whereNotNull('buyer_rating')
        ->pluck('buyer_rating');

        $sellerRatings = \App\Models\SoldItem::whereHas('item', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->whereNotNull('seller_rating')
            ->pluck('seller_rating');

        $allRatings = $buyerRatings->merge($sellerRatings);
        $avgRating = $allRatings->count() > 0 ? round($allRatings->avg(), 1) : null;

        // ページごとの分岐
        if ($page === 'buy') {
            // 購入した商品
            $items = SoldItem::where('user_id', $user->id)->get()->map(function ($sold_item) {
                return $sold_item->item;
            });
        } elseif ($page === 'trading') {
            // 取引中 or 評価中（購入者が評価済）の商品
            $items = SoldItem::where(function ($q) use ($user) {
                $q->where('user_id', $user->id) // 購入者
                    ->orWhereHas('item', function ($query) use ($user) {
                        $query->where('user_id', $user->id); // 出品者
                    });
            })
                ->whereIn('status', ['trading', 'buyer_rated'])                ->with(['item', 'buyer', 'item.user'])
                ->get()
                ->map(function ($sold_item) use ($user) {
                    $item = $sold_item->item;
                    $item->sold_item_id = $sold_item->id;

                    // 未読メッセージ数も同様に取得
                    $item->unread_count = \App\Models\Chat::where('sold_item_id', $sold_item->id)
                        ->where('user_id', '!=', $user->id)
                        ->where('is_read', false)
                        ->count();

                    return $item;
                });
        } elseif ($page === 'sell' || empty($page)) {
            // 出品した商品一覧 ← 元の挙動を明示的に復活！
            $items = Item::where('user_id', $user->id)->get();
        } else {
            $items = collect(); // 万一どれにも該当しない場合は空コレクション
        }

        return view('mypage', compact('user', 'items', 'page', 'totalUnreadCount', 'avgRating'));
    }
}
