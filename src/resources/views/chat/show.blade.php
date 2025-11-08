@extends('layouts.default')

@section('title', '取引チャット')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/chat.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="chat-wrapper">

    {{-- サイドバー --}}
    <div class="chat-sidebar">
        <h2>そ の 他 の 取 引</h2>
        <ul class="trade-list">
            @foreach($activeTrades as $trade)
            <li class="trade-item {{ $trade->id == $soldItem->id ? 'active' : '' }}">
                <a href="{{ route('chat.show', $trade->id) }}">
                    {{ $trade->item->name }}
                </a>
            </li>
            @endforeach
        </ul>
    </div>

    {{-- メインチャットエリア --}}
    <div class="chat-container">

        {{-- ヘッダー --}}
        <div class="chat-header">
            <div class="user-info">
                <img src="{{ isset($chatPartner->profile->img_url)
                    ? Storage::url($chatPartner->profile->img_url)
                    : asset('images/sample-user.png') }}"
                    alt="ユーザーアイコン" class="user-icon">
                <h2>「{{ $chatPartner->name ?? 'ユーザー名' }}」さんとの取引画面</h2>
            </div>
            @if ($canRate)
            <button class="complete-btn" id="openModalBtn">取引を完了する</button>
            @endif

        </div>

        {{-- 商品情報 --}}
        <div class="item-info">
            <img src="{{ isset($soldItem->item->img_url)
                ? Storage::url($soldItem->item->img_url)
                : asset('images/sample-item.png') }}"
                alt="商品画像"
                class="item-image">
            <div class="item-details">
                <p class="item-name">{{ $soldItem->item->name }}</p>
                <p class="item-price">¥{{ number_format($soldItem->item->price) }}</p>
            </div>
        </div>

        <hr>

        {{-- チャットメッセージ --}}
        <div class="chat-messages">
            @foreach($chats as $chat)
            <div class="chat-message {{ $chat->user_id == Auth::id() ? 'mine' : 'theirs' }}" data-id="{{ $chat->id }}">
                {{-- ユーザー情報 --}}
                @if($chat->user_id != Auth::id())
                <div class="message-user">
                    <img src="{{ isset($chatPartner->profile->img_url)
                                ? Storage::url($chatPartner->profile->img_url)
                                : asset('images/sample-user.png') }}"
                        alt="ユーザーアイコン" class="user-icon">
                    <span class="chat-username">{{ $chat->user->name }}</span>
                </div>
                @else
                <div class="message-user mine-user">
                    <span class="chat-username">{{ Auth::user()->name }}</span>
                    <img src="{{ isset(Auth::user()->profile->img_url)
                                ? Storage::url(Auth::user()->profile->img_url)
                                : asset('images/sample-user.png') }}"
                        alt="自分のアイコン" class="user-icon">
                </div>
                @endif

                {{-- メッセージ本文 --}}
                <div class="message-bubble">
                    <p>{{ $chat->message }}</p>
                </div>

                {{-- 自分のメッセージ操作 --}}
                @if($chat->user_id == Auth::id())
                <div class="chat-actions">
                    <button type="button" class="inline-cancel-btn" style="display:none;">✕</button>
                    <button type="button" class="edit-btn">編集</button>
                    <form action="{{ route('chat.destroy', $chat->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="delete-btn" onclick="return confirm('このメッセージを削除しますか？')">削除</button>
                    </form>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- 入力フォーム --}}
        <form id="chatForm" action="{{ route('chat.store', ['sold_item' => $soldItem->id]) }}" method="POST" class="chat-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="chat_id" id="chat_id" value="">
            <input type="text" name="message" id="chatMessageInput" placeholder="取引メッセージを記入してください" required>
            <label class="image-upload">
                画像を追加
                <input type="file" name="image" accept="image/*" hidden>
            </label>
            <button type="submit" class="send-btn">
                <img src="{{ Storage::url('img/send_icon.png') }}" alt="送信" class="send-icon">
            </button>

        </form>

        {{-- 取引完了モーダル --}}
        <div id="completeModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close"></span>
                <p class="comp">取引が完了しました。</p>
                <hr>
                <p class="hyouka">今回の取引相手はどうでしたか？</p>

                {{-- 星評価 --}}
                <div class="star-rating">
                    @for ($i = 1; $i <= 5; $i++)
                        <span class="star" data-value="{{ $i }}">★</span>
                        @endfor
                </div>

                <hr>

                {{-- 評価送信フォーム --}}
                <form id="completeForm" action="{{ route('trade.complete', ['sold_item' => $soldItem->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="rating" id="rating" value="0">
                    <button type="submit" class="send-btn">送信する</button>
                </form>
            </div>
        </div>

    </div>
</div>

{{-- JS --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('chatForm');
        const chatIdInput = document.getElementById('chat_id');
        const messageInput = document.getElementById('chatMessageInput');
        const sendBtnLabel = document.getElementById('sendBtnLabel');
        const modal = document.getElementById('completeModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = modal.querySelector('.close');
        const stars = modal.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');

        if (!messageInput) return;

        // --- 入力内容の保持 ---
        const storageKey = 'chatMessageDraft_' + '{{ $soldItem->id }}';

        // ページ読み込み時に復元
        setTimeout(() => {
            const savedMessage = localStorage.getItem(storageKey);
            if (savedMessage) {
                messageInput.value = savedMessage;
            }
        }, 0);

        messageInput.addEventListener('input', function() {
            localStorage.setItem(storageKey, messageInput.value);
        });

        // 送信時に削除
        form.addEventListener('submit', function() {
            localStorage.removeItem(storageKey);
        });

        // --- 編集機能 ---
        let editMode = false;
        let editingChatId = null;

        const hideAllCancelButtons = () => {
            document.querySelectorAll('.inline-cancel-btn').forEach(btn => btn.style.display = 'none');
        };

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                hideAllCancelButtons();
                const chatMessage = this.closest('.chat-message');
                const chatId = chatMessage.dataset.id;
                const messageText = chatMessage.querySelector('.message-bubble p').innerText;
                const cancelBtn = chatMessage.querySelector('.inline-cancel-btn');

                chatIdInput.value = chatId;
                messageInput.value = messageText;
                sendBtnLabel.textContent = '編集';
                messageInput.focus();
                cancelBtn.style.display = 'inline-block';

                editMode = true;
                editingChatId = chatId;
            });
        });

        document.querySelectorAll('.inline-cancel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.display = 'none';
                chatIdInput.value = '';
                messageInput.value = '';
                sendBtnLabel.textContent = '📩';
                editMode = false;
                editingChatId = null;
            });
        });

        form.addEventListener('submit', function(e) {
            if (editMode && editingChatId) {
                e.preventDefault();
                const url = `/chat/${editingChatId}`;
                const formData = new FormData(form);
                formData.append('_method', 'PUT');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                }).then(res => res.ok ? location.reload() : alert('更新に失敗しました'));
            }
        });

        // --- モーダル機能 ---
        openModalBtn.addEventListener('click', () => modal.style.display = 'block');
        closeModalBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', e => {
            if (e.target === modal) modal.style.display = 'none';
        });

        // --- 星クリックで評価 ---
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.value);
                ratingInput.value = val;
                highlightStars(val);
            });
        });

        function highlightStars(rating) {
            stars.forEach((star, idx) => {
                if (idx < rating) star.classList.add('selected');
                else star.classList.remove('selected');
            });
        }
    });
</script>
@endsection