<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class YandexForecastInfo extends YandexBasicCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yandex:forecast';

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

    public function __construct(YandexApi $api, Log $log)
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
        //
        $this->doStep0();
    }

    public function doStep1(){

    }


}
