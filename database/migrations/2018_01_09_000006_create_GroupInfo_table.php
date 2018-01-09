<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateGroupinfoTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'GroupInfo';
    /**
     * Run the migrations.
     * @table GroupInfo
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->bigInteger('keyword_id_in_group')->nullable();
            $table->bigInteger('impressions_per_month')->nullable();
            $table->bigInteger('campaing_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->integer('bid')->nullable();
            $table->integer('context_bid')->nullable();
            $table->integer('search_prices_pf')->nullable();
            $table->integer('search_prices_pb')->nullable();
            $table->integer('search_prices_ff')->nullable();
            $table->integer('search_prices_fb')->nullable();
            $table->integer('min_search_price')->nullable();
            $table->integer('current_search_price')->nullable();
            $table->unsignedInteger('Keywords_id');

            $table->index(["Keywords_id"], 'fk_GroupInfo_Keywords1_idx');
            $table->nullableTimestamps();


            $table->foreign('Keywords_id', 'fk_GroupInfo_Keywords1_idx')
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
