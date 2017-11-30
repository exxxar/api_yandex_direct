<?php

namespace App\Console\Commands;

use App\AuctionBids;
use App\CompetitorsBids;
use App\ContextCoverage;
use App\Http\Controllers\API\YandexApi;
use App\Http\Controllers\ApiDirectController;
use App\Keywords;
use Biplane\YandexDirect\Api\V5\Contract\DictionaryNameEnum;
use Biplane\YandexDirect\Exception\LogicException;
use Illuminate\Console\Command;

use Illuminate\Support\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\File;


class YandexDirect extends Command
{
    const MAX_CAMPAINGS = 2999;
    const MAX_GROUPS = 9999;
    const MAX_KEYWORDS = 199;

    private $api;

    private $log;

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
        $yandex_type = $this->argument('type');
        error_log($yandex_type);



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
     *
     * лимит кампаний: 3000
     * лимит групп в кампании: 10000
     * лимит слов в группе: 200
     */

        if ($this->argument("type")=="reset") {
            //повторно отмечаем все слова не проверенными, чтоб повторно пройтись по ним и заполнить группы
            $words = Keywords::where('check', 1)->get();
            foreach ($words as $w){
                if ($w->impressions_per_month==null){
                    $w->check = null;
                    $w->keyword_id = null;
                    $w->campaing_id = null;
                    $w->ad_group_id = null;
                    $w->save();
                }
            }

            $this->log->info('Все слова успешно сброшены!');
           // $yandex_type = 0;
            return;

        }
        //очистка чек-ов и по новой пройтись с фильтром [!]
        if ($this->argument("type")=="11") {
            //повторно отмечаем все слова не проверенными, чтоб повторно пройтись по ним и заполнить группы
            $words = Keywords::where('check', 1)->get();
            foreach ($words as $w){
                if ($w->impressions_per_month==null){
                    $w->check = null;
                    $w->save();
                }
            }

            $this->log->info('Все check у слов успешно сброшены!');
            $yandex_type = 1;
        }

        $this->log->info('Процесс получения информация из Yandex.Direct начался.');

        if (Keywords::count() == 0) {
            $this->log->info("В таблице 'Keywords' отсутствуют слова, получаем их из файла");
            $contents = Storage::disk('public')->get('words.txt');
            $contents = preg_split('/[\s,]+/', $contents);
            $index = 0;
            foreach ($contents as $c) {
                $index++;
                if (trim($c)) {
                    $this->log->info($index . "->" . $c);
                    $id = 0;
                    try {
                        $id = Keywords::insertGetId(
                            [
                                'keyword' => trim($c),
                                'add_date' => Carbon::now(),
                                'check_date' => Carbon::now()
                            ]
                        );
                    } catch (\Exception $e) {
                        $this->log->error("Ну удалось добавить слово в базу: " . $e->getMessage());
                    }

                }
            }
        }


