<?php

namespace restlin\morphology;

use Exception;
/**
 * Хелпер по работе с морфологией русского языка
 * @version 1.0.0
 * @todo добавить проверки на несуществующий падеж во все функции
 * @author Ilia Shumilov <restlinru@yandex.ru>
 */
class MorphologicHelper
{

    /**
     * путь до исходного файла со словами в формате .txt
     */
    const DICT_RAW_PATH = __DIR__ . '/words.txt';

    /**
     * Массив уникальных слов
     * @var string[]
     */
    static $words = [];

    /**
     * @var string sha1 исходного файла словаря
     */
    static $words_hash;

    /**
     * Именительный падеж
     */
    const NOMINATIVE = 'nominative';

    /**
     * родительский падеж
     */
    const GENETIVE = 'genetive';

    /**
     * дательный падеж
     */
    const DATIVE = 'dative';

    /**
     * винительный падеж
     */
    const ACCUSATIVE = 'accusative';

    /**
     * Творительный падеж
     */
    const INSTRUMENTAL = 'instrumental';

    /**
     * Предложный
     */
    const PREPOSITIONAL = 'prepositional';

    /**
     * Мужской род
     */
    const MALE = 1;

    /**
     * Женский род
     */
    const FEMALE = 2;

    /**
     * Пребразует ФИО в именительный падеж
     * @param string $fio ФИО
     * @return string результат
     */
    public static function fioToSimpleForm(string $fio) : string
    {
        if (preg_match('/ой$/ui', $fio)) { //переписать обратную функцию по всем окончаниям падежей
            $fio1 = preg_replace('/ой$/ui', 'а', $fio);
        } else {
            $fio1 = preg_replace('/(а|у|ого|ую)$/ui', '', $fio);
        }
        return preg_replace('/^([а-я]\.[а-я]\.)([а-я])/ui', '$1 $2', $fio1); //вставка обязательного пробела
    }

    /**
     * Определяет пол по отчеству
     * @param string $patron отчество
     * @return int
     */
    public static function identSexByPatronymic(string $patron) : int
    {
        return preg_match('/на$|кызы/ui', $patron) ? self::FEMALE : self::MALE;
    }

    /**
     * Получить окончания имен
     * @return Array
     */
    protected static function nameEnds() : Array
    {
        return [
            self::GENETIVE => ['и', 'и', 'ы', 'я', 'а', 'и'],
            self::DATIVE => ['е', 'e', 'е', 'ю', 'у', 'и'],
            self::ACCUSATIVE => ['у', 'ю', 'у', 'я', 'а', 'ь'],
            self::INSTRUMENTAL => ['ой', 'ей', 'ой', 'ем', 'ом', 'ю'],
            self::PREPOSITIONAL => ['е', 'е', 'е', 'е', 'е', 'и']
        ];
    }

    /**
     * Получить массив нестандартных имен
     * @return Array
     */
    protected static function uniqueNames() : Array
    {
        return [
            'павел' => 'павл',
            'лев' => 'льв'
        ];
    }

    /**
     * Склонение имени
     * @param string $name имя
     * @param int $sex пол
     * @param string $case падеж genetive dative accusative
     * @return string
     */
    public static function nameCase(string $name, int $sex, string $case) : string
    {
        $name = mb_convert_case($name, MB_CASE_LOWER, 'UTF-8');
        $uniq = self::uniqueNames();
        if (isset($uniq[$name])) {
            $name = $uniq[$name];
        }

        $ends = self::nameEnds();
        if (preg_match('/[уеыоэию]$/ui', $name)) {
            $name = $name;
        } elseif (preg_match('/[хгшжчщ]а$/ui', $name)) {
            $name = preg_replace("/.$/ui", $ends[$case][0], $name);
        } elseif (preg_match('/я$/ui', $name)) {
            $name = preg_replace("/.$/ui", $ends[$case][1], $name);
        } elseif (preg_match('/ка$/ui', $name)) {
            $name = preg_replace("/.$/ui", $ends[$case][0], $name);
        } elseif (preg_match('/а$/ui', $name)) {
            $name = preg_replace("/.$/ui", $ends[$case][2], $name);
        } elseif ($sex == self::MALE && preg_match('/[йь]$/ui', $name)) {
            $name = preg_replace("/.$/ui", $ends[$case][3], $name);
        } elseif ($sex == self::MALE) {
            $name .= $ends[$case][4];
        } elseif ($sex == self::FEMALE && preg_match('/[ь]$/ui', $name)) {
            $name = preg_replace("/.$/ui", $ends[$case][5], $name);
        }

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8'); //@todo это не функция морфологии а почему мы это делаем здесь???
    }

