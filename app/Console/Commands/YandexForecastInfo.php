<?php

namespace App\Console\Commands;


use App\AuctionBids;
use App\Forecastinfo;
use App\Http\Controllers\API\YandexApi;
use App\Keywords;
use Biplane\YandexDirect\Exception\ApiException;
use Dompdf\Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class YandexForecastInfo extends Command implements YandexDirectConst
{
    use YandexBasicCommand;

    protected $log;
    protected $api;
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
        $this->api = $api;
        $this->log = $log;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->doMain();
    }

    public function doMain($reverse = true)
    {
        $this->doStep0();

        while (true) {
            $words_from_db = Keywords::where('check', null)
                ->orderBy('id', $reverse ? 'DESC' : 'ASC')
                ->get();


            foreach ($words_from_db as $select_db_word) {

                $kw = Keywords::findOrFail($select_db_word->id);
                $kw->check = True;
                $kw->save();
                unset($kw);

                try {
                    $this->doStep1($select_db_word);//режим сбора
                } catch (ApiException $ae) {
                    if ($ae->getCode()==self::QUERY_LIMIT_EXCEEDED)
                        $this->doStep1_1($select_db_word);
                }

                try {
                    $this->doStep2();
                } catch (ApiException $ae) {
                    error_log("[2]=>" . $ae->getMessage());
                }


            }
            $this->doResetChecksIfForecastNotFound();

            if (count($words_from_db) <= 0)
                break;
        }

        unset($words_from_db);
    }

    public function doStep1($select_db_word)
    {
        //получаем список подсказок по слову до тех пор пока это возможно
        $suggested_words = $this->api->getKeywordsSuggestion($this->checkLenAndSlice($select_db_word->keyword));

        $this->log->info("Получаем список подсказок по ключевому слову!");
        //проходим циклом по подсказкам, проверяем нет ли слова в бд
        //если нет - добавляем слово в бд
        foreach ($suggested_words as $sw) {
            $fKw = Keywords::where("keyword", $this->checkLenAndSlice($sw))->first();
            if (empty($fKw)) {
                Keywords::insertGetId(
                    [
                        'keyword' => $this->checkLenAndSlice($sw),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]
                );
            }
            unset($fKw);
        }

        unset($suggested_words);
    }

    protected function doStep2($reverse = false, $regions = [1])
    {
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

        $buf = [];
        //в буфер помимо ключевого слова добавляется сразу же его уточненная копия, именно по этой причине
        //мы делим максимально возможное число слов в отчете пополам
        foreach ($keywords_without_forecast as $kw) {
            if (strlen(trim($kw->keyword)) > 0) {
                array_push($buf, $this->checkLenAndSlice($kw->keyword));
                array_push($buf, $this->divideAndPrecede($this->checkLenAndSlice($kw->keyword)));
            }
        }

        $this->api->createNewForecast($buf, $regions);
        unset($keywords_without_forecast);
        unset($buf);

        $this->doRandomInterval();
        while (($reports = $this->api->getForecastList())[0]->getStatusForecast() == "Pending") {
            $this->doRandomInterval();
        }

        $report = $this->api->getForecastInfo($reports[0]->getForecastID());


        foreach ($report->getPhrases() as $wr) {
            //каждый репорт содержит большой набор словоформ
            try {
                $fKwId = Keywords::where("keyword", $this->restoringPrecede($wr->getPhrase()))->first();
                if (empty($fKwId)) {

                    $inserted_id = Keywords::insertGetId([
                        'keyword' => $this->restoringPrecede($wr->getPhrase()),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    $fKwId = Keywords::findOne($inserted_id);

                    error_log("в этом моменте у нас нет ключевого слова, но мы пытаемся добавить id " . $fKwId->id);
                }

                //SELECT COUNT(`Keywords_id`), `Keywords_id` FROM forecastinfo WHERE `Keywords_id`=1

                $fcount = Forecastinfo::select('Keywords_id', DB::raw('count(Keywords_id)'))
                    ->groupBy('Keywords_id')
                    ->where("Keywords_id", $fKwId->id)
                    ->count();

                if (!empty($fKwId) && $fcount < 2) {

                    $forecastInfoId = Forecastinfo::insertGetId(
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
                            'updated_at' => Carbon::now(),
                            'created_at' => Carbon::now(),
                            'is_preceded' => strpos($wr->getPhrase(), "!") == False ? False : True,
                        ]
                    );


                    foreach ($wr->getAuctionBids() as $ab) {
                        $pos = 0;

                        switch (strtoupper($ab->Position)) {
                            case 'P11':
                                $pos = 1;
                                break;
                            case 'P12':
                                $pos = 2;
                                break;
                            case 'P13':
                                $pos = 3;
                                break;
                            case 'P14':
                                $pos = 4;
                                break;
                            case 'P21':
                                $pos = 5;
                                break;
                            case 'P22':
                                $pos = 6;
                                break;
                            case 'P23':
                                $pos = 7;
                                break;
                            case 'P24':
                                $pos = 8;
                                break;
                        }

                        AuctionBids::insertGetId(
                            [
                                'position' => $pos,
                                'bid' => $ab->Bid,
                                'price' => $ab->Price,
                                'forecastInfo_id' => $forecastInfoId,
                                'updated_at' => Carbon::now(),
                            ]
                        );
                    }
                    unset($forecastInfoId);
                }


            } catch (\Exception $e) {

            }

            unset($fKwId);
            unset($fcount);
        }
        unset($report);
        //удаляем репорт
        $forecast_reports = $this->api->getForecastList();
        foreach ($forecast_reports as $report) {
            $this->api->deleteForecastReport($report->getForecastID());
            $this->doRandomInterval();
        }
        unset($forecast_reports);
    }

    public function doStep1_1($select_db_word, $region = [1])
    {
        //обращаемся к вордстату по выбранному слову
        $this->api->createNewWordstatReport($region,
               $this->checkLenAndSlice($select_db_word->keyword));

        $this->doRandomInterval();
        //ждём завершение формирование отчета вордстата
        while (($reports = $this->api->getWordstatReportList())[0]->getStatusReport() == "Pending") {
            $this->doRandomInterval();
        }

        //берем репорт по айди
        $report = $this->api->getWordstatReport($reports[0]->getReportID());
        foreach ($report as $wr) {
            //каждый репорт содержит большой набор словоформ
            foreach ($wr->getSearchedWith() as $sw) {
                $fKwId = Keywords::where("keyword", $sw->getPhrase())->first();
                if (empty($fKwId))
                    Keywords::insertGetId(
                        [
                            'keyword' => $sw->getPhrase(),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]
                    );

                unset($fKwId);
            }
        }
        unset($report);
        //удаляем текущий репорт
        $this->api->deleteWordstatReport($reports[0]->getReportID());
    }

}
