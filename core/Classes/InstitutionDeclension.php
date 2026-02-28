<?php

namespace Core;

/**
 * Класс для склонения названий учреждений по падежам.
 *
 * Логика:
 * 1. Декодируем HTML-сущности и нормализуем двойные CSV-кавычки ("" → «»)
 * 2. Всё до первой кавычки — склоняемая часть («префикс»)
 * 3. Всё от первой кавычки — не склоняется (собственное название)
 * 4. Аббревиатуры (ООО, АНО, ГКУ и т.д.) не склоняются
 * 5. Топонимы после типа округа не склоняются
 * 6. "Московской" всегда с заглавной буквы
 */
class InstitutionDeclension
{
    // -----------------------------------------------------------------------
    // Словари
    // -----------------------------------------------------------------------

    /**
     * Словарь существительных: ключ — именительный (нижний регистр).
     */
    private static $nouns = [
        'учреждение' => [
            'genitive'      => 'учреждения',
            'dative'        => 'учреждению',
            'accusative'    => 'учреждение',
            'instrumental'  => 'учреждением',
            'prepositional' => 'учреждении',
        ],
        'пансионат' => [
            'genitive'      => 'пансионата',
            'dative'        => 'пансионату',
            'accusative'    => 'пансионат',
            'instrumental'  => 'пансионатом',
            'prepositional' => 'пансионате',
        ],
        'центр' => [
            'genitive'      => 'центра',
            'dative'        => 'центру',
            'accusative'    => 'центр',
            'instrumental'  => 'центром',
            'prepositional' => 'центре',
        ],
        'администрация' => [
            'genitive'      => 'администрации',
            'dative'        => 'администрации',
            'accusative'    => 'администрацию',
            'instrumental'  => 'администрацией',
            'prepositional' => 'администрации',
        ],
        'округ' => [
            'genitive'      => 'округа',
            'dative'        => 'округу',
            'accusative'    => 'округ',
            'instrumental'  => 'округом',
            'prepositional' => 'округе',
        ],
        'район' => [
            'genitive'      => 'района',
            'dative'        => 'району',
            'accusative'    => 'район',
            'instrumental'  => 'районом',
            'prepositional' => 'районе',
        ],
        'область' => [
            'genitive'      => 'области',
            'dative'        => 'области',
            'accusative'    => 'область',
            'instrumental'  => 'областью',
            'prepositional' => 'области',
        ],
        'служба' => [
            'genitive'      => 'службы',
            'dative'        => 'службе',
            'accusative'    => 'службу',
            'instrumental'  => 'службой',
            'prepositional' => 'службе',
        ],
        'управление' => [
            'genitive'      => 'управления',
            'dative'        => 'управлению',
            'accusative'    => 'управление',
            'instrumental'  => 'управлением',
            'prepositional' => 'управлении',
        ],
        'министерство' => [
            'genitive'      => 'министерства',
            'dative'        => 'министерству',
            'accusative'    => 'министерство',
            'instrumental'  => 'министерством',
            'prepositional' => 'министерстве',
        ],
        'департамент' => [
            'genitive'      => 'департамента',
            'dative'        => 'департаменту',
            'accusative'    => 'департамент',
            'instrumental'  => 'департаментом',
            'prepositional' => 'департаменте',
        ],
        'комитет' => [
            'genitive'      => 'комитета',
            'dative'        => 'комитету',
            'accusative'    => 'комитет',
            'instrumental'  => 'комитетом',
            'prepositional' => 'комитете',
        ],
        'дом' => [
            'genitive'      => 'дома',
            'dative'        => 'дому',
            'accusative'    => 'дом',
            'instrumental'  => 'домом',
            'prepositional' => 'доме',
        ],
        'интернат' => [
            'genitive'      => 'интерната',
            'dative'        => 'интернату',
            'accusative'    => 'интернат',
            'instrumental'  => 'интернатом',
            'prepositional' => 'интернате',
        ],
        'реабилитация' => [
            'genitive'      => 'реабилитации',
            'dative'        => 'реабилитации',
            'accusative'    => 'реабилитацию',
            'instrumental'  => 'реабилитацией',
            'prepositional' => 'реабилитации',
        ],
        'обслуживание' => [
            'genitive'      => 'обслуживания',
            'dative'        => 'обслуживанию',
            'accusative'    => 'обслуживание',
            'instrumental'  => 'обслуживанием',
            'prepositional' => 'обслуживании',
        ],
        'население' => [
            'genitive'      => 'населения',
            'dative'        => 'населению',
            'accusative'    => 'население',
            'instrumental'  => 'населением',
            'prepositional' => 'населении',
        ],
        'помощь' => [
            'genitive'      => 'помощи',
            'dative'        => 'помощи',
            'accusative'    => 'помощь',
            'instrumental'  => 'помощью',
            'prepositional' => 'помощи',
        ],
        'семья' => [
            'genitive'      => 'семье',
            'dative'        => 'семье',
            'accusative'    => 'семью',
            'instrumental'  => 'семьёй',
            'prepositional' => 'семье',
        ],
        'дети' => [
            'genitive'      => 'детей',
            'dative'        => 'детям',
            'accusative'    => 'детей',
            'instrumental'  => 'детьми',
            'prepositional' => 'детях',
        ],
        'лаборатория' => [
            'genitive'      => 'лаборатории',
            'dative'        => 'лаборатории',
            'accusative'    => 'лабораторию',
            'instrumental'  => 'лабораторией',
            'prepositional' => 'лаборатории',
        ],
        'деятельность' => [
            'genitive'      => 'деятельности',
            'dative'        => 'деятельности',
            'accusative'    => 'деятельность',
            'instrumental'  => 'деятельностью',
            'prepositional' => 'деятельности',
        ],
        'адаптация' => [
            'genitive'      => 'адаптации',
            'dative'        => 'адаптации',
            'accusative'    => 'адаптацию',
            'instrumental'  => 'адаптацией',
            'prepositional' => 'адаптации',
        ],
        'инновация' => [
            'genitive'      => 'инновации',
            'dative'        => 'инновации',
            'accusative'    => 'инновацию',
            'instrumental'  => 'инновацией',
            'prepositional' => 'инновации',
        ],
        'сфера' => [
            'genitive'      => 'сферы',
            'dative'        => 'сфере',
            'accusative'    => 'сферу',
            'instrumental'  => 'сферой',
            'prepositional' => 'сфере',
        ],
        'занятость' => [
            'genitive'      => 'занятости',
            'dative'        => 'занятости',
            'accusative'    => 'занятость',
            'instrumental'  => 'занятостью',
            'prepositional' => 'занятости',
        ],
        'заказчик' => [
            'genitive'      => 'заказчика',
            'dative'        => 'заказчику',
            'accusative'    => 'заказчик',
            'instrumental'  => 'заказчиком',
            'prepositional' => 'заказчике',
        ],
        'дирекция' => [
            'genitive'      => 'дирекции',
            'dative'        => 'дирекции',
            'accusative'    => 'дирекцию',
            'instrumental'  => 'дирекцией',
            'prepositional' => 'дирекции',
        ],
    ];

