<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 03.01.2018
 * Time: 10:18
 */

namespace App\Console\Commands;

use App\Forecastinfo;
use App\Http\Controllers\API\YandexApi;
use App\Keywords;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\Storage;


trait YandexBasicCommand
{
    protected $api;
    protected $log;

    public function __construct(YandexApi $api, Log $log)
    {
        $this->api = $api;
        $this->log = $log;

    }

    public function doStep0($fname = "words.txt", $refresh = false)
    {
        $this->log->info('Этап 0 - начало работы');
        if (Keywords::count() == 0 || $refresh == True) {
            $this->log->info($refresh ? "Режим обновления" : "В таблице 'Keywords' отсутствуют слова, получаем из файла");
            $contents = Storage::disk('public')->get($fname);
            $contents = preg_split('/[\s,]+\n/', $contents);
            $index = 0;
            foreach ($contents as $c) {
                $index++;
                if (trim($c)) {
                    $this->log->info($index . "->" . $c);
                    try {
                        $kw = Keywords::where("keyword", $c)->first();
                        if (empty($kw)) {
                            Keywords::insertGetId(
                                [
                                    'keyword' => $c,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ]
                            );
                            $this->log->info("Добавляем слово:$c");
                        }

                    } catch (\Exception $e) {
                        $this->log->error("Не удалось добавить слово в базу: " . $e->getMessage());
                    }

                }
            }
        }
        $this->log->info('Этап 0 - завершение работы');

    }


    public function doFinalCheck()
    {
        $this->log->info("Запуск режима проверки пропущенных слов!");
        // тут у нас проверка пропущенных по разным причинам слов в бд - если слово уже было взято на обработку, но инфа из ворд стата не получена, то снимаем флаг "проверки" и на следующей круге по новой будет проверено слово
        $words = Keywords::where('check', 1)->get();
        $count = 0;
        foreach ($words as $w) {
            if ($w->impressions_per_month == null) {
                $this->log->error("Слово $w->keyword было пропущено");
                $w->check = null;
                $w->save();
                $count++;
            }
        }
        $this->log->info("Все промущенные слова успешно найдены, всего $count!");
    }

    public function doRandomInterval($min = 3, $max = 10)
    {
        $time = mt_rand($min, $max);
        $this->log->info("Засыпаем на время $time секунд");
        sleep($time);
    }

    public function doResetChecks()
    {
        $this->log->info("Запуск режима сброса отмеченный слов!");
        $words = Keywords::where('check', 1)->get();
        foreach ($words as $w) {
            $w->check = null;
            $w->save();
        }
        $this->log->info('Все check у слов успешно сброшены!');
    }

    public function doResetChecksIfForecastNotFound()
    {
        $words = Keywords::where('check', 1)->get();
        foreach ($words as $w) {
            $forecast = Forecastinfo::find($w->id);
            if (empty($forecast)) {
                $w->check = null;
                $w->save();
            }
        }
        $this->log->info('Все check у слов успешно сброшены!');
    }

    public function doResetCampaings()
    {
        $this->log->info("Запуск режима сброса информации о компаниях!");
        //повторно отмечаем все слова не проверенными, чтоб повторно пройтись по ним и заполнить группы
        $words = Keywords::all();
        foreach ($words as $w) {
            $w->check = null;
            $w->keyword_id = null;
            $w->campaing_id = null;
            $w->ad_group_id = null;
            $w->save();
        }
        $this->log->info('Все компании успешно сброшены!');
    }

    public function divideAndPrecede($keyword)
    {
        if (strrpos(trim($keyword), "[") !== False
            && strrpos(trim($keyword), "]") !== False
            && strrpos(trim($keyword), "!") !== False)
            return $keyword;

        $buf = explode(' ', $keyword);
        $splited_keywords = "\"[";
        foreach ($buf as $b) {
            if (strlen($b) <= 0)
                continue;
            //если слово идёт с "+", то это и следующие слова идут без "!"
            $splited_keywords .= (strrpos(trim($b), "+") === False ? " !$b " : str_replace('+', ' ', $b));
        }
        $splited_keywords .= "]\"";
        $this->log->info("Предваряем ключевые слова во фразе знаком '!'->$splited_keywords");
        return $splited_keywords;
    }

    public function restoringPrecede($keyword)
    {
        $rez = trim(preg_replace("/[!\[\]\"]/i", "", $keyword));

        $rezInArray = explode(" ",$rez);
        $buf = "";
        foreach ($rezInArray as $r){
            $buf .=(strlen(trim($r))>0?trim($r)." ":"");
        }
        return $buf;
    }

    public function checkLenAndSlice($keyword) {
        $text = mb_split(" ",$keyword);
        $new_keyword = "";
        $index = 7;
        if (count($text)>7) {
            foreach ($text as $word) {
                $new_keyword .= trim($word)." ";
                $index--;
                if ($index==0)
                    break;
            }
        }
        return count($text)>7?trim( $new_keyword):$keyword;
    }

    public function word_count($keyword){
        $text = mb_split(" ",$keyword);
        return count($text);
    }
}