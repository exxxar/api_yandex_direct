<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\YandexApi;
use App\Http\Controllers\ApiDirectController;
use Illuminate\Console\Command;

use Illuminate\Support\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\File;


class YandexDirect extends Command
{
    private $api;

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
    public function __construct(YandexApi $api)
    {
        $this->api = $api;
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
         * Этапы:
         * 0)Пока в бд нет слов - берем слова из файла, закончились слова из файла - берем слова из бд
         * 1)создание запроса к вордстату
         * 2)выбор всех схожих слов (без минус фраз)
         * 3)удаление всех групп в кампании
         * 4)создание группы
         * 5)добавление всех слов из отчета в запрос
         * 6)запрос ставок на слова группы
         * 7)обновление данных в бд с учетом инфы из предидущего запроса
         */
        $contents = Storage::disk('public')->get('words.txt');


    }
}