    /**
     * Аббревиатуры и несклоняемые слова (нижний регистр).
     * Возвращаются как есть, без изменений.
     */
    private static $abbreviations = [
        'ооо', 'ано', 'гку', 'гбу', 'гау', 'гкуз', 'гбуз', 'ип', 'пао', 'оао', 'зао',
        'нко', 'чуз', 'моо', 'гку мо', 'гау мо', 'гбу мо', 'ску', 'гмо',
    ];

    /**
     * Слова, которые не склоняются в составе названий учреждений.
     * Ключ — нижний регистр.
     */
    private static $invariable = [
        'социального',   // часть устойчивого сочетания
        'московской',    // топоним — всегда с заглавной
        'области',       // уже в родительном
        'имени',         // предложное слово
        'единого',       // часть устойчивого сочетания
        'обеспечения',   // часть устойчивого сочетания
        'развития',      // часть устойчивого сочетания
        'ухода',         // часть устойчивого сочетания
        'долговременного',
    ];

    /**
     * Типы округов/районов — после них идёт топоним, который не склоняется.
     */
    private static $districtTypes = [
        'муниципального округа',
        'городского округа',
        'муниципального района',
        'городского района',
    ];

    /**
     * Обратный индекс: словоформа (нижний регистр) → именительный.
     */
    private static $reverseIndex = null;

