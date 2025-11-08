@extends('layouts.default')

@section('title','マイページ')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/index.css')  }}">
<link rel="stylesheet" href="{{ asset('/css/mypage.css')  }}">
@endsection

@section('content')

@include('components.header')
<div class="container">
    <div class="user">
        <div class="user__info">
            <div class="user__img">
                @if (isset($user->profile->img_url))
                <img class="user__icon" src="{{ \Storage::url($user->profile->img_url) }}" alt="">
                @else
                <img id="myImage" class="user__icon" src="{{ asset('img/icon.png') }}" alt="">
                @endif
            </div>
            <div>
                <p class="user__name">{{$user->name}}</p>
                @if($avgRating)
                <p class="user__rating">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <=round($avgRating))
                        <span class="star filled">★</span>
                        @else
                        <span class="star">★</span>
                        @endif
                        @endfor
                </p>
                @else

                @endif
            </div>

        </div>
        <div class="mypage__user--btn">
            <a class="btn2" href="/mypage/profile">プロフィールを編集</a>
        </div>
    </div>

    <div class="border">
        <ul class="border__list">
            <li><a href="/mypage?page=sell">出品した商品</a></li>
            <li><a href="/mypage?page=buy">購入した商品</a></li>
            <li>
                <a href="/mypage?page=trading"
                    class="{{ $totalUnreadCount > 0 ? 'has-unread' : '' }}">
                    取引中の商品
                    @if($totalUnreadCount > 0)
                    <span class="total-unread">{{ $totalUnreadCount }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </div>

    <div class="items">
        @if($page === 'trading')
        @foreach ($items as $item)
        <div class="item">
            <a href="{{ route('chat.show', ['sold_item' => $item->sold_item_id]) }}">
                <div class="item__img--container">
                    <img src="{{ \Storage::url($item->img_url) }}" class="item__img" alt="商品画像">
                    @if(isset($item->unread_count) && $item->unread_count > 0)
                    <div class="badge">{{ $item->unread_count }}</div>
                    @endif
                </div>
                <p class="item__name">{{$item->name}}</p>
            </a>
        </div>
        @endforeach
        @else
        @foreach ($items as $item)
        <div class="item">
            <a href="/item/{{$item->id}}">
                @if ($item->sold())
                <div class="item__img--container sold">
                    <img src="{{ \Storage::url($item->img_url) }}" class="item__img" alt="商品画像">
                </div>
                @else
                <div class="item__img--container">
                    <img src="{{ \Storage::url($item->img_url) }}" class="item__img" alt="商品画像">
                </div>
                @endif
                <p class="item__name">{{$item->name}}</p>
            </a>
        </div>
        @endforeach
        @endif
    </div>
</div>
@endsection