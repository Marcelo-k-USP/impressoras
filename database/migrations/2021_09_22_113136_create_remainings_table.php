<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemainingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remainings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            $table->string('user');
            $table->foreignId('rule_id')->constrained();

            $table->integer('remaining');

            /* Possibitar que o usuário solicite quota extra */
            $table->integer('extra_quota')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remainings');
    }
}
