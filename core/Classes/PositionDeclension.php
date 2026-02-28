<?php

namespace Core;

/**
 * Класс для склонения названий должностей по падежам.
 *
 * Поддерживает:
 * - Простые должности: "Директор", "Разработчик"
 * - Составные с управлением: "Начальник управления", "Заведующий отделом"
 * - С прилагательными: "Главный инспектор", "Первый заместитель"
 * - ВРИО: "ВРИО Губернатора"
 * - С тире: "Заместитель начальника управления – заведующий отделом"
 * - Фиксированные фразы: "Московской области", "социального развития" и др.
 */
class PositionDeclension
{
    // -----------------------------------------------------------------------
    // Словари
    // -----------------------------------------------------------------------

    /**
     * Слова, которые не склоняются сами и не вызывают управления.
     */
    private static $fixedWords = ['врио', 'ВРИО', 'и.о.', 'И.О.', 'и/о'];

    /**
     * Фиксированные фразы, которые не склоняются (уже в нужном падеже или несклоняемые).
     * Порядок важен: более длинные фразы должны быть раньше.
     */
    private static $fixedPhrases = [
        'Правительства Московской области',
        'Московской области',
        'социального развития',
        'финансового контроля и аудита',
        'мониторинга и сводного анализа',
        'обеспечения контрольных функций',
        'внутреннего финансового аудита',
        'внутреннего аудита',
        'финансового контроля',
        'контрольных функций',
        'финансового менеджмента',
    ];

