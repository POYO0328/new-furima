<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;
use App\Models\SoldItem;
use App\Http\Requests\ChatRequest;

class ChatController extends Controller
{
    public function show($sold_item_id)
    {
        $soldItem = SoldItem::with(['item', 'item.user', 'buyer'])->findOrFail($sold_item_id);
        $currentUser = auth()->user();

        // 出品者と購入者
        $seller = $soldItem->item->user; // 出品者
        $buyer = $soldItem->buyer;       // 購入者

        // 現在ログインしているユーザーがどちらかを判定
        $isBuyer = $currentUser->id === $buyer->id;
        $isSeller = $currentUser->id === $seller->id;

        // 現在ログインしているユーザーがどちらかを判定
        if ($currentUser->id === $buyer->id) {
            $chatPartner = $seller; // 購入者から見た相手 = 出品者
        } else {
            $chatPartner = $buyer;  // 出品者から見た相手 = 購入者
        }

        // 自分のプロフィールも取得
        $authUser = $currentUser->load('profile');

        Chat::where('sold_item_id', $sold_item_id)
            ->where('user_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $chats = Chat::where('sold_item_id', $sold_item_id)
            ->orderBy('created_at', 'asc')
            ->get();

        // 取引中リスト（サイドバー用）
        $activeTrades = SoldItem::with('item')
            ->whereIn('status', ['trading', 'buyer_rated'])
            ->where(function ($query) use ($currentUser) {
                $query->whereHas('buyer', fn($q) => $q->where('id', $currentUser->id))
                    ->orWhereHas('item.user', fn($q) => $q->where('id', $currentUser->id));
            })
            ->get();

        $status = $soldItem->status;
        $canRate = false;
        $canSellerRate = false;

        if ($status === 'trading' && $isBuyer) {
            $canRate = true; // 購入者のみ
        } elseif ($status === 'buyer_rated' && $isSeller) {
            $canSellerRate = true; // 出品者のみ
        }

        return view('chat.show', compact('soldItem', 'chats', 'chatPartner', 'authUser', 'activeTrades', 'isBuyer', 'isSeller', 'canRate', 'canSellerRate'));
    }

    public function store(ChatRequest $request, $sold_item_id)
    {
        $soldItem = SoldItem::findOrFail($sold_item_id);

        $validated = $request->validated();

        // 画像保存
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat_images', 'public');
            $validated['image'] = $path;
        }

        $chatData = [
            'sold_item_id' => $sold_item_id,
            'user_id' => Auth::id(),
            'message' => $validated['message'] ?? '',
            'image' => null,
            'is_read' => false,
        ];

        if ($request->hasFile('image')) {
            $chatData['image'] = $request->file('image')->store('chat_images', 'public');
        }

        Chat::create($chatData);

        return redirect()->route('chat.show', ['sold_item' => $sold_item_id]);
    }

    public function destroy(Chat $chat)
    {
        // 自分のメッセージだけ削除可能
        if ($chat->user_id !== auth()->id()) {
            abort(403, '不正な操作です');
        }

        $chat->delete();

        return redirect()->back()->with('success', 'メッセージを削除しました');
    }


    public function update(ChatRequest $request, Chat $chat)
    {
        if ($chat->user_id !== auth()->id()) {
            abort(403, '不正な操作です');
        }

        $validated = $request->validated();

         if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('chat_images', 'public');
        }

        $chat->update([
            'message' => $validated['message'],
            'image' => $validated['image'] ?? $chat->image,
        ]);

        // fetchの場合はJSON返す
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'メッセージを更新しました');
    }
}