        //выбираем слово из бд
        //часть 1: по 1 слов за 1 отчет
        $words = Keywords::where('check', null)->get();
        foreach ($words as $w) {

            //говорим что слово использовано
            $kw = Keywords::findOrFail($w->id);
            $kw->check = True;
            $kw->save();

            $this->log->info("Выбор слова " . $w->keyword . " из БД");

            //TODO:тут нужно выбирать слова, чьи сроки check_date+1 месяц меньше текущей даты

            if ($yandex_type == 0) {
                $this->api->createNewWordstatReport([1], [$w->keyword]);
            } else {
                $buf = explode(' ', $w->keyword);
                $splited_keywords = "\"[";
                foreach ($buf as $b) {
                    if (strlen($b)<=0)
                        continue;
                    //если слово идёт с "+", то это и следующие слова идут без "!"
                    //$splited_keywords .= (strrpos(trim($b), "+")===False ?"!$b " :"$b " );
                    $splited_keywords .= (strrpos(trim($b), "+")===False ?" !$b " :str_replace('+',' ',$b) );
                }
                $splited_keywords .= "]\"";
                $this->api->createNewWordstatReport([1], [$splited_keywords]);
            }

            $this->log->info("Создание вордстат репорта  по слову " . $w->keyword . ". Засыпаем на 10 сек.");
            sleep(10);
            $reports = $this->api->getWordstatReportList();

            //ждём завершение формирование отчета вордстата
            while (($reports = $this->api->getWordstatReportList())[0]->getStatusReport() == "Pending") {
                $this->log->info("засыпаем");
                $this->log->info($reports[0]->getReportID() . " " . $reports[0]->getStatusReport());
                sleep(5);
            }

            foreach ($reports as $w) {
                //берем репорт по айди
                $report = $this->api->getWordstatReport($w->getReportID());

                foreach ($report as $wr) {
                    foreach ($wr->getSearchedWith() as $sw) {


                        try {
                            $fKwId = Keywords::where("keyword",$sw->getPhrase())->first();
                            if (empty($fKwId))
                                Keywords::insertGetId(
                                    [
                                        'keyword' => $sw->getPhrase(),
                                        'impressions_per_month' => $sw->getShows(),
                                        'add_date' => Carbon::now(),
                                        'check_date' => Carbon::now()
                                    ]
                                );
                            else
                            {
                                $fKw = Keywords::find($fKwId->id);
                                $fKw->impressions_per_month = $sw->getShows();
                                $fKw->save();
                            }

                        } catch (\Exception $e) {
                            $this->log->error("Не удалось добавить слово в базу: " . $e->getMessage());
                        }
                        $this->log->info($sw->getPhrase() . " " . $sw->getShows());

                    }
                }

                //удаляем текущий репорт
                $this->api->deleteWordstatReport($w->getReportID());

                //если необработанных слов с ad_group_id==null  в бд меньше 199, то возвращаемся к предидущей итерации и набиваем базу слов
                //необходимо для того чтоб не создавать кучу групп и не тратить "очки"
                if (Keywords::where('ad_group_id', null)->count()<self::MAX_KEYWORDS)
                    continue;

                //выбираем из бд 199 слов (лимит 200)
                $keywords_for_group = Keywords::where('ad_group_id', null)
                    ->limit(self::MAX_KEYWORDS)
                    ->get();

                //можем тут сделать создание компаний только если заполнена предидущая компания
                //компания может содержать 9999 объектов  групп

                $idCampaign = $idGroup = $companies = $groups = $kw_in_groups = null;

                $groups = Keywords::select('ad_group_id','campaing_id', DB::raw('count(ad_group_id)'))
                    ->groupBy('ad_group_id','campaing_id')
                    ->havingRaw("count(ad_group_id) <= ".self::MAX_GROUPS." and count(ad_group_id) >=1")
                    ->orderBy('count(ad_group_id)', 'desc')
                    ->first();

                if (!empty($groups)&&$groups["count(ad_group_id)"]<self::MAX_GROUPS) {
                    //пока в компании групп меньше чем лимит, берем айди этой компании

                    $idCampaign = $groups->campaing_id;

                    $kw_in_groups = Keywords::select('ad_group_id',DB::raw('count(keyword_id)'))
                        ->groupBy('ad_group_id')
                        ->where("ad_group_id",$groups["ad_group_id"])
                        ->first();
                }
                else{
                    //если кампаний нет или нет подходящей компании, то создаем
                    try {
                        error_log("test 2");
                        $idCampaign = $this->api->addCampain("test company ".((new Carbon())->now()))
                            ->getAddResults()[0]
                            ->getId();
                    }
                    catch (LogicException $exception){
                        $this->log->error($exception->getTraceAsString());

                    }
                }


                if (!empty($kw_in_groups)&&$kw_in_groups["count(keyword_id)"]<self::MAX_KEYWORDS){
                    //если найдена подходящая группа, то берем её айди и не создаем новый запрос к апи

                    $idGroup = $kw_in_groups->ad_group_id;

                }
                else {
                    //добавляем в команию группу
                    try {

                        $idGroup = $this->api->addGroup("group_to_campaign_$idCampaign-".((new Carbon())->now()), $idCampaign, [1])
                            ->getAddResults()[0]
                            ->getId();
                    }catch (LogicException $exception){
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
                    error_log("$idGroup , $kwfg->keyword");
                    try {
                        $item = $this->api->addKeyword($idGroup, $kwfg->keyword, False);
                        array_push($buf, $item);
                    }catch (LogicException $exception){
                        $this->log->error($exception->getTraceAsString());
                        continue;
                    }
                }

                if (empty($buf)) {
                    $this->log->error("Содержимое массива ключевых слов отсутствует!");
                    continue;
                }


                $keywordsIds = $this->api->doKeywordRequest($buf)->getAddResults();//тут получаем добавленные идентификаторы ключевых слов
                //по идее тут мы должны добавить их в бд, чтоб было проще добавлять цены по айди ключевого слова, а не только группы

                //ставим в соответствие каждому вернувшемуся айдишнику выбранное из бд слово
                for ($i = 0; $i < count($keywordsIds); $i++) {
                    $ki = $keywordsIds[$i]->getId();
                    $keywords_for_group[$i]->keyword_id = $ki;
                    $keywords_for_group[$i]->save();
                }

                //запрос к Bid-ам
                //засыпаем на 10 сек
                sleep(10);

                $bidData = null;

                try {
                    $bidData = $this->api->getBidData($idGroup);
                }catch (LogicException $exception){
                    $this->log->error($exception->getTraceAsString());
                    continue;
                }

                foreach ($bidData->getBids() as $bid) {
                    $kwwb = Keywords::where('keyword_id', $bid->getKeywordId())->first();

                    $kwwb->bid = $bid->getBid() != null ? $bid->getBid() / 1000000 : null;
                    $kwwb->context_bid = $bid->getContextBid() != null ? $bid->getContextBid() / 1000000 : null;

                    if ($bid->getCompetitorsBids() !== null) {
                        foreach ($bid->getCompetitorsBids() as $cb) {
                            //добавляем инфу в CompetitorsBids
                            $cmb = new CompetitorsBids();
                            $cmb->element = $cb / 1000000;
                            $cmb->Keywords_id = $kwwb->id;
                            $cmb->save();
                        }
                    }

                    if ($bid->getSearchPrices() !== null) {
                        $kwwb->search_prices_pf = $bid->getSearchPrices()[0]->getPrice() / 1000000;
                        $kwwb->search_prices_pb = $bid->getSearchPrices()[1]->getPrice() / 1000000;
                        $kwwb->search_prices_ff = $bid->getSearchPrices()[2]->getPrice() / 1000000;
                        $kwwb->search_prices_fb = $bid->getSearchPrices()[3]->getPrice() / 1000000;

                    }

                    if ($bid->getContextCoverage() !== null) {
                        foreach ($bid->getContextCoverage() as $cc) {
                            foreach ($cc->getItems() as $item) {
                                $kwcc = new ContextCoverage();
                                $kwcc->probability = $item->getProbability();
                                $kwcc->price = $item->getPrice() / 1000000;
                                $kwcc->Keywords_id = $kwwb->id;
                                $kwcc->save();
                            }
                        }
                    }

                    $kwwb->min_search_price = $bid->getMinSearchPrice() / 1000000;
                    $kwwb->current_search_price = $bid->getCurrentSearchPrice() / 1000000;
                    $kwwb->check_date = (new Carbon())->now();
                    $kwwb->save();

                }

            }
            // тут у нас проверка пропущенных по разным ричинам слов в бд - если слово уже было взято на обработку, но инфа из ворд стата не получена, то снимаем флаг "проверки" и на следующей круге по новой будет проверено слово
            $words = Keywords::where('check', 1)->get();
            foreach ($words as $w){
                if ($w->impressions_per_month==null){
                    $this->log->error("Слово $w->keyword было пропущено");
                    $w->check = null;
                    $w->save();
                }
            }
        }

    }
}
