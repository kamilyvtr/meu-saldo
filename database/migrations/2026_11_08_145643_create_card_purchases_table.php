<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_card_id')->constrained('credit_cards')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('total_amount')->comment('Valor total em centavos');
            $table->date('purchase_date');
            $table->unsignedInteger('installments_count')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purchase_date']);
            $table->index(['credit_card_id', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_purchases');
    }
};
