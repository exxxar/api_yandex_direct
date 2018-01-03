<?php

namespace App\Console\Commands;

use App\AuctionBids;
use App\CompetitorsBids;
use App\ContextCoverage;
use App\Forecastinfo;
use App\Keywords;
use Biplane\YandexDirect\Exception\ApiException;
use Biplane\YandexDirect\Exception\LogicException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class YandexDirect extends Command implements YandexDirectConst
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yandex {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'type = 1 - выборка статистики непосредственно по словам, type = 0 - выборка всех значений';

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
        //TODO: ПЕРЕДЕЛАТЬ ПОД НОВУЮ БАЗУ ИНАЧЕ ЭТОТ СКРИПТ РАБОТАТЬ НЕ БУДЕТ!!!

        /*
         * Режимы работы:
         * 0) Наполнение базы вводными словами и словосочетаниями из файла
         * 1) Режим сбора слов
         * 2) Режим уточнения слов
         * 3) Режим обновления информации по словам
         * 3.1) Собираем информацию из Forecast
         * 3.2) Собираем информацию из компании
         * 4) Очистка
         * 5) Выгрузка слов в файл
         *
         * Режим 1, этапы:
         *
         * Собираем слова из подсказок и вордстата
         *
         * 1) получаем слово из базы данных
         * 2) отправляем запрос к подсказкам по этому слову
         * 3) добавляем в базу слова из подсказок
         * 4) формируем по этому же слову обращение к вордстату
         * 5) выбираем результаты и заносим в базу данных
         *
         * Режим 2, этапы:
         *
         * запускаем аналогичные действия как в первом этапе, но теперь предваряем все слова знаком !
         *
         * Режим 3.1, этапы:
         *
         * 1) выбираем 100 слов из бд и формируем Forecast отчет
         * 2) ожидаем готовности отчета,
         * 3) выбираем данные по словам и заносим в соответствующую таблицу
         *
         * Режим 3.2, этапы:
         *
         * 1) выбираем 200 слов из бд и формируем группу в рекламной компании
         * 2) получаем данные о ставках и заносим в таблицу
         *
         *
         * Старые этапы:
         * 0)Пока в бд нет слов - берем слова из файла, закончились слова из файла - берем слова из бд
         * 1)создание запроса к вордстату
         * 2)выбор всех схожих слов (без минус фраз)
         * 3)удаление всех групп в кампании
         * 4)создание группы
         * 5)добавление всех слов из отчета в запрос
         * 6)запрос ставок на слова группы
         * 7)обновление данных в бд с учетом инфы из предидущего запроса
         *
         * лимит кампаний: 3000
         * лимит групп в кампании: 10000
         * лимит слов в группе: 200
         * лимит слов в Forecast: 100
         *
         */

        switch ($this->argument('type')) {
            default:
            case 'prepare':
                    $this->doStep0("log_db2.txt",true);
                break;
            case 'start':
                $this->doMain();
                break;
            case 'start:reverse':
                $this->doMain(true);
                break;
            case 'reset:test':
                error_log("test");
                break;
            case 'reset:campaings':
                $this->doResetCampaings();
                break;
            case 'reset:checks':
                $this->doResetChecks();
                break;
        }

    }


    public function doMain($reverse=false)
    {
        $this->doStep0();

        $words_from_db = Keywords::where('check', null)
            ->orderBy('id', $reverse?'DESC':'ASC')
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
                $this->doStep1($select_db_word, 1); //режим уточнения
            } catch (ApiException $ae) {
            }

            try {
                $this->doStep3_1($reverse);//режим выбора данных из forecast
            } catch (ApiException $ae) {
            }

            try {
                //если балы есть или время ожидания прошло, тогда выполняем этап, требующий балы
                //иначе мы крашимся, ставим время ожидания и просто переходим к выполнению следующего цикла

                if ($this->api->getUnits()->getRest() > 0 || $this->api->checkUnitsTime())
                    $this->doStep3_2();//режим выбора данных из кампаний
                else {
                    $this->log->error("Количество оставшихся баллов:" . $this->api->getUnits()->getRest() . " Осталось времени:" . $this->api->getRefreshUnitsTime());
                }
            } catch (ApiException $ae) {
                $this->log->error("Ошибка количества баллов:" . $ae->getTraceAsString() . " Продолжаем работу в режиме сбора слов!");
                $this->api->updateUnitsTime();
            }
            $this->doFinalCheck();
        }

    }



    /*
    1) получаем слово из базы данных
    2) отправляем запрос к подсказкам по этому слову
    3) добавляем в базу слова из подсказок
    4) формируем по этому же слову обращение к вордстату
    5) выбираем результаты и заносим в базу данных
    */
    protected function doStep1($select_db_word, $mode = 0, $region = [1])
    {
        $this->log->info($mode == 0 ? "Этап 1 - начало работы" : "Этап 2 - начало работы");
        $this->log->info("Выбор слова [" . $select_db_word->keyword . "] из БД");
        if ($mode == 0) {
            //получаем список подсказок по слову
            $suggested_words = $this->api->getKeywordsSuggestion($select_db_word->keyword);
            $this->log->info("Получаем список подсказок по ключевому слову!");
            //проходим циклом по подсказкам, проверяем нет ли слова в бд
            //если нет - добавляем слово в бд
            foreach ($suggested_words as $sw) {
                $this->log->info("Подсказка: $sw");
                $fKw = Keywords::where("keyword", $sw)->first();
                if (empty($fKw))
                    Keywords::insertGetId(
                        [
                            'keyword' => $sw,
                            'add_date' => Carbon::now(),
                            'check_date' => Carbon::now()
                        ]
                    );
            }
        }

        //обращаемся к вордстату по выбранному слову
        $this->api->createNewWordstatReport($region,
            [$mode == 0 ?
                $select_db_word->keyword :
                $this->divideAndPrecede($select_db_word->keyword)
            ]);

        $this->doRandomInterval();
        //ждём завершение формирование отчета вордстата
        while (($reports = $this->api->getWordstatReportList())[0]->getStatusReport() == "Pending") {
            $this->log->info($reports[0]->getReportID() . " " . $reports[0]->getStatusReport());
            $this->doRandomInterval();
        }
        //берем репорт по айди
        $report = $this->api->getWordstatReport($reports[0]->getReportID());

        $this->log->info("Выбираем слова из wordstat отчета:");
        foreach ($report as $wr) {
            //каждый репорт содержит большой набор словоформ
            foreach ($wr->getSearchedWith() as $sw) {
                $this->log->info($sw->getPhrase() . " " . $sw->getShows());
                try {
                    $fKwId = Keywords::where("keyword", $sw->getPhrase())->first();
                    if (empty($fKwId))
                        Keywords::insertGetId(
                            [
                                'keyword' => $sw->getPhrase(),
                                'impressions_per_month' => $sw->getShows(),
                                'add_date' => Carbon::now(),
                                'check_date' => Carbon::now()
                            ]
                        );
                    else {
                        $fKw = Keywords::find($fKwId->id);
                        $this->log->info("Слово [" . $fKw->keyword . "] найдено в бд , обновляем информацию!");
                        $fKw->impressions_per_month = $sw->getShows();
                        $fKw->save();
                    }

                } catch (\Exception $e) {
                    $this->log->error("Не удалось добавить слово в базу: " . $e->getMessage());
                }
            }
        }

        //удаляем текущий репорт
        $this->api->deleteWordstatReport($reports[0]->getReportID());
        $this->log->info("Удаляем wordstat " . $reports[0]->getReportID() . " отчет");
        $this->log->info($mode == 0 ? "Этап 1 - завершение работы" : "Этап 2 - завершение работы");
    }


    /*
     * Режим 3.1, этапы:         *
     * 1) выбираем 100 слов из бд и формируем Forecast отчет
     * 2) ожидаем готовности отчета,
     * 3) выбираем данные по словам и заносим в соответствующую таблицу
     *
         select * from `keywords`
         where not exists (select * from `forecastinfo` where `keywords`.`id`=`forecastinfo`.`Keywords_id`)

         SELECT *  FROM `keywords` WHERE `id` NOT IN (SELECT `Keywords_id` FROM `forecastinfo` )

        SELECT COUNT(`id`), `Keywords_id`
                FROM `forecastinfo`  WHERE `Keywords_id`=1
                GROUP BY `Keywords_id`
                ORDER BY COUNT(`id`) DESC
*/
    protected function doStep3_1($reverse=false,$regions = [1])
    {
        $this->log->info("Этап 3.1 - начало этапа");
        $this->log->info("Берем порциям все ключевые слова, для которых нет соответствия в таблице forecastinfo");
        $keywords_without_forecast = Keywords::whereNotIn('id', function ($query) use($reverse) {
            $query->select('Keywords_id')
                ->from('forecastinfo')
                ->orderBy('id', $reverse?'DESC':'ASC');
        })
            ->orderBy('id', $reverse?'DESC':'ASC')
            ->limit(self::MAX_FORECAST)
            ->get();

        if (count($keywords_without_forecast) < self::MAX_FORECAST) {
            $this->log->info("Еще не набралось нужное колличество ключевых слов:" . count($keywords_without_forecast) . "\\" . self::MAX_FORECAST);
            return;
        }

        $this->log->info("Формируем массив слов для создания Forecast отчета");

        $buf = [];
        foreach ($keywords_without_forecast as $kw) {
            array_push($buf, $kw->keyword);
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
                $fKwId = Keywords::where("keyword", $wr->getPhrase())->first();
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

        $this->log->info("Этап 3.1 - завершение этапа");
    }

    protected function doStep3_2()
    {

        $this->log->info("Этап 3.2 - начало этапа");
        //если ключевых слов без групп не набралось, то переходим к следующему этапу
        if (Keywords::where('ad_group_id', null)->count() < self::MAX_KEYWORDS)
            return;


        $this->log->info("Выбираем из бд " . self::MAX_KEYWORDS . " ключевых слов");
        //выбираем из бд 199 слов (лимит 200)
        $keywords_for_group = Keywords::where('ad_group_id', null)
            ->limit(self::MAX_KEYWORDS)
            ->get();

        //можем тут сделать создание компаний только если заполнена предидущая компания
        //компания может содержать 9999 объектов  групп

        $idCampaign = $idGroup = $companies = $groups = $kw_in_groups = null;

        $groups = Keywords::select('ad_group_id', 'campaing_id', DB::raw('count(ad_group_id)'))
            ->groupBy('ad_group_id', 'campaing_id')
            ->havingRaw("count(ad_group_id) <= " . self::MAX_GROUPS . " and count(ad_group_id) >=1")
            ->orderBy('count(ad_group_id)', 'desc')
            ->first();

        // $this->log->info("Групп в компании => ".$groups["count(ad_group_id)"]);
        if (!empty($groups) && $groups["count(ad_group_id)"] < self::MAX_GROUPS) {
            //пока в компании групп меньше чем лимит, берем айди этой компании

            $idCampaign = $groups->campaing_id;

            $kw_in_groups = Keywords::select('ad_group_id', DB::raw('count(keyword_id)'))
                ->groupBy('ad_group_id')
                ->where("ad_group_id", $groups["ad_group_id"])
                ->first();
        } else {
            //если кампаний нет или нет подходящей компании, то создаем
            try {
                $this->log->info("Создаем новую компанию");
                $idCampaign = $this->api->addCampain("test company " . ((new Carbon())->now()))
                    ->getAddResults()[0]
                    ->getId();
            } catch (LogicException $exception) {
                $this->log->error($exception->getTraceAsString());
            }
        }


        if (!empty($kw_in_groups) && $kw_in_groups["count(keyword_id)"] < self::MAX_KEYWORDS) {
            //если найдена подходящая группа, то берем её айди и не создаем новый запрос к апи
            $idGroup = $kw_in_groups->ad_group_id;
            $this->log->info("Берем группу в из компании");
        } else {
            //добавляем в команию группу
            try {

                $idGroup = $this->api->addGroup("group_to_campaign_$idCampaign-" . ((new Carbon())->now()), $idCampaign, [1])
                    ->getAddResults()[0]
                    ->getId();

                //создаем само обновление и добавляем в него группу
                $this->api->createAds($idGroup);

                $this->log->info("Добавляем группу [$idGroup] в комапнию [$idCampaign]");
            } catch (LogicException $exception) {
                $this->log->error($exception->getTraceAsString());

            }

        }

        //формируем слова для массива, который затем будет дбавлен в группу
        $buf = array();
        foreach ($keywords_for_group as $kwfg) {
            //array_push($buf,$kwfg->keyword);
            //сохраняем айдишник группы - своеобразный флаг чтоб в следующий раз не выбирать слова, у которых уже есть айди группы
            $kwfg->ad_group_id = $idGroup;
            $kwfg->campaing_id = $idCampaign;
            $kwfg->save();
            try {
                $this->log->info("Ключевое слово, добавляемое в группу " . $kwfg->keyword);

                $item = $this->api->addKeyword_item($idGroup, $kwfg->keyword, False);
                array_push($buf, $item);
            } catch (LogicException $exception) {
                $this->log->error($exception->getTraceAsString());
                continue;
            }
        }

        if (empty($buf)) {
            $this->log->error("Содержимое массива ключевых слов отсутствует!");
            return;
        }
        $keywordsIds = null;

        try {
            $this->log->info("Посылаем запрос по ключевым словам на " . count($buf) . " элементов, в группе $idGroup");
            $keywordsIds = $this->api->doKeywordRequest($buf)->getAddResults();//тут получаем добавленные идентификаторы ключевых слов
            //по идее тут мы должны добавить их в бд, чтоб было проще добавлять цены по айди ключевого слова, а не только группы

            //ставим в соответствие каждому вернувшемуся айдишнику выбранное из бд слово
            $bidsArray = array();
            for ($i = 0; $i < count($keywordsIds); $i++) {
                $ki = $keywordsIds[$i]->getId();
                $keywords_for_group[$i]->keyword_id = $ki;
                $keywords_for_group[$i]->save();
                $this->log->info("Создаем Bids Item для $ki");
                array_push($bidsArray, $this->api->createBidsItem($ki));
            }
            $this->log->info("Посылаем BidsRequest");
            $this->api->doBidsRequest($bidsArray);
        } catch (ApiException $exception) {
            $this->log->error($exception->getMessage());
            return;
        }
        //запрос к Bid-ам
        $this->doRandomInterval();
        $bidData = null;

        try {
            $this->log->info("Получаем информацию о ценах на слова для $idGroup группы");
            $bidData = $this->api->getBidData($idGroup);
        } catch (LogicException $exception) {
            $this->log->error($exception->getTraceAsString());
            return;
        }

        $this->log->info("Количество результатов => " . count($bidData->getBids()));

        if (is_array($bidData->getBids()))
            foreach ($bidData->getBids() as $bid) {
                $kwwb = Keywords::where('keyword_id', $bid->getKeywordId())->first();

                $this->log->info("Добавляем информацию по ценам для слова => " . $kwwb->keyword . "[" . $bid->getKeywordId() . "]");

                $kwwb->bid = $bid->getBid() != null ? $bid->getBid() / 1000000 : null;
                $kwwb->context_bid = $bid->getContextBid() != null ? $bid->getContextBid() / 1000000 : null;

                $this->log->info("Кол-во CompetitorsBids=>" . count($bid->getCompetitorsBids()));
                if ($bid->getCompetitorsBids() !== null) {
                    $this->log->info("Доступно CompetitorsBids");
                    foreach ($bid->getCompetitorsBids() as $cb) {
                        //добавляем инфу в CompetitorsBids
                        $cmb = new CompetitorsBids();
                        $cmb->element = $cb / 1000000;
                        $cmb->Keywords_id = $kwwb->id;
                        $cmb->save();
                    }
                }

                $this->log->info("Кол-во SearchPrices=>" . count($bid->getSearchPrices()));
                if ($bid->getSearchPrices() !== null) {
                    $this->log->info("Доступно SearchPrices");
                    $kwwb->search_prices_pf = $bid->getSearchPrices()[0]->getPrice() / 1000000;
                    $kwwb->search_prices_pb = $bid->getSearchPrices()[1]->getPrice() / 1000000;
                    $kwwb->search_prices_ff = $bid->getSearchPrices()[2]->getPrice() / 1000000;
                    $kwwb->search_prices_fb = $bid->getSearchPrices()[3]->getPrice() / 1000000;

                }

                $this->log->info("Кол-во ContextCoverage=>" . count($bid->getContextCoverage()));
                if ($bid->getContextCoverage() !== null) {
                    if (is_array($bid->getContextCoverage()->getItems()))
                        foreach ($bid->getContextCoverage()->getItems() as $item) {
                            $kwcc = new ContextCoverage();
                            $kwcc->probability = intval($item->getProbability());
                            $kwcc->price = $item->getPrice() / 1000000;
                            $kwcc->Keywords_id = $kwwb->id;
                            $kwcc->save();
                        }
                }

                $this->log->info("Кол-во AuctionBids=>" . count($bid->getAuctionBids()));
                if ($bid->getAuctionBids() !== null) {
                    foreach ($bid->getAuctionBids() as $ab) {

                        $kwab = new AuctionBids();
                        $kwab->position = $ab->getPosition();
                        $kwab->bid = $ab->getBid() / 1000000;
                        $kwab->price = $ab->getPrice() / 1000000;
                        $kwab->Keywords_id = $kwwb->id;
                        $kwab->save();

                    }

                }

                $kwwb->min_search_price = $bid->getMinSearchPrice() / 1000000;
                $kwwb->current_search_price = $bid->getCurrentSearchPrice() / 1000000;
                $kwwb->check_date = (new Carbon())->now();
                $kwwb->save();

                $this->log->info("Bid=>" . $kwwb->bid . "\nContextBid=>" . $kwwb->context_bid . "\nMinSearchPrice=>" . $kwwb->min_search_price);
            }
        $this->log->info("Этап 3.2 - завершение этапа");
    }









}