    /**
     * Словарь именительных форм существительных должностей.
     * Ключ — именительный падеж (нижний регистр).
     * Для каждого слова указаны все косвенные падежи.
     */
    private static $nouns = [
        'министр' => [
            'genitive'      => 'министра',
            'dative'        => 'министру',
            'accusative'    => 'министра',
            'instrumental'  => 'министром',
            'prepositional' => 'министре',
        ],
        'заместитель' => [
            'genitive'      => 'заместителя',
            'dative'        => 'заместителю',
            'accusative'    => 'заместителя',
            'instrumental'  => 'заместителем',
            'prepositional' => 'заместителе',
        ],
        'начальник' => [
            'genitive'      => 'начальника',
            'dative'        => 'начальнику',
            'accusative'    => 'начальника',
            'instrumental'  => 'начальником',
            'prepositional' => 'начальнике',
        ],
        'заведующий' => [
            'genitive'      => 'заведующего',
            'dative'        => 'заведующему',
            'accusative'    => 'заведующего',
            'instrumental'  => 'заведующим',
            'prepositional' => 'заведующем',
        ],
        'директор' => [
            'genitive'      => 'директора',
            'dative'        => 'директору',
            'accusative'    => 'директора',
            'instrumental'  => 'директором',
            'prepositional' => 'директоре',
        ],
        'специалист' => [
            'genitive'      => 'специалиста',
            'dative'        => 'специалисту',
            'accusative'    => 'специалиста',
            'instrumental'  => 'специалистом',
            'prepositional' => 'специалисте',
        ],
        'инспектор' => [
            'genitive'      => 'инспектора',
            'dative'        => 'инспектору',
            'accusative'    => 'инспектора',
            'instrumental'  => 'инспектором',
            'prepositional' => 'инспекторе',
        ],
        'консультант' => [
            'genitive'      => 'консультанта',
            'dative'        => 'консультанту',
            'accusative'    => 'консультанта',
            'instrumental'  => 'консультантом',
            'prepositional' => 'консультанте',
        ],
        'советник' => [
            'genitive'      => 'советника',
            'dative'        => 'советнику',
            'accusative'    => 'советника',
            'instrumental'  => 'советником',
            'prepositional' => 'советнике',
        ],
        'разработчик' => [
            'genitive'      => 'разработчика',
            'dative'        => 'разработчику',
            'accusative'    => 'разработчика',
            'instrumental'  => 'разработчиком',
            'prepositional' => 'разработчике',
        ],
        'губернатор' => [
            'genitive'      => 'губернатора',
            'dative'        => 'губернатору',
            'accusative'    => 'губернатора',
            'instrumental'  => 'губернатором',
            'prepositional' => 'губернаторе',
        ],
        'отдел' => [
            'genitive'      => 'отдела',
            'dative'        => 'отделу',
            'accusative'    => 'отдел',
            'instrumental'  => 'отделом',
            'prepositional' => 'отделе',
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
        'служба' => [
            'genitive'      => 'службы',
            'dative'        => 'службе',
            'accusative'    => 'службу',
            'instrumental'  => 'службой',
            'prepositional' => 'службе',
        ],
        'сектор' => [
            'genitive'      => 'сектора',
            'dative'        => 'сектору',
            'accusative'    => 'сектор',
            'instrumental'  => 'сектором',
            'prepositional' => 'секторе',
        ],
    ];

    /**
     * Обратный индекс: словоформа → именительный падеж.
     * Строится один раз при первом обращении.
     */
    private static $reverseIndex = null;

    /**
     * Слова, требующие управления: после них следующее существительное
     * ставится в указанный падеж (именительная форма управляющего слова → падеж зависимого).
     *
     * Управление применяется всегда (независимо от склоняемого падежа всей должности),
     * потому что зависимое слово остаётся в своём фиксированном падеже управления.
     */
    private static $governmentRules = [
        'заведующий'  => 'instrumental', // заведующий ЧЕМ → отделом
        'начальник'   => 'genitive',      // начальник ЧЕГО → управления
        'директор'    => 'genitive',      // директор ЧЕГО → департамента
        'заместитель' => 'genitive',      // заместитель КОГО → директора
        'министр'     => 'genitive',      // министр ЧЕГО → ...
        'губернатор'  => 'genitive',
        'советник'    => 'genitive',      // советник КОГО → министра
        'консультант' => 'genitive',      // консультант ЧЕГО → отдела
        'инспектор'   => 'genitive',      // инспектор ЧЕГО → ...
    ];

    // -----------------------------------------------------------------------
    // Публичный API
    // -----------------------------------------------------------------------

    /**
     * Склоняет название должности в указанный падеж.
     *
     * @param string $position  Название должности (именительный падеж или уже склонённое)
     * @param string $case      Падеж: nominative|genitive|dative|accusative|instrumental|prepositional
     * @param string $gender    Пол: male|female (влияет только на прилагательные)
     * @return string
     */
    public static function decline(string $position, string $case, string $gender = 'male'): string
    {
        $position = trim($position);
        if (empty($position)) {
            return '';
        }

        // Тестовые данные — не трогаем
        if (self::isTestData($position)) {
            return $position;
        }

        // Именительный — возвращаем как есть (после нормализации пробелов)
        if ($case === 'nominative') {
            return $position;
        }

        // Если строка содержит тире — разбиваем на части, склоняем каждую
        if (preg_match('/\s*[–—-]\s*/u', $position)) {
            return self::declineWithDash($position, $case, $gender);
        }

        return self::declineSegment($position, $case, $gender);
    }

    // -----------------------------------------------------------------------
    // Приватные методы
    // -----------------------------------------------------------------------

    /**
     * Склонение строки, содержащей тире (несколько должностей).
     */
    private static function declineWithDash(string $position, string $case, string $gender): string
    {
        // Разбиваем по тире, сохраняя разделитель
        $parts = preg_split('/(\s*[–—-]\s*)/u', $position, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = [];
        foreach ($parts as $part) {
            // Если это разделитель — оставляем как есть
            if (preg_match('/^[\s–—-]+$/u', $part)) {
                $result[] = trim($part);
            } else {
                $result[] = self::declineSegment(trim($part), $case, $gender);
            }
        }
        return implode(' ', $result);
    }

    /**
     * Склонение одного сегмента должности (без тире).
     */
    private static function declineSegment(string $segment, string $case, string $gender): string
    {
        // Нормализуем: приводим к именительному (на случай если подано уже в косвенном)
        $segment = self::normalizeToNominative($segment);

        // ВРИО/и.о. в начале — сохраняем, остальное склоняем
        if (preg_match('/^(ВРИО|врио|и\.о\.|И\.О\.)\s+(.+)$/u', $segment, $m)) {
            return $m[1] . ' ' . self::declineSegment($m[2], $case, $gender);
        }

        $words = preg_split('/\s+/u', $segment);
        $result = [];
        $i = 0;
        $n = count($words);

        while ($i < $n) {
            $word = $words[$i];
            if ($word === '') {
                $i++;
                continue;
            }

            // 1. Фиксированное слово (врио, и.о.) — не трогаем
            if (self::isFixedWord($word)) {
                $result[] = $word;
                $i++;
                continue;
            }

            // 2. Фиксированная фраза — берём из словаря, первое слово сохраняет регистр оригинала
            $phraseLen = self::matchFixedPhrase($words, $i);
            if ($phraseLen > 0) {
                $canonicalPhrase = self::getCanonicalPhrase($words, $i, $phraseLen);
                // Регистр первого слова берём из оригинала входной строки
                $canonicalPhrase[0] = self::matchCase($words[$i], $canonicalPhrase[0]);
                $result[] = implode(' ', $canonicalPhrase);
                $i += $phraseLen;
                continue;
            }

            // 3. Определяем именительную форму текущего слова
            $nominative = self::toNominative($word);

            // 4. Прилагательное?
            if (self::isAdjective($nominative)) {
                $result[] = self::declineAdjective($nominative, $case, $gender);
                $i++;
                continue;
            }

            // 5. Существительное из словаря
            $lc = mb_strtolower($nominative, 'UTF-8');
            if (isset(self::$nouns[$lc])) {
                // Сохраняем регистр первой буквы оригинального слова
                $declined = self::$nouns[$lc][$case] ?? $nominative;
                $declined = self::matchCase($word, $declined);
                $result[] = $declined;

                // Управление: следующее слово ставим в нужный падеж
                if (isset(self::$governmentRules[$lc]) && $i + 1 < $n) {
                    $govCase = self::$governmentRules[$lc];
                    $i++;

                    // Пропускаем фиксированные слова
                    while ($i < $n && self::isFixedWord($words[$i])) {
                        $result[] = $words[$i];
                        $i++;
                    }

                    if ($i < $n) {
                        // Проверяем фиксированную фразу
                        $phraseLen = self::matchFixedPhrase($words, $i);
                        if ($phraseLen > 0) {
                            $canonicalPhrase = self::getCanonicalPhrase($words, $i, $phraseLen);
                            $canonicalPhrase[0] = self::matchCase($words[$i], $canonicalPhrase[0]);
                            $result[] = implode(' ', $canonicalPhrase);
                            $i += $phraseLen;
                        } else {
                            // Следующее слово — зависимое, ставим в падеж управления
                            $depWord = $words[$i];
                            $depNom  = self::toNominative($depWord);
                            $depLc   = mb_strtolower($depNom, 'UTF-8');

                            if (isset(self::$nouns[$depLc])) {
                                $declined = self::$nouns[$depLc][$govCase] ?? $depNom;
                                $result[] = self::matchCase($depWord, $declined);
                            } else {
                                $result[] = self::declineNounByRules($depNom, $govCase);
                            }
                            $i++;

                            // Цепочка управления: если зависимое само управляет следующим словом
                            // (например "заместитель → начальника → отдела")
                            if (isset(self::$governmentRules[$depLc]) && $i < $n) {
                                $chainGovCase = self::$governmentRules[$depLc];

                                // Пропускаем фиксированные слова
                                while ($i < $n && self::isFixedWord($words[$i])) {
                                    $result[] = $words[$i];
                                    $i++;
                                }

                                if ($i < $n) {
                                    $phraseLen2 = self::matchFixedPhrase($words, $i);
                                    if ($phraseLen2 > 0) {
                                        $canonicalPhrase2 = self::getCanonicalPhrase($words, $i, $phraseLen2);
                                        $canonicalPhrase2[0] = self::matchCase($words[$i], $canonicalPhrase2[0]);
                                        $result[] = implode(' ', $canonicalPhrase2);
                                        $i += $phraseLen2;
                                    } else {
                                        $chainDepWord = $words[$i];
                                        $chainDepNom  = self::toNominative($chainDepWord);
                                        $chainDepLc   = mb_strtolower($chainDepNom, 'UTF-8');
                                        if (isset(self::$nouns[$chainDepLc])) {
                                            $chainDeclined = self::$nouns[$chainDepLc][$chainGovCase] ?? $chainDepNom;
                                            $result[] = self::matchCase($chainDepWord, $chainDeclined);
                                        } else {
                                            $result[] = self::declineNounByRules($chainDepNom, $chainGovCase);
                                        }
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                    continue;
                }

                $i++;
                continue;
            }

            // 6. Обычное существительное (не в словаре) — по правилам
            $result[] = self::declineNounByRules($nominative, $case);
            $i++;
        }

        return implode(' ', $result);
    }

    /**
     * Нормализация сегмента: пытаемся привести слова из косвенных форм к именительному.
     * Нужно для случаев, когда на вход подают уже склонённую строку.
     * Фиксированные фразы не трогаем.
     */
    private static function normalizeToNominative(string $segment): string
    {
        self::buildReverseIndex();
        $words = preg_split('/\s+/u', $segment);
        $result = [];
        $i = 0;
        $n = count($words);

        while ($i < $n) {
            $word = $words[$i];
            if (empty($word)) { $i++; continue; }

            // Фиксированные фразы не нормализуем
            $phraseLen = self::matchFixedPhrase($words, $i);
            if ($phraseLen > 0) {
                for ($j = 0; $j < $phraseLen; $j++) {
                    $result[] = $words[$i + $j];
                }
                $i += $phraseLen;
                continue;
            }

            $lc = mb_strtolower($word, 'UTF-8');
            if (isset(self::$reverseIndex[$lc])) {
                $nom = self::$reverseIndex[$lc];
                $result[] = self::matchCase($word, $nom);
            } else {
                $result[] = $word;
            }
            $i++;
        }
        return implode(' ', $result);
    }

    /**
     * Возвращает слова фиксированной фразы из словаря (каноническое написание).
     */
    private static function getCanonicalPhrase(array $words, int $i, int $phraseLen): array
    {
        foreach (self::$fixedPhrases as $phrase) {
            $pWords = preg_split('/\s+/u', $phrase);
            if (count($pWords) !== $phraseLen) continue;
            $match = true;
            for ($j = 0; $j < $phraseLen; $j++) {
                if (mb_strtolower($words[$i + $j], 'UTF-8') !== mb_strtolower($pWords[$j], 'UTF-8')) {
                    $match = false; break;
                }
            }
            if ($match) {
                // "Московской" всегда с заглавной буквы
                foreach ($pWords as &$pw) {
                    if (mb_strtolower($pw, 'UTF-8') === 'московской') {
                        $pw = 'Московской';
                    }
                }
                unset($pw);
                return $pWords;
            }
        }
        return array_slice($words, $i, $phraseLen);
    }

    /**
     */
    private static function buildReverseIndex(): void
    {
        if (self::$reverseIndex !== null) return;
        self::$reverseIndex = [];
        foreach (self::$nouns as $nom => $forms) {
            foreach ($forms as $form) {
                $lc = mb_strtolower($form, 'UTF-8');
                // Не перезаписываем, если форма совпадает с именительным другого слова
                if (!isset(self::$reverseIndex[$lc])) {
                    self::$reverseIndex[$lc] = $nom;
                }
            }
        }
    }

    /**
     * Приводит слово к именительному по обратному индексу (если есть).
     */
    private static function toNominative(string $word): string
    {
        self::buildReverseIndex();
        $lc = mb_strtolower($word, 'UTF-8');
        if (isset(self::$reverseIndex[$lc])) {
            return self::matchCase($word, self::$reverseIndex[$lc]);
        }
        return $word;
    }

    /**
     * Проверяет, является ли строка тестовыми данными.
     */
    private static function isTestData(string $s): bool
    {
        return mb_stripos($s, 'Тестовый') !== false
            || $s === 'Сотрудниковская';
    }

    /**
     * Проверяет, является ли слово фиксированным (не склоняемым).
     */
    private static function isFixedWord(string $word): bool
    {
        return in_array($word, self::$fixedWords, true)
            || in_array(mb_strtolower($word, 'UTF-8'), array_map('mb_strtolower', self::$fixedWords));
    }

    /**
     * Проверяет, начинается ли массив слов с позиции $i на фиксированную фразу.
     * Возвращает длину фразы (в словах) или 0.
     */
    private static function matchFixedPhrase(array $words, int $i): int
    {
        foreach (self::$fixedPhrases as $phrase) {
            $pWords = preg_split('/\s+/u', $phrase);
            $len = count($pWords);
            if ($i + $len > count($words)) continue;
            $match = true;
            for ($j = 0; $j < $len; $j++) {
                if (mb_strtolower($words[$i + $j], 'UTF-8') !== mb_strtolower($pWords[$j], 'UTF-8')) {
                    $match = false;
                    break;
                }
            }
            if ($match) return $len;
        }
        return 0;
    }

    /**
     * Определяет, является ли слово прилагательным по окончанию.
     */
    private static function isAdjective(string $word): bool
    {
        $endings = ['ый', 'ий', 'ой', 'ая', 'яя', 'ое', 'ее', 'ые', 'ие'];
        foreach ($endings as $e) {
            if (mb_substr($word, -mb_strlen($e, 'UTF-8'), null, 'UTF-8') === $e) {
                return true;
            }
        }
        return false;
    }

    /**
     * Склонение прилагательного.
     */
    private static function declineAdjective(string $word, string $case, string $gender): string
    {
        // Таблица: род → окончание → падежные окончания
        $table = [
            'male' => [
                'ый' => ['genitive' => 'ого', 'dative' => 'ому', 'accusative' => 'ого', 'instrumental' => 'ым',  'prepositional' => 'ом'],
                'ий' => ['genitive' => 'его', 'dative' => 'ему', 'accusative' => 'его', 'instrumental' => 'им',  'prepositional' => 'ем'],
                'ой' => ['genitive' => 'ого', 'dative' => 'ому', 'accusative' => 'ого', 'instrumental' => 'ым',  'prepositional' => 'ом'],
            ],
            'female' => [
                'ая' => ['genitive' => 'ой', 'dative' => 'ой', 'accusative' => 'ую', 'instrumental' => 'ой', 'prepositional' => 'ой'],
                'яя' => ['genitive' => 'ей', 'dative' => 'ей', 'accusative' => 'юю', 'instrumental' => 'ей', 'prepositional' => 'ей'],
            ],
            'neuter' => [
                'ое' => ['genitive' => 'ого', 'dative' => 'ому', 'accusative' => 'ое', 'instrumental' => 'ым', 'prepositional' => 'ом'],
                'ее' => ['genitive' => 'его', 'dative' => 'ему', 'accusative' => 'ее', 'instrumental' => 'им', 'prepositional' => 'ем'],
            ],
        ];

        $genders = $gender === 'female' ? ['female', 'male'] : ['male', 'female', 'neuter'];

        foreach ($genders as $g) {
            if (!isset($table[$g])) continue;
            foreach ($table[$g] as $ending => $cases) {
                $eLen = mb_strlen($ending, 'UTF-8');
                if (mb_substr($word, -$eLen, null, 'UTF-8') === $ending) {
                    $base = mb_substr($word, 0, -$eLen, 'UTF-8');
                    $suffix = $cases[$case] ?? $ending;
                    return $base . $suffix;
                }
            }
        }

        return $word;
    }

    /**
     * Склонение существительного по общим правилам (для слов не из словаря).
     */
    private static function declineNounByRules(string $word, string $case): string
    {
        if ($case === 'nominative') return $word;

        $last  = mb_substr($word, -1, 1, 'UTF-8');
        $last2 = mb_substr($word, -2, 2, 'UTF-8');

        // На -ие (управление → управления)
        if ($last2 === 'ие') {
            $base = mb_substr($word, 0, -2, 'UTF-8');
            $map = ['genitive' => 'ия', 'dative' => 'ию', 'accusative' => 'ие', 'instrumental' => 'ием', 'prepositional' => 'ии'];
            return $base . ($map[$case] ?? $last2);
        }

        // На -ие (тоже)
        if ($last2 === 'ия') {
            $base = mb_substr($word, 0, -2, 'UTF-8');
            $map = ['genitive' => 'ии', 'dative' => 'ии', 'accusative' => 'ию', 'instrumental' => 'ией', 'prepositional' => 'ии'];
            return $base . ($map[$case] ?? $last2);
        }

        // На -ость (должность → должности)
        if (mb_substr($word, -4, 4, 'UTF-8') === 'ость') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            $map = ['genitive' => 'и', 'dative' => 'и', 'accusative' => 'ь', 'instrumental' => 'ью', 'prepositional' => 'и'];
            $suffix = $map[$case] ?? '';
            if ($case === 'accusative') return $base . 'ь';
            return $base . $suffix;
        }

        // На -ь женский род
        if ($last === 'ь') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            $map = ['genitive' => 'и', 'dative' => 'и', 'accusative' => 'ь', 'instrumental' => 'ью', 'prepositional' => 'и'];
            return $base . ($map[$case] ?? 'ь');
        }

        // На -а
        if ($last === 'а') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            $map = ['genitive' => 'ы', 'dative' => 'е', 'accusative' => 'у', 'instrumental' => 'ой', 'prepositional' => 'е'];
            return $base . ($map[$case] ?? 'а');
        }

        // На -я
        if ($last === 'я') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            $map = ['genitive' => 'и', 'dative' => 'е', 'accusative' => 'ю', 'instrumental' => 'ей', 'prepositional' => 'е'];
            return $base . ($map[$case] ?? 'я');
        }

        // На -о или -е
        if ($last === 'о' || $last === 'е') {
            $base = mb_substr($word, 0, -1, 'UTF-8');
            $map = ['genitive' => 'а', 'dative' => 'у', 'accusative' => $word, 'instrumental' => 'м', 'prepositional' => 'е'];
            if ($case === 'accusative') return $word;
            return $base . ($map[$case] ?? $last);
        }

        // Мужской род на согласную
        if (preg_match('/[бвгджзйклмнпрстфхцчшщ]$/u', $last)) {
            $map = ['genitive' => 'а', 'dative' => 'у', 'accusative' => 'а', 'instrumental' => 'ом', 'prepositional' => 'е'];
            return $word . ($map[$case] ?? '');
        }

        return $word;
    }

    /**
     * Копирует регистр первой буквы из $source в $target.
     * Нужно, чтобы "Заместитель" → "Заместителя" (с большой буквы).
     */
    private static function matchCase(string $source, string $target): string
    {
        if (empty($source) || empty($target)) return $target;
        $firstSource = mb_substr($source, 0, 1, 'UTF-8');
        $firstTarget = mb_substr($target, 0, 1, 'UTF-8');
        if (mb_strtoupper($firstSource, 'UTF-8') === $firstSource) {
            return mb_strtoupper($firstTarget, 'UTF-8') . mb_substr($target, 1, null, 'UTF-8');
        }
        return $target;
    }
}