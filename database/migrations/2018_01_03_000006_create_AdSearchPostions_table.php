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
            $table->unsignedInteger('Url_id');
            $table->unsignedInteger('Keywords_id');

            $table->index(["Keywords_id"], 'fk_AdSearchPostions_Keywords1_idx');

            $table->index(["Url_id"], 'fk_AdSearchPostions_Url1_idx');
            $table->nullableTimestamps();


            $table->foreign('Url_id', 'fk_AdSearchPostions_Url1_idx')
                ->references('id')->on('Url')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('Keywords_id', 'fk_AdSearchPostions_Keywords1_idx')
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
