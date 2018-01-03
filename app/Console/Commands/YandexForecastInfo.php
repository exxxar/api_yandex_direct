<?php

namespace App\Console\Commands;


use App\Forecastinfo;
use App\Http\Controllers\API\YandexApi;

use App\Keywords;

use Biplane\YandexDirect\Exception\ApiException;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\DB;


class YandexForecastInfo extends Command implements YandexDirectConst
{
    use YandexBasicCommand;
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

        error_log($this->restoringPrecede("\"[!квартира !окна !купить +на]\""));
        //$this->doMain();
    }

    public function doMain($reverse = true)
    {

        $this->doStep0();

        $words_from_db = Keywords::where('check', null)
            ->orderBy('id', $reverse ? 'DESC' : 'ASC')
            ->get();

        foreach ($words_from_db as $select_db_word) {
            //говорим что слово использовано
            $kw = Keywords::findOrFail($select_db_word->id);
            $kw->check = True;
            $kw->save();

            try {
                $this->doStep1($select_db_word);//режим сбора
            } catch (ApiException $ae) {
            }

            try {
                $this->doStep2();
            } catch (ApiException $ae) {
            }
        }
    }

    public function doStep1($select_db_word)
    {

        $keyword_for_suggestion = $select_db_word->keyword;

        //получаем список подсказок по слову до тех пор пока это возможно
        while (!empty($suggested_words = $this->api->getKeywordsSuggestion($keyword_for_suggestion))) {

            $this->log->info("Получаем список подсказок по ключевому слову!");
            //проходим циклом по подсказкам, проверяем нет ли слова в бд
            //если нет - добавляем слово в бд
            foreach ($suggested_words as $sw) {
                $this->log->info("Подсказка: $sw");
                $fKw = Keywords::where("keyword", $sw)->first();
                if (empty($fKw)) {
                    Keywords::insertGetId(
                        [
                            'keyword' => $sw,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]
                    );

                    //добавляем слово через разделить чтоб запросить еще больше подсказок
                    $keyword_for_suggestion .= "," . $sw;
                }
            }
        }

    }

    protected function doStep2($reverse = false, $regions = [1])
    {
        $this->log->info("Этап 2 - начало этапа");
        $this->log->info("Берем порциям все ключевые слова, для которых нет соответствия в таблице forecastinfo");
        $keywords_without_forecast = Keywords::whereNotIn('id', function ($query) use ($reverse) {
            $query->select('Keywords_id')
                ->from('forecastinfo')
                ->orderBy('id', $reverse ? 'DESC' : 'ASC');
        })
            ->orderBy('id', $reverse ? 'DESC' : 'ASC')
            ->limit(round(self::MAX_FORECAST / 2))
            ->get();

        if (count($keywords_without_forecast) < round(self::MAX_FORECAST / 2)) {
            $this->log->info("Еще не набралось нужное колличество ключевых слов:" . count($keywords_without_forecast) . "\\" . self::MAX_FORECAST);
            return;
        }

        $this->log->info("Формируем массив слов для создания Forecast отчета");

        $buf = [];
        //в буфер помимо ключевого слова добавляется сразу же его уточненная копия, именно по этой причине
        //мы делим максимально возможное число слов в отчете пополам
        foreach ($keywords_without_forecast as $kw) {
            array_push($buf, $kw->keyword);
            array_push($buf, $this->divideAndPrecede($kw->keyword));
        }

        $this->log->info("Формируем Forecast отчет");
        $this->api->createNewForecast($buf, $regions);
        $this->doRandomInterval();
        while (($reports = $this->api->getForecastList())[0]->getStatusForecast() == "Pending") {
            $this->log->info($reports[0]->getForecastID() . " " . $reports[0]->getStatusForecast());
            $this->doRandomInterval();
        }

        $this->log->info("Получаем информацию из Forecast отчет [" . $reports[0]->getForecastID() . "]");
        $report = $this->api->getForecastInfo($reports[0]->getForecastID());


        foreach ($report->getPhrases() as $wr) {
            //каждый репорт содержит большой набор словоформ

            try {
                $fKwId = Keywords::where("keyword", $this->restoringPrecede($wr->getPhrase()))->first();
                if (empty($fKwId)) {
                    $fKwId->id = Keywords::insertGetId([
                        'keyword' => $wr->getPhrase(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
                //проверка на наличие в бд уже существующих элементов с Keywords_id
                $fcount = Forecastinfo::select('Keywords_id', DB::raw('count(id)'))
                    ->groupBy('Keywords_id')
                    ->where("Keywords_id", $fKwId->id)
                    ->count();
                $this->log->info("Forecast информация по[" . $fKwId->id . "](" . ($fcount == 0 ? "будет добавлена" : "уже есть в колличестве ($fcount)") . ")");
                if (!empty($fKwId) && $fcount == 0) {
                    Forecastinfo::insertGetId(
                        [
                            'min' => $wr->getMin(),
                            'max' => $wr->getMax(),
                            'premium_min' => $wr->getPremiumMin(),
                            'premium_max' => $wr->getPremiumMax(),
                            'shows' => $wr->getShows(),
                            'clicks' => $wr->getClicks(),
                            'first_place_clicks' => $wr->getFirstPlaceClicks(),
                            'premium_clicks' => $wr->getPremiumClicks(),
                            'ctr' => $wr->getCTR(),
                            'first_place_ctr' => $wr->getFirstPlaceCTR(),
                            'premium_ctr' => $wr->getPremiumCTR(),
                            'currency' => $wr->getCurrency(),
                            'Keywords_id' => $fKwId->id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'is_preceded' => strpos($kw->getPhrase(), "!") == False ? 0 : 1
                        ]
                    );
                    $this->log->info("Добавляем информацию для фразы [" . $fKwId->keyword . " ]");
                }


            } catch (\Exception $e) {
                $this->log->error("Не удалось добавить информацию базу: " . $e->getMessage());
            }
        }

        $this->log->info("Удаляем Foreacst отчет [" . $reports[0]->getForecastID() . "]");
        //удаляем репорт
        $this->api->deleteForecastReport($reports[0]->getForecastID());

        $this->log->info("Этап 2 - завершение этапа");
    }

}
