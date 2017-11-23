<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApiDirectController;
use Illuminate\Console\Command;

use Illuminate\Support\Carbon;
use Illuminate\Contracts\Logging\Log;


class YandexDirect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yandex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'collects statistics data';

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
        ApiDirectController::main();
        error_log('Some message here.');
    }
}
