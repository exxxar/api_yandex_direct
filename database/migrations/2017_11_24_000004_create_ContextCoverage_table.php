<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateContextcoverageTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $set_schema_table = 'ContextCoverage';
    /**
     * Run the migrations.
     * @table ContextCoverage
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('probability')->nullable();
            $table->integer('price')->nullable();
            $table->unsignedInteger('Keywords_id');
            $table->dateTime('updated_at')->nullable();

            $table->index(["Keywords_id"], 'fk_ContextCoverage_Keywords_idx');


            $table->foreign('Keywords_id', 'fk_ContextCoverage_Keywords_idx')
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
