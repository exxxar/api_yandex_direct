<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateAdsearchpostionsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'AdSearchPostions';
    /**
     * Run the migrations.
     * @table AdSearchPostions
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('description')->nullable();
            $table->unsignedInteger('positions');
            $table->integer('search_engine')->nullable();
            $table->tinyInteger('is_ad')->nullable();
            $table->unsignedInteger('region_id');
            $table->unsignedInteger('AdSearchPostions_site_id');
            $table->unsignedInteger('Keywords_id');
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
