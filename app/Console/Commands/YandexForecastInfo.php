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

                try {
                    $this->doStep1($select_db_word);//режим сбора
                } catch (ApiException $ae) {

                }

                try {
                    $this->doStep2();
                } catch (ApiException $ae) {

                }


            }
            $this->doResetChecksIfForecastNotFound();

            if (count($words_from_db)<=0)
                break;
        }
    }

    public function doStep1($select_db_word)
    {
        //получаем список подсказок по слову до тех пор пока это возможно
        $suggested_words = $this->api->getKeywordsSuggestion($select_db_word->keyword);

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

                    $inserted_id = Keywords::insertGetId([
                        'keyword' => $this->restoringPrecede($wr->getPhrase()),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                    $fKwId = Keywords::findOne($inserted_id);

                    error_log("в этом моменте у нас нет ключевого слова, но мы пытаемся добавить id " . $fKwId->id);
                }

                if (!empty($fKwId) ) {

                    $forecastInfo = new Forecastinfo();
                    $forecastInfo->min = $wr->getMin();
                    $forecastInfo->max = $wr->getMax();
                    $forecastInfo->premium_min = $wr->getPremiumMin();
                    $forecastInfo->premium_max = $wr->getPremiumMax();
                    $forecastInfo->shows = $wr->getShows();
                    $forecastInfo->clicks = $wr->getClicks();
                    $forecastInfo->first_place_clicks = $wr->getFirstPlaceClicks();
                    $forecastInfo->premium_clicks = $wr->getPremiumClicks();
                    $forecastInfo->ctr = $wr->getCTR();
                    $forecastInfo->first_place_ctr = $wr->getFirstPlaceCTR();
                    $forecastInfo->premium_ctr = $wr->getPremiumCTR();
                    $forecastInfo->currency = $wr->getCurrency();
                    $forecastInfo->Keywords_id = $fKwId->id;
                    $forecastInfo->created_at = Carbon::now();
                    $forecastInfo->updated_at = Carbon::now();
                    $forecastInfo->is_preceded = strpos($wr->getPhrase(), "!") == False ? False : True;
                    $forecastInfo->save();


                    foreach ($wr->getAuctionBids() as $ab) {
                        $pos = 0;

                        switch (strtoupper($ab->Position)) {
                            case 'P11':
                                $pos = 1;
                                break;
                            case 'P12':
                                $pos = 2;
                                break;
                            case 'P21':
                                $pos = 3;
                                break;
                            case 'P22':
                                $pos = 4;
                                break;
                        }
                        $auctionBids = new AuctionBids();

                        $auctionBids->position = $pos;
                        $auctionBids->bid = $ab->Bid;
                        $auctionBids->price = $ab->Price;
                        $auctionBids->Keywords_id = $fKwId->id;
                        $auctionBids->updated_at = Carbon::now();
                        $auctionBids->save();
                    }

                }


            } catch (\Exception $e) {

            }
        }
        //удаляем репорт
        $this->api->deleteForecastReport($reports[0]->getForecastID());

    }

}
