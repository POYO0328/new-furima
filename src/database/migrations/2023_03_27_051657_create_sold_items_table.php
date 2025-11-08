<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoldItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sold_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sending_postcode');
            $table->string('sending_address');
            $table->string('sending_building')->nullable();

            // 評価関連
            $table->tinyInteger('buyer_rating')->nullable();   // 購入者→出品者の評価(1〜5)
            $table->tinyInteger('seller_rating')->nullable();  // 出品者→購入者の評価(1〜5)
            // ステータス
            $table->enum('status', [
                'trading',        // 取引中
                'buyer_rated',    // 購入者が評価済（出品者未評価）
                'completed',      // 双方評価完了
            ])->default('trading');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sold_items');
    }
}
