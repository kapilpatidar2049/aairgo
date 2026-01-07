<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAppSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_app_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('transport_type');
            $table->string('service_type');
            $table->string('icon_types_for')->nullable(0);
            $table->integer('order_by')->nullable();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('mobile_menu_icon')->nullable();
            $table->string('mobile_menu_cover_image')->nullable();
            $table->boolean('active')->default(false);
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
        Schema::dropIfExists('mobile_app_settings');
    }
}
