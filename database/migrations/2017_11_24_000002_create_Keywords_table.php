<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateKeywordsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'Keywords';
    /**
     * Run the migrations.
     * @table Keywords
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('keyword');
            $table->integer('impressions_per_month')->nullable();
            $table->integer('ad_group_id')->nullable();
            $table->integer('bid')->nullable();
            $table->integer('context_bid')->nullable();
            $table->integer('search_prices_pf')->nullable();
            $table->integer('search_prices_pb')->nullable();
            $table->integer('search_prices_ff')->nullable();
            $table->integer('search_prices_fb')->nullable();
            $table->integer('min_search_price')->nullable();
            $table->integer('current_search_price')->nullable();
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
