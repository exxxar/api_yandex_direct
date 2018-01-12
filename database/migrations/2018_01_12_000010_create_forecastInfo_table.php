<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateForecastinfoTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'forecastInfo';
    /**
     * Run the migrations.
     * @table forecastInfo
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->double('min')->nullable()->comment('Средневзвешенная цена клика в нижнем блоке на момент составления прогноза.');
            $table->double('max')->nullable()->comment('Средневзвешенная цена клика на первом месте в нижнем блоке на момент составления прогноза.');
            $table->double('premium_min')->nullable()->comment('Средневзвешенная цена клика в спецразмещении на момент составления прогноза.');
            $table->double('premium_max')->nullable()->comment('Средневзвешенная цена клика на первом месте в спецразмещении на момент составления прогноза.');
            $table->integer('shows')->nullable()->comment('Возможное количество показов объявления по данной фразе за прошедший месяц.');
            $table->integer('clicks')->nullable()->comment('Возможное количество кликов по объявлению в нижнем блоке (кроме первого места) за прошедший месяц.');
            $table->integer('first_place_clicks')->nullable()->comment('Возможное количество кликов по объявлению на первом месте в нижнем блоке, за прошедший месяц.');
            $table->integer('premium_clicks')->nullable()->comment('Возможное количество кликов по объявлению в спецразмещении за прошедший месяц.');
            $table->double('ctr')->nullable()->comment('CTR при показе в нижнем блоке, в процентах. Рассчитывается по формуле:

Clicks/Shows * 100');
            $table->double('first_place_ctr')->nullable()->comment('CTR при показе на первом месте в нижнем блоке. Рассчитывается по формуле:

FirstPlaceClicks/Shows * 100');
            $table->double('premium_ctr')->nullable()->comment('CTR при показе в спецразмещении. Рассчитывается по формуле:

PremiumClicks/Shows * 100');
            $table->string('currency', 10)->nullable()->comment('Валюта, в которой выражены цены клика и суммарные затраты в отчете.');
            $table->integer('Keywords_id');
            $table->unsignedTinyInteger('is_preceded');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists($this->set_schema_table);
     }
}