    /**
     * Склонение отвества
     * @param string $patron отчество
     * @param int $sex пол
     * @param string $case падеж genetive dative accusative
     * @return string
     */
    public static function patronymicCase(string $patron, int $sex, string $case) : string
    {
        //@todo выделить самостоятельную функцию, если будет необходимость
        return self::nameCase($patron, $sex, $case);
    }

    /**
     * Получить окончания фамилий
     * @return Array
     */
    protected static function surnameEnds() : array
    {
        return [
            self::GENETIVE => ['и', 'ы', '$1я', 'ы', 'ого', 'ца', 'я', 'а', 'ой', 'ы', 'ой', 'и'],
            self::DATIVE => ['е', 'e', '$1ю', 'е', 'ому', 'цу', 'ю', 'у', 'ой', 'е', 'ой', 'е'],
            self::ACCUSATIVE => ['у', 'у', '$1я', 'у', 'ого', 'ца', 'я', 'а', 'ую', 'у', 'у', 'ю'],
            self::INSTRUMENTAL => ['ой', 'ой', '$1ем', 'ей', 'ым', 'ом', 'ем', 'ом', 'ой', 'ой', 'ой', 'ей'],
            self::PREPOSITIONAL => ['е', 'е', '$1е', 'е', 'е', 'е', 'е', 'е', 'ой', 'е', 'ой', 'е'],
        ];
    }

    /**
     * Склонение мужской фамилии
     * @param string $surname фамилия
     * @param Array $ends окончания для соответствующего падежа
     * @return string
     */
    protected static function surnameMaleCase(string $surname, array $ends) : string
    {
        if (preg_match('/[кгх]а$/ui', $surname)) {
            $surname = preg_replace("/.$/ui", $ends[0], $surname); //0
        } elseif (preg_match('/[а]$/ui', $surname)) {
            $surname = preg_replace("/.$/ui", $ends[1], $surname); //1
        } elseif (preg_match('/[цкнгшщзхфвпрлджчсмтб]я$/ui', $surname)) { //Медя Месся и тд
            $surname = preg_replace("/.$/ui", $ends[11], $surname);
        } elseif (preg_match('/[оихяэе]$/ui', $surname)) {
            $surname = $surname;
        } elseif (preg_match('/ой$/ui', $surname) && mb_strlen($surname) > 5) { //Жирновой, но не Гой
            $surname = preg_replace("/..$/ui", $ends[4], $surname);
        } elseif (preg_match('/[уеаоэяю]й$/ui', $surname)) {
            $surname = preg_replace("/([уеаоэяю])й$/ui", $ends[2], $surname);
        } elseif (preg_match('/ца$/ui', $surname)) {
            $surname = preg_replace("/а$/ui", $ends[3], $surname);
        } elseif (preg_match('/й$/ui', $surname)) {
            $surname = preg_replace("/.{2,2}$/ui", $ends[4], $surname);
        } elseif (preg_match('/ец$/ui', $surname)) {
            $surname = preg_replace("/ец$/ui", $ends[5], $surname);
        } elseif (preg_match('/ь$/ui', $surname)) {
            $surname = preg_replace("/ь$/ui", $ends[6], $surname);
        } elseif (preg_match('/[ое]к$/ui', $surname) && mb_strlen($surname, 'UTF-8') > 4) { //Шляхтёнок, но не Флёк
            $surname = preg_replace("/[ое]к$/ui", 'к' . $ends[7], $surname);
        } else {
            $surname .= $ends[7];
        }
        $surname = preg_replace('/(вороб|солов)е([^й])/ui', '$1ь$2', $surname);
        return $surname;
    }

    /**
     * Склонение женской фамилии
     * @param string $surname фамилия
     * @param Array $ends окончания для соответствующего падежа
     * @return string
     */
    protected static function surnameFemaleCase(string $surname, Array $ends) : string
    {
        if (preg_match('/[бвгдежзийлкмнпорстуфхцчьшщ]$/ui', $surname)) {
            $surname = $surname;
        } elseif (preg_match('/[цкнгшщзхфвпрлджчсмтб]я$/ui', $surname)) { //Медя Месся и тд
            $surname = preg_replace("/.$/ui", $ends[11], $surname);
        } elseif (preg_match('/я$/ui', $surname)) { //Бахмутская
            $surname = preg_replace("/.{2,2}$/ui", $ends[8], $surname);
        } elseif (preg_match('/[пц]а$/ui', $surname)) {
            $surname = preg_replace("/а$/ui", $ends[9], $surname);
        } elseif (preg_match('/[ткб]а$/ui', $surname)) {
            $surname = $surname;
        } else {
            $surname = preg_replace("/.$/ui", $ends[10], $surname);
        }
        return $surname;
    }

