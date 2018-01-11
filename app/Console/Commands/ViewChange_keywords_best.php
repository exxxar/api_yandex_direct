<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ViewChange_keywords_best extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sqlview';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::statement("DROP VIEW keywords_best");
        DB::statement("select keyword,price, bid, is_preceded, shows, ctr,  price*shows*ctr as budget from keywords 
                join forecastinfo on forecastinfo.Keywords_id=keywords.id
                join auctionbids on auctionbids.forecastInfo_id=forecastinfo.id and auctionbids.position=4 and is_preceded=1 and price>100
                group by keyword
                order by budget desc 
        ");
    }
}
