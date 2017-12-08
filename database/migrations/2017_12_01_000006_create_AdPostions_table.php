<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateAdpostionsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'AdPostions';
    /**
     * Run the migrations.
     * @table AdPostions
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('url')->nullable();
            $table->string('description')->nullable();
            $table->string('positions')->nullable();
            $table->integer('browser')->nullable();
            $table->unsignedInteger('Keywords_id');

            $table->index(["Keywords_id"], 'fk_AdPostions_Keywords1_idx');
            $table->nullableTimestamps();


            $table->foreign('Keywords_id', 'fk_AdPostions_Keywords1_idx')
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
