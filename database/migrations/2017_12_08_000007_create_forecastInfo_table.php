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
            $table->double('min')->nullable();
            $table->double('max')->nullable();
            $table->double('premium_min')->nullable();
            $table->double('premium_max')->nullable();
            $table->integer('shows')->nullable();
            $table->integer('clicks')->nullable();
            $table->integer('first_place_clicks')->nullable();
            $table->integer('premium_clicks')->nullable();
            $table->double('ctr')->nullable();
            $table->double('first_place_ctr')->nullable();
            $table->double('premium_ctr')->nullable();
            $table->string('currency', 10)->nullable();
            $table->unsignedInteger('Keywords_id');

            $table->index(["Keywords_id"], 'fk_forecastInfo_Keywords1_idx');
            $table->nullableTimestamps();


            $table->foreign('Keywords_id', 'fk_forecastInfo_Keywords1_idx')
                ->references('id')->on('Keywords')
                ->onDelete('no action')
                ->onUpdate('no action');
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
