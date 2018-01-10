<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBestKeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->down();
        DB::statement("create view keywords_best as select keyword,price, premium_clicks, price*premium_clicks as budget from keywords 
                join forecastinfo on forecastinfo.Keywords_id=keywords.id
                join auctionbids on auctionbids.forecastInfo_id=forecastinfo.id and auctionbids.position=1 and is_preceded=1
                order by budget desc
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW keywords_best");
    }
}
