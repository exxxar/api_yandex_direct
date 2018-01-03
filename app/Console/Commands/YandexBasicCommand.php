<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 03.01.2018
 * Time: 10:18
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class YandexBasicCommand extends Command
{
    const MAX_CAMPAINGS = 2999;
    const MAX_GROUPS = 9999;
    const MAX_KEYWORDS = 199;
    const MAX_FORECAST = 99;

    protected $api;

    protected $log;

    public function __construct(YandexApi $api, Log $log)
    {
        $this->api = $api;
        $this->log = $log;
        parent::__construct();
    }

    protected function doStep0($fname = "words.txt", $refresh = false)
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
                                    'add_date' => Carbon::now(),
                                    'check_date' => Carbon::now()
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


    protected function doFinalCheck()
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

    protected function doRandomInterval($min = 3, $max = 10)
    {
        $time = mt_rand($min, $max);
        $this->log->info("Засыпаем на время $time секунд");
        sleep($time);
    }

    protected function doResetChecks()
    {
        $this->log->info("Запуск режима сброса отмеченный слов!");
        $words = Keywords::where('check', 1)->get();
        foreach ($words as $w) {
            $w->check = null;
            $w->save();
        }
        $this->log->info('Все check у слов успешно сброшены!');
    }

    protected function doResetCampaings()
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

    protected function divideAndPrecede($keyword)
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
}