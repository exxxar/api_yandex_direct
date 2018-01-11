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

        /*      $words_from_db = Keywords::
                  orderBy('id',  'asc')
                  ->limit(10000)
                      ->offset(10000)
                  ->get();*/


        /*        DELETE f
        FROM forecastinfo f
        WHERE f.Keywords_id = 82023*/

        /*
                DELETE a
        FROM auctionbids a
        INNER JOIN forecastinfo f
        ON a.forecastInfo_id = f.id
        WHERE f.Keywords_id = 82023*/

        /*  foreach ($words_from_db  as $kw) {

              $felem = DB::select(
                  "SELECT COUNT(`Keywords_id`) as `cnt` FROM `forecastinfo` WHERE `Keywords_id`=? GROUP by `Keywords_id` ORDER BY  COUNT(`Keywords_id`) DESC",[$kw->id]
              );

              try {
                  if ($felem[0]->cnt > 2) {

                     error_log("=>".$felem[0]->cnt );
                      DB::select(
                          "DELETE a FROM auctionbids a INNER JOIN forecastinfo f ON a.forecastInfo_id = f.id WHERE f.Keywords_id = ?", [$kw->id]
                      );

                      DB::select(
                          "DELETE f FROM forecastinfo f WHERE f.Keywords_id=? ", [$kw->id]
                      );

                  }

              }
              catch(\Exception $e){

              }
          }

          $felem = DB::select(
              "SELECT COUNT(`Keywords_id`) as `cnt` FROM `forecastinfo` WHERE `Keywords_id`=? GROUP by `Keywords_id` ORDER BY  COUNT(`Keywords_id`) DESC",[82023]
          );
          error_log($felem[0]->cnt);
          return;
          //$this->checkLenAndSlice("Content-Type: text/plain");
          // return;*/
        $this->doMain();
    }

    public function doMain($reverse = false)
    {
        $this->doStep0();

        while (true) {
            $words_from_db = Keywords::where('check', null)
                ->orderBy('id', $reverse ? 'DESC' : 'ASC')
                ->limit(300)
                ->get();


            foreach ($words_from_db as $select_db_word) {

                $kw = Keywords::findOrFail($select_db_word->id);
                $kw->check = True;
                $kw->save();
                unset($kw);

                try {
                    $this->doStep1($select_db_word);//режим сбора
                } catch (ApiException $ae) {
                    error_log("[1]=>" . $ae->getMessage() . " [" . $ae->getCode() . "]");
                    if ($ae->getCode() == self::QUERY_LIMIT_EXCEEDED)
                        $this->doStep1_1($select_db_word);
                }

                try {
                    $this->doStep2();
                } catch (ApiException $ae) {
                    error_log("[2]=>" . $ae->getMessage() . " [" . $ae->getCode() . "]");
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


    /*

       SELECT t1.*
       FROM keywords as t1
       LEFT JOIN forecastinfo t2 ON t1.id = t2.Keywords_id
       WHERE t2.id IS NULL

    */

    protected function doStep2($reverse = false, $regions = [1])
    {

        $keywords_without_forecast = DB::select(
            'SELECT t1.*
              FROM keywords as t1
              LEFT JOIN forecastinfo t2 ON t1.id = t2.Keywords_id
             WHERE t2.id IS NULL LIMIT ?', [round(self::MAX_FORECAST / 2)]
        );


        if (count($keywords_without_forecast) < round(self::MAX_FORECAST / 2)) {
            return;
        }

        $buf = [];
        //в буфер помимо ключевого слова добавляется сразу же его уточненная копия, именно по этой причине
        //мы делим максимально возможное число слов в отчете пополам
        foreach ($keywords_without_forecast as $kw) {
            if (strlen(trim($this->checkLenAndSlice($kw->keyword))) > 0) {
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

                error_log("id=" . $fKwId->id . "=>" . $wr->getPhrase());
                if (empty($fKwId)) {

                    $inserted_id = Keywords::insertGetId([
                        'keyword' => $this->restoringPrecede($wr->getPhrase()),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    $fKwId = Keywords::findOne($inserted_id);

                    error_log("в этом моменте у нас нет ключевого слова, но мы пытаемся добавить id " . $fKwId->id);
                }

                $felem = DB::select(
                    "SELECT COUNT(`Keywords_id`) as `cnt` FROM `forecastinfo` WHERE `Keywords_id`=? GROUP by `Keywords_id` ORDER BY  COUNT(`Keywords_id`) DESC", [$fKwId->id]
                );
                if (isset($felem[0]->cnt))
                    if ($felem[0]->cnt>=2)
                        continue;

                error_log(isset($felem[0]->cnt) ? "есть->".$felem[0]->cnt : "нету");

                if (!empty($fKwId) &&  $felem[0]->cnt < 2) {
                    error_log("TEST");
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