    /**
     * Склонение фамилии
     * @param string $surname фамилия
     * @param int $sex пол
     * @param string $case падеж genetive dative accusative
     * @return string
     */
    public static function surnameCase(string $surname, int $sex, string $case) : string
    {
        $ends = self::surnameEnds();
        if ($sex == self::MALE) {
            $surname = self::surnameMaleCase($surname, $ends[$case]);
        } else {
            $surname = self::surnameFemaleCase($surname, $ends[$case]);
        }
        return mb_convert_case($surname, MB_CASE_TITLE, 'UTF-8'); //@todo это не функция морфологии а почему мы это делаем здесь???
    }

    /**
     * получить массив окончаний должностей
     * @return array
     */
    protected static function profEnds() : array
    {
        return [
            self::GENETIVE => ['ого', 'его', 'а', 'я', 'ого'],
            self::DATIVE => ['ому', 'ему', 'у', 'ю', 'ому'],
            self::ACCUSATIVE => ['ого', 'его', 'а', 'я', 'ого'],
            self::INSTRUMENTAL => ['им', 'им', 'ом', 'ем', 'ым'],
            self::PREPOSITIONAL => ['ом', 'ем', 'е', 'е', 'ом'],
        ];
    }

    /**
     * Возвращает должность в указанном падеже
     * @param string $case падеж
     * @return string
     */
    public static function profCase(string $prof, string $case) : string
    {
        $ends = self::profEnds();
        $prof = trim($prof) . ' ';
        $prof1 = preg_replace('/(ск)ий /ui', "$1{$ends[$case][0]} ", $prof);
        $prof2 = preg_replace('/([чшщ])ий /ui', "$1{$ends[$case][1]} ", $prof1);
        $prof3 = preg_replace('/ый /ui', "{$ends[$case][4]} ", $prof2);
        $prof4 = preg_replace('/([аиео]р|ист|ик|[ае]нт|ог|юрисконсульт|ч|вед|ен)([ \-])/ui', "$1{$ends[$case][2]}$2", $prof3);
        $prof5 = preg_replace('/(ел|ар)ь([ \-])/ui', "$1{$ends[$case][3]}$2", $prof4);
        return trim($prof5);
    }

    /**
     * возвращает должность в базовую форму
     * @param string $prof должность
     * @return string
     */
    public static function profToSimpleForm(string $prof) : string
    {
        $prof1 = preg_replace('/([внт])(ый|ому|ого)\b/ui', '$1ый', $prof);
        $prof2 = preg_replace('/([кчшщ])(ий|ему|ому|его|ого)\b/ui', '$1ий', $prof1);
        $prof3 = preg_replace('/(тел)(ь|я|ю)?/ui', '$1ь', $prof2);
        $prof4 = preg_replace('/([мнртчщ]ик)(а|у)?/ui', '$1', $prof3);
        $prof5 = preg_replace('/(ор|ер|ог)(а|у)?\b/ui', '$1', $prof4);
        return $prof5;
    }

    /**
     * получить массив окончаний слов
     * @return array
     */
    protected static function wordEnds() : array
    {
        return [
            self::GENETIVE => ['ого', 'его', 'а', 'я', 'ой', 'и', 'я', 'ка', 'а', 'ы', 'ей', 'и'],
            self::DATIVE => ['ому', 'ему', 'у', 'ю', 'ой', 'е', 'ю', 'ку', 'у', 'е', 'ей', 'и'],
            self::ACCUSATIVE => ['ого', 'его', 'а', 'я', 'ую', 'у', 'е', 'ок', '', 'у', 'ую', 'ю'],
            self::INSTRUMENTAL => ['им', 'им', 'ом', 'ем', 'ой', 'ой', 'ем', 'ком', 'ом', 'ой', 'ей', 'ей'],
            self::PREPOSITIONAL => ['ом', 'ом', 'е', 'е', 'ой', 'е', 'и', 'ке', 'е', 'е', 'ей', 'и'],
        ];
    }

