@component('mail::message')
# 出品者様へ

{{ $buyer->name }}さんが、あなたの商品「{{ $sold_item->item->name }}」を受取評価しました。

評価：{{ $sold_item->buyer_rating }} ★

ログインしてマイページの取引ページから詳細を確認できます。

ご利用ありがとうございます。  
@endcomponent