    // -----------------------------------------------------------------------
    // Публичный API
    // -----------------------------------------------------------------------

    /**
     * Склоняет название учреждения в указанный падеж.
     */
    public static function decline(string $name, string $case): string
    {
        $name = html_entity_decode(trim($name), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (empty($name) || $case === 'nominative') {
            return $name;
        }

        // Нормализуем кавычки
        $name = self::normalizeCsvQuotes($name);

        // Если вся строка в верхнем регистре (МИНИСТЕРСТВО СОЦИАЛЬНОГО...) —
        // приводим к нормальному виду: первое слово с заглавной, остальные строчные
        if (self::isAllCaps($name)) {
            $name = self::normalizeAllCaps($name);
        }

        // Аббревиатура в начале (ООО, АНО и т.д.) — не склоняем всё название
        if (self::startsWithAbbreviation($name)) {
            return $name;
        }

        // Находим первую кавычку
        $quotePos = self::findFirstQuote($name);

        if ($quotePos === false) {
            return self::declinePhrase($name, $case);
        }

        $prefix = mb_substr($name, 0, $quotePos, 'UTF-8');
        $quoted  = mb_substr($name, $quotePos, null, 'UTF-8');

        return rtrim(self::declinePhrase($prefix, $case)) . ' ' . $quoted;
    }

    // -----------------------------------------------------------------------
    // Приватные методы
    // -----------------------------------------------------------------------

    /**
     * Нормализует все виды кавычек к «».
     *
     * Поддерживает:
     * - CSV двойные: ""Название"" → «Название»
     * - Одиночные прямые: "Пансионат "Клинский" → «Пансионат «Клинский»
     * - Уже нормальные «» — оставляем как есть
     */
    private static function normalizeCsvQuotes(string $s): string
    {
        // 1. CSV-формат: заменяем "" → временный маркер, потом в «»
        if (mb_strpos($s, '""', 0, 'UTF-8') !== false) {
            $s = preg_replace('/""/u', '«', $s);
            $s = preg_replace('/«([^«»]+)«/u', '«$1»', $s);
            $s = trim($s, '"');
            return $s;
        }

        // 2. Одиночные прямые кавычки "
        // Все " становятся «, кроме последней — она становится »
        if (mb_strpos($s, '"', 0, 'UTF-8') !== false) {
            $lastPos = mb_strrpos($s, '"', 0, 'UTF-8');
            $s = str_replace('"', '«', $s);
            $s = mb_substr($s, 0, $lastPos, 'UTF-8') . '»' . mb_substr($s, $lastPos + 1, null, 'UTF-8');
            return $s;
        }

        return $s;
    }

    /**
     * Проверяет, начинается ли строка с аббревиатуры типа ООО, АНО и т.д.
     */
    private static function startsWithAbbreviation(string $name): bool
    {
        $firstWord = mb_strtolower(preg_split('/[\s«»"]/u', trim($name))[0] ?? '', 'UTF-8');
        return in_array($firstWord, self::$abbreviations, true);
    }

    /**
     * Склоняет фразу (часть до кавычек).
     */
    private static function declinePhrase(string $phrase, string $case): string
    {
        $phrase = self::normalizeToNominative(trim($phrase));
        $words  = preg_split('/\s+/u', $phrase);
        $n      = count($words);
        $result = [];
        $i      = 0;

        while ($i < $n) {
            $word = $words[$i];
            if (empty($word)) { $i++; continue; }

            $lc = mb_strtolower($word, 'UTF-8');

            // Предлоги
            if (self::isPreposition($word)) {
                $result[] = $word;
                $i++;
                continue;
            }

            // Аббревиатуры внутри фразы (ГКУ МО, ГАУ МО и т.д.)
            if (self::isAbbreviation($word)) {
                $result[] = $word;
                $i++;
                continue;
            }

            // Неизменяемые слова
            if (in_array($lc, self::$invariable, true)) {
                $result[] = ($lc === 'московской') ? 'Московской' : $word;
                $i++;
                continue;
            }

            // Тип округа: "городского округа" / "муниципального округа"
            // После них идёт топоним — пропускаем его
            $districtLen = self::matchDistrictType($words, $i);
            if ($districtLen > 0) {
                // Склоняем тип округа
                for ($j = 0; $j < $districtLen; $j++) {
                    $result[] = self::declineSingleWord($words[$i + $j], $case);
                }
                $i += $districtLen;

                // Следующее слово — топоним (название города/округа) — не склоняем
                if ($i < $n) {
                    $toponym = $words[$i];
                    $toponymLc = mb_strtolower($toponym, 'UTF-8');
                    // Если это не "Московской" и не "области" — это топоним
                    if (!in_array($toponymLc, ['московской', 'области'], true)) {
                        // Добавляем топоним как есть (может быть составным через дефис)
                        $result[] = $toponym;
                        $i++;
                    }
                }
                continue;
            }

            // Существительное из словаря
            if (isset(self::$nouns[$lc])) {
                $declined = self::$nouns[$lc][$case] ?? $word;
                $result[] = self::matchCase($word, $declined);
                $i++;
                continue;
            }

            // По правилам
            $result[] = self::declineSingleWord($word, $case);
            $i++;
        }

        return implode(' ', $result);
    }

    /**
     * Склоняет одно слово по правилам (прилагательное или существительное).
     */
    private static function declineSingleWord(string $word, string $case): string
    {
        if ($case === 'nominative') return $word;
        if (self::isAdjective($word)) return self::declineAdjective($word, $case);
        return self::declineNoun($word, $case);
    }

    /**
     * Нормализует фразу к именительному падежу через обратный индекс и нормализацию прилагательных.
     */
    private static function normalizeToNominative(string $phrase): string
    {
        self::buildReverseIndex();
        $words  = preg_split('/\s+/u', $phrase);
        $result = [];

        foreach ($words as $word) {
            if (empty($word)) continue;
            $lc = mb_strtolower($word, 'UTF-8');

            if (in_array($lc, self::$invariable, true) || self::isPreposition($word) || self::isAbbreviation($word)) {
                $result[] = $word;
                continue;
            }

            if (isset(self::$reverseIndex[$lc])) {
                $result[] = self::matchCase($word, self::$reverseIndex[$lc]);
                continue;
            }

            $result[] = self::adjectiveToNominative($word);
        }

        return implode(' ', $result);
    }

    /**
     * Строит обратный индекс.
     */
    private static function buildReverseIndex(): void
    {
        if (self::$reverseIndex !== null) return;
        self::$reverseIndex = [];
        foreach (self::$nouns as $nom => $forms) {
            foreach ($forms as $form) {
                $lc = mb_strtolower($form, 'UTF-8');
                if (!isset(self::$reverseIndex[$lc])) {
                    self::$reverseIndex[$lc] = $nom;
                }
            }
        }
    }

    /**
     * Пытается привести прилагательное из косвенного падежа к именительному.
     */
    private static function adjectiveToNominative(string $word): string
    {
        $lc = mb_strtolower($word, 'UTF-8');
        // Мужской/средний род косвенные → именительный средний (чаще встречается в названиях учреждений)
        $oblique = [
            'ого' => 'ое', 'ему' => 'ое', 'ым' => 'ое', 'ом' => 'ое',
            'его' => 'ее', 'им'  => 'ее', 'ем' => 'ее',
            'ой'  => 'ая', 'ую'  => 'ая',
            'ей'  => 'яя', 'юю'  => 'яя',
        ];
        foreach ($oblique as $suffix => $nomSuffix) {
            $sufLen = mb_strlen($suffix, 'UTF-8');
            if (mb_substr($lc, -$sufLen, null, 'UTF-8') === $suffix) {
                $base = mb_substr($word, 0, -$sufLen, 'UTF-8');
                if (mb_strlen($base, 'UTF-8') > 2) {
                    return $base . $nomSuffix;
                }
            }
        }
        return $word;
    }

    /**
     * Проверяет совпадение типа округа начиная с позиции $i.
     * Возвращает количество слов типа округа или 0.
     */
    private static function matchDistrictType(array $words, int $i): int
    {
        foreach (self::$districtTypes as $type) {
            $typeWords = explode(' ', $type);
            $len = count($typeWords);
            if ($i + $len > count($words)) continue;
            $match = true;
            for ($j = 0; $j < $len; $j++) {
                if (mb_strtolower($words[$i + $j], 'UTF-8') !== mb_strtolower($typeWords[$j], 'UTF-8')) {
                    $match = false; break;
                }
            }
            if ($match) return $len;
        }
        return 0;
    }

    /**
     * Проверяет, написана ли вся строка в верхнем регистре (кроме пробелов).
     */
    private static function isAllCaps(string $s): bool
    {
        $letters = preg_replace('/[^а-яёa-z]/ui', '', $s);
        if (empty($letters)) return false;
        return mb_strtoupper($s, 'UTF-8') === $s;
    }

    /**
     * Приводит строку в верхнем регистре к нормальному виду:
     * первое слово с заглавной буквы, остальные строчные.
     * Например: "МИНИСТЕРСТВО СОЦИАЛЬНОГО РАЗВИТИЯ" → "Министерство социального развития"
     */
    private static function normalizeAllCaps(string $s): string
    {
        $lower = mb_strtolower($s, 'UTF-8');
        return mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($lower, 1, null, 'UTF-8');
    }

    /**
     * Проверяет, является ли слово аббревиатурой.
     */
    private static function isAbbreviation(string $word): bool
    {
        $lc = mb_strtolower($word, 'UTF-8');
        // Чисто заглавные слова длиной до 5 символов — аббревиатуры
        if (mb_strtoupper($word, 'UTF-8') === $word && mb_strlen($word, 'UTF-8') <= 6) {
            return true;
        }
        return in_array($lc, self::$abbreviations, true);
    }

    /**
     * Определяет, является ли слово прилагательным.
     */
    private static function isAdjective(string $word): bool
    {
        $endings = ['ый', 'ий', 'ой', 'ая', 'яя', 'ое', 'ее', 'ые', 'ие'];
        foreach ($endings as $e) {
            if (mb_substr($word, -mb_strlen($e, 'UTF-8'), null, 'UTF-8') === $e) return true;
        }
        return false;
    }

    /**
     * Склонение прилагательного.
     */
    private static function declineAdjective(string $word, string $case): string
    {
        $table = [
            'ое' => ['genitive'=>'ого', 'dative'=>'ому', 'accusative'=>'ое',  'instrumental'=>'ым',  'prepositional'=>'ом'],
            'ее' => ['genitive'=>'его', 'dative'=>'ему', 'accusative'=>'ее',  'instrumental'=>'им',  'prepositional'=>'ем'],
            'ый' => ['genitive'=>'ого', 'dative'=>'ому', 'accusative'=>'ый',  'instrumental'=>'ым',  'prepositional'=>'ом'],
            'ий' => ['genitive'=>'его', 'dative'=>'ему', 'accusative'=>'ий',  'instrumental'=>'им',  'prepositional'=>'ем'],
            'ой' => ['genitive'=>'ого', 'dative'=>'ому', 'accusative'=>'ой',  'instrumental'=>'ым',  'prepositional'=>'ом'],
            'ая' => ['genitive'=>'ой',  'dative'=>'ой',  'accusative'=>'ую',  'instrumental'=>'ой',  'prepositional'=>'ой'],
            'яя' => ['genitive'=>'ей',  'dative'=>'ей',  'accusative'=>'юю',  'instrumental'=>'ей',  'prepositional'=>'ей'],
            'ые' => ['genitive'=>'ых',  'dative'=>'ым',  'accusative'=>'ые',  'instrumental'=>'ыми', 'prepositional'=>'ых'],
            'ие' => ['genitive'=>'их',  'dative'=>'им',  'accusative'=>'ие',  'instrumental'=>'ими', 'prepositional'=>'их'],
        ];
        foreach ($table as $ending => $cases) {
            $eLen = mb_strlen($ending, 'UTF-8');
            if (mb_substr($word, -$eLen, null, 'UTF-8') === $ending) {
                $base = mb_substr($word, 0, -$eLen, 'UTF-8');
                return $base . ($cases[$case] ?? $ending);
            }
        }
        return $word;
    }

    /**
     * Склонение существительного по общим правилам.
     */
    private static function declineNoun(string $word, string $case): string
    {
        $last  = mb_substr($word, -1, 1, 'UTF-8');
        $last2 = mb_substr($word, -2, 2, 'UTF-8');

        if ($last2 === 'ие') {
            $base = mb_substr($word, 0, -2, 'UTF-8');
            return $base . (['genitive'=>'ия','dative'=>'ию','accusative'=>'ие','instrumental'=>'ием','prepositional'=>'ии'][$case] ?? $last2);
        }
        if ($last2 === 'ия') {
            $base = mb_substr($word, 0, -2, 'UTF-8');
            return $base . (['genitive'=>'ии','dative'=>'ии','accusative'=>'ию','instrumental'=>'ией','prepositional'=>'ии'][$case] ?? $last2);
        }
        if ($last === 'ь') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            return $base . (['genitive'=>'и','dative'=>'и','accusative'=>'ь','instrumental'=>'ью','prepositional'=>'и'][$case] ?? 'ь');
        }
        if ($last === 'а') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            return $base . (['genitive'=>'ы','dative'=>'е','accusative'=>'у','instrumental'=>'ой','prepositional'=>'е'][$case] ?? 'а');
        }
        if ($last === 'я') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            return $base . (['genitive'=>'и','dative'=>'е','accusative'=>'ю','instrumental'=>'ей','prepositional'=>'е'][$case] ?? 'я');
        }
        if ($last === 'о' || $last === 'е') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            if ($case === 'accusative') return $word;
            return $base . (['genitive'=>'а','dative'=>'у','instrumental'=>'м','prepositional'=>'е'][$case] ?? $last);
        }
        if (preg_match('/[бвгджзйклмнпрстфхцчшщ]$/u', $last)) {
            if ($case === 'accusative') return $word;
            return $word . (['genitive'=>'а','dative'=>'у','instrumental'=>'ом','prepositional'=>'е'][$case] ?? '');
        }
        return $word;
    }

    /**
     * Ищет первую кавычку (любого типа).
     *
     * @return int|false
     */
    private static function findFirstQuote(string $text)
    {
        $quotes   = ['«', '»', '"'];
        $firstPos = false;
        foreach ($quotes as $q) {
            $pos = mb_strpos($text, $q, 0, 'UTF-8');
            if ($pos !== false && ($firstPos === false || $pos < $firstPos)) {
                $firstPos = $pos;
            }
        }
        return $firstPos;
    }

    /**
     * Проверяет, является ли слово предлогом.
     */
    private static function isPreposition(string $word): bool
    {
        static $prepositions = ['в', 'на', 'по', 'из', 'от', 'для', 'с', 'у', 'о', 'об', 'без', 'до', 'при', 'к', 'за', 'над', 'под', 'перед', 'между', 'и'];
        return in_array(mb_strtolower($word, 'UTF-8'), $prepositions, true);
    }

    /**
     * Копирует регистр первой буквы из $source в $target.
     */
    private static function matchCase(string $source, string $target): string
    {
        if (empty($source) || empty($target)) return $target;
        $first = mb_substr($source, 0, 1, 'UTF-8');
        if (mb_strtoupper($first, 'UTF-8') === $first) {
            return mb_strtoupper(mb_substr($target, 0, 1, 'UTF-8'), 'UTF-8')
                . mb_substr($target, 1, null, 'UTF-8');
        }
        return $target;
    }
}