    /**
     * Возвращает слово в указанном падеже
     * @param string $word слово
     * @param string $case падеж
     * @return string
     */
    public static function wordCase(string $word, string $case): string
    {
        $ends = self::wordEnds();
        if (in_array(mb_strtolower($word, 'UTF-8'), ['по', 'о', 'об', 'над', 'пальто', 'метро', 'кофе', 'бюро', 'на', 'постоянно', 'регулярно', 'вечно'])) {
            return $word;
        } elseif (preg_match('/ский|ое$/ui', $word)) {
            return preg_replace('/..$/ui', $ends[$case][0], $word);
        } elseif (preg_match('/[чшщ]ий$/ui', $word)) {
            return preg_replace('/..$/ui', $ends[$case][1], $word);
        } elseif (preg_match('/ый$/ui', $word)) {
            return preg_replace('/..$/ui', $ends[$case][0], $word); //проверить в рабочем варианте
        } elseif (preg_match('/([аиеёот]р|ист|ик|[ае]нт|ог|юрисконсульт|ч|вед|аз|ен)$/ui', $word)) {
            return $word . $ends[$case][2];
        } elseif (preg_match('/(ел|ар)ь$/ui', $word)) {
            return preg_replace('/.$/ui', $ends[$case][3], $word);
        } elseif (preg_match('/[жчщш][ая]я$/ui', $word)) {
            return preg_replace('/..$/ui', $ends[$case][10], $word);
        } elseif (preg_match('/[ая]я$/ui', $word)) {
            return preg_replace('/..$/ui', $ends[$case][4], $word);
        } elseif (preg_match('/[гжкчхшщ]а$/ui', $word)) {
            return preg_replace('/.$/ui', $ends[$case][5], $word);
        } elseif (preg_match('/[цнзвпрлдсмтб]а$/ui', $word)) {
            return preg_replace('/.$/ui', $ends[$case][9], $word);
        } elseif (preg_match('/ия$/ui', $word)) {
            return preg_replace('/.$/ui', $ends[$case][11], $word);
        } elseif (preg_match('/ние$/ui', $word)) {
            return preg_replace('/.$/ui', $ends[$case][6], $word);
        } elseif (preg_match('/ок$/ui', $word) && mb_strlen($word, 'UTF-8') > 4) {
            return preg_replace('/..$/ui', $ends[$case][7], $word);
        } elseif (preg_match('/[ое][бвгдлнх]$/ui', $word)) { //но не бармен
            return $word . $ends[$case][8];
        } elseif (preg_match('/[цкнгшщзхфвпрлджчсмтб]о$/ui', $word)) { //общество
            return preg_replace('/.$/ui', $ends[$case][8], $word);
        } else {
            return $word;
        }
    }
    protected static function getPathForTmpFile(): string {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'restlin-words.php';
    }

    /**
     * Загрузить словарь в $words
     */
    protected static function loadWords()
    {
        if (self::$words) {
            return true;
        }
        $path = self::getPathForTmpFile();
        if (file_exists($path)) {
            list(self::$words_hash, self::$words) = require $path;
        }
        if (self::$words_hash !== sha1_file(self::DICT_RAW_PATH)) {
            self::readWords();
            self::writeWords();
            self::$words_hash = sha1_file(self::DICT_RAW_PATH);
        }
    }

    /**
     * Загрузка слов в массив $words из исходного словаря
     */
    private static function readWords()
    {
        if (file_exists(self::DICT_RAW_PATH) && ($filein = @fopen(self::DICT_RAW_PATH, 'r'))) {
            while (($word = fgets($filein)) !== false) {
                self::$words += self::getConnectedWords($word);
            }
            fclose($filein);
        } else {
            throw new Exception('Не могу найти или открыть исходный файл словаря');
        }
    }

    /**
     * Запись слов в новый словарь
     */
    private static function writeWords()
    {
        $content = "<?php\nreturn ";
        $content .= var_export([sha1_file(self::DICT_RAW_PATH), self::$words], true);
        $content .= ";\n";
        $result = file_put_contents(self::getPathForTmpFile(), $content);
        if ($result === false) {
            throw new Exception('Не могу создать или открыть конечный файл словаря');
        }
    }

    /**
     * Родственные слова для слова из словаря
     * @param string $word
     * @return string[]
     */
    public static function getConnectedWords(string $word): array
    {
        $words = [];
        $base = str_replace('ё', 'е', trim($word));
        $words[$base] = true;
        if (preg_match('/[иы]й$/ui', $base)) { //именительные падежи для женского и среднего рода
            $female = preg_replace('/..$/ui', 'ая', $base);
            $words[$female] = true;
            $female2 = preg_replace('/..$/ui', 'яя', $base);
            $words[$female2] = true;
            $middle = preg_replace('/..$/ui', 'ое', $base);
            $words[$middle] = true;
        }
        return $words;
    }

    /**
     * Является ли слово базовой формой слова
     * @param string $word слово
     * @return bool
     */
    protected static function isWordMainCase(string $word): bool
    {
        $base = str_replace('ё', 'е', mb_strtolower($word, 'utf-8'));
        return !self::$words || key_exists($base, self::$words);
    }

    /**
     * Преобразовать словосочетание из именительного падежа в переданный
     * @param string $collocation словосочетание
     * @param string $case итоговый падеж
     * @param bool $checkDict проверка в словаре
     * @return string
     */
    public static function collocationCase(string $collocation, string $case, bool $checkDict = true): string
    {
        self::loadWords();
        return preg_replace_callback('/\w+/ui', function($search) use ($case, $checkDict)
        {
            if ($checkDict && !self::isWordMainCase($search[0])) {
                return $search[0];
            }
            return self::wordCase($search[0], $case);
        }, $collocation);
    }

}
