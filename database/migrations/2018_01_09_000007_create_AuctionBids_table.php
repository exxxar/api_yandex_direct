<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateAuctionbidsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'AuctionBids';
    /**
     * Run the migrations.
     * @table AuctionBids
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('position');
            $table->float('bid')->nullable();
            $table->float('price')->nullable();
            $table->integer('forecastInfo_id')->unsigned()->index();

            $table->index(["forecastInfo_id"], 'fk_AuctionBids_forecastInfo1_idx');
            $table->nullableTimestamps();

            $table->foreign('forecastInfo_id', 'fk_AuctionBids_forecastInfo1_idx')
                ->references('id')->on('forecastInfo')
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
