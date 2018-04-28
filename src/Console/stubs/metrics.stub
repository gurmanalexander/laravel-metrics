<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

class CreateMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_failed')->default(false);

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on(Config::get('metrics.users_table', 'users'))->onDelete('cascade')->onUpdate('cascade');

            $table->integer('admin_id')->unsigned()->nullable();
            $table->foreign('admin_id')->references('id')->on(Config::get('metrics.users_table', 'users'))->onDelete('set null')->onUpdate('cascade');

            $table->string('type')->index();
            $table->morphs('metricable');

            $table->json('data');
            $table->integer('count')->default(0)->index();
            $table->timestamp('start_at')->default(DB::raw('CURRENT_TIMESTAMP'))->index();
            $table->timestamp('end_at')->nullable()->index();
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
        Schema::dropIfExists('metrics');
    }
}
