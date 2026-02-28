<?php


namespace Core;


class PersonNameDeclension
{

    // Расширенный словарь исключений для имён
    private static $nameExceptions = [
        // Мужские имена (русские)
        'Александр' => ['genitive' => 'Александра', 'dative' => 'Александру', 'accusative' => 'Александра', 'instrumental' => 'Александром', 'prepositional' => 'Александре'],
        'Алексей' => ['genitive' => 'Алексея', 'dative' => 'Алексею', 'accusative' => 'Алексея', 'instrumental' => 'Алексеем', 'prepositional' => 'Алексее'],
        'Андрей' => ['genitive' => 'Андрея', 'dative' => 'Андрею', 'accusative' => 'Андрея', 'instrumental' => 'Андреем', 'prepositional' => 'Андрее'],
        'Антон' => ['genitive' => 'Антона', 'dative' => 'Антону', 'accusative' => 'Антона', 'instrumental' => 'Антоном', 'prepositional' => 'Антоне'],
        'Василий' => ['genitive' => 'Василия', 'dative' => 'Василию', 'accusative' => 'Василия', 'instrumental' => 'Василием', 'prepositional' => 'Василии'],
        'Виктор' => ['genitive' => 'Виктора', 'dative' => 'Виктору', 'accusative' => 'Виктора', 'instrumental' => 'Виктором', 'prepositional' => 'Викторе'],
        'Владислав' => ['genitive' => 'Владислава', 'dative' => 'Владиславу', 'accusative' => 'Владислава', 'instrumental' => 'Владиславом', 'prepositional' => 'Владиславе'],
        'Вячеслав' => ['genitive' => 'Вячеслава', 'dative' => 'Вячеславу', 'accusative' => 'Вячеслава', 'instrumental' => 'Вячеславом', 'prepositional' => 'Вячеславе'],
        'Дамир' => ['genitive' => 'Дамира', 'dative' => 'Дамиру', 'accusative' => 'Дамира', 'instrumental' => 'Дамиром', 'prepositional' => 'Дамире'],
        'Дмитрий' => ['genitive' => 'Дмитрия', 'dative' => 'Дмитрию', 'accusative' => 'Дмитрия', 'instrumental' => 'Дмитрием', 'prepositional' => 'Дмитрии'],
        'Евгений' => ['genitive' => 'Евгения', 'dative' => 'Евгению', 'accusative' => 'Евгения', 'instrumental' => 'Евгением', 'prepositional' => 'Евгении'],
        'Иван' => ['genitive' => 'Ивана', 'dative' => 'Ивану', 'accusative' => 'Ивана', 'instrumental' => 'Иваном', 'prepositional' => 'Иване'],
        'Мнацакан' => ['genitive' => 'Мнацакана', 'dative' => 'Мнацакану', 'accusative' => 'Мнацакана', 'instrumental' => 'Мнацаканом', 'prepositional' => 'Мнацакане'],
        'Михаил' => ['genitive' => 'Михаила', 'dative' => 'Михаилу', 'accusative' => 'Михаила', 'instrumental' => 'Михаилом', 'prepositional' => 'Михаиле'],
        'Николай' => ['genitive' => 'Николая', 'dative' => 'Николаю', 'accusative' => 'Николая', 'instrumental' => 'Николаем', 'prepositional' => 'Николае'],
        'Павел' => ['genitive' => 'Павла', 'dative' => 'Павлу', 'accusative' => 'Павла', 'instrumental' => 'Павлом', 'prepositional' => 'Павле'],
        'Пётр' => ['genitive' => 'Петра', 'dative' => 'Петру', 'accusative' => 'Петра', 'instrumental' => 'Петром', 'prepositional' => 'Петре'],
        'Сергей' => ['genitive' => 'Сергея', 'dative' => 'Сергею', 'accusative' => 'Сергея', 'instrumental' => 'Сергеем', 'prepositional' => 'Сергее'],
        'Тест' => ['genitive' => 'Теста', 'dative' => 'Тесту', 'accusative' => 'Теста', 'instrumental' => 'Тестом', 'prepositional' => 'Тесте'],
        'Эльман' => ['genitive' => 'Эльмана', 'dative' => 'Эльману', 'accusative' => 'Эльмана', 'instrumental' => 'Эльманом', 'prepositional' => 'Эльмане'],
        'Юрий' => ['genitive' => 'Юрия', 'dative' => 'Юрию', 'accusative' => 'Юрия', 'instrumental' => 'Юрием', 'prepositional' => 'Юрии'],
        'Ярослав' => ['genitive' => 'Ярослава', 'dative' => 'Ярославу', 'accusative' => 'Ярослава', 'instrumental' => 'Ярославом', 'prepositional' => 'Ярославе'],

        // Мужские имена (восточные)
        'Айсин' => ['genitive' => 'Айсина', 'dative' => 'Айсину', 'accusative' => 'Айсина', 'instrumental' => 'Айсином', 'prepositional' => 'Айсине'],
        'Карапетян' => ['genitive' => 'Карапетяна', 'dative' => 'Карапетяну', 'accusative' => 'Карапетяна', 'instrumental' => 'Карапетяном', 'prepositional' => 'Карапетяне'],
        'Муранов' => ['genitive' => 'Муранова', 'dative' => 'Муранову', 'accusative' => 'Муранова', 'instrumental' => 'Мурановым', 'prepositional' => 'Муранове'],
        'Мурыгин' => ['genitive' => 'Мурыгина', 'dative' => 'Мурыгину', 'accusative' => 'Мурыгина', 'instrumental' => 'Мурыгиным', 'prepositional' => 'Мурыгине'],
        'Нечаевский' => ['genitive' => 'Нечаевского', 'dative' => 'Нечаевскому', 'accusative' => 'Нечаевского', 'instrumental' => 'Нечаевским', 'prepositional' => 'Нечаевском'],
        'Сиднев' => ['genitive' => 'Сиднева', 'dative' => 'Сидневу', 'accusative' => 'Сиднева', 'instrumental' => 'Сидневым', 'prepositional' => 'Сидневе'],
        'Чиркин' => ['genitive' => 'Чиркина', 'dative' => 'Чиркину', 'accusative' => 'Чиркина', 'instrumental' => 'Чиркиным', 'prepositional' => 'Чиркине'],
        'Ядров' => ['genitive' => 'Ядрова', 'dative' => 'Ядрову', 'accusative' => 'Ядрова', 'instrumental' => 'Ядровым', 'prepositional' => 'Ядрове'],
        'Яцковский' => ['genitive' => 'Яцковского', 'dative' => 'Яцковскому', 'accusative' => 'Яцковского', 'instrumental' => 'Яцковским', 'prepositional' => 'Яцковском'],

        // Женские имена
        'Александра' => ['genitive' => 'Александры', 'dative' => 'Александре', 'accusative' => 'Александру', 'instrumental' => 'Александрой', 'prepositional' => 'Александре'],
        'Анна' => ['genitive' => 'Анны', 'dative' => 'Анне', 'accusative' => 'Анну', 'instrumental' => 'Анной', 'prepositional' => 'Анне'],
        'Валентина' => ['genitive' => 'Валентины', 'dative' => 'Валентине', 'accusative' => 'Валентину', 'instrumental' => 'Валентиной', 'prepositional' => 'Валентине'],
        'Вера' => ['genitive' => 'Веры', 'dative' => 'Вере', 'accusative' => 'Веру', 'instrumental' => 'Верой', 'prepositional' => 'Вере'],
        'Виктория' => ['genitive' => 'Виктории', 'dative' => 'Виктории', 'accusative' => 'Викторию', 'instrumental' => 'Викторией', 'prepositional' => 'Виктории'],
        'Галина' => ['genitive' => 'Галины', 'dative' => 'Галине', 'accusative' => 'Галину', 'instrumental' => 'Галиной', 'prepositional' => 'Галине'],
        'Дарья' => ['genitive' => 'Дарьи', 'dative' => 'Дарье', 'accusative' => 'Дарью', 'instrumental' => 'Дарьей', 'prepositional' => 'Дарье'],
        'Елена' => ['genitive' => 'Елены', 'dative' => 'Елене', 'accusative' => 'Елену', 'instrumental' => 'Еленой', 'prepositional' => 'Елене'],
        'Марина' => ['genitive' => 'Марины', 'dative' => 'Марине', 'accusative' => 'Марину', 'instrumental' => 'Мариной', 'prepositional' => 'Марине'],
        'Мария' => ['genitive' => 'Марии', 'dative' => 'Марии', 'accusative' => 'Марию', 'instrumental' => 'Марией', 'prepositional' => 'Марии'],
        'Надежда' => ['genitive' => 'Надежды', 'dative' => 'Надежде', 'accusative' => 'Надежду', 'instrumental' => 'Надеждой', 'prepositional' => 'Надежде'],
        'Наталия' => ['genitive' => 'Наталии', 'dative' => 'Наталии', 'accusative' => 'Наталию', 'instrumental' => 'Наталией', 'prepositional' => 'Наталии'],
        'Наталья' => ['genitive' => 'Натальи', 'dative' => 'Наталье', 'accusative' => 'Наталью', 'instrumental' => 'Натальей', 'prepositional' => 'Наталье'],
        'Ольга' => ['genitive' => 'Ольги', 'dative' => 'Ольге', 'accusative' => 'Ольгу', 'instrumental' => 'Ольгой', 'prepositional' => 'Ольге'],
        'Светлана' => ['genitive' => 'Светланы', 'dative' => 'Светлане', 'accusative' => 'Светлану', 'instrumental' => 'Светланой', 'prepositional' => 'Светлане'],
        'Татьяна' => ['genitive' => 'Татьяны', 'dative' => 'Татьяне', 'accusative' => 'Татьяну', 'instrumental' => 'Татьяной', 'prepositional' => 'Татьяне'],

        // Фамилии как имена (для случаев, когда имя совпадает с фамилией)
        'Сотрудников' => ['genitive' => 'Сотрудникова', 'dative' => 'Сотрудникову', 'accusative' => 'Сотрудникова', 'instrumental' => 'Сотрудниковым', 'prepositional' => 'Сотрудникове'],
        'Сотрудник' => ['genitive' => 'Сотрудника', 'dative' => 'Сотруднику', 'accusative' => 'Сотрудника', 'instrumental' => 'Сотрудником', 'prepositional' => 'Сотруднике']
    ];

    // Список имён, которые не склоняются (обычно иностранные или сокращения)
    private static $indeclinableNames = [
        'Drizhak', 'Yarmarkina', 'Vorobyev', 'Yurkov', 'test2'
    ];

    /**
     * Склонение ФИО по падежам
     */
    public static function decline($fullName, $case, $gender = null)
    {
        // Если это латиница или тестовые данные - возвращаем как есть
        if (self::isLatinOrTest($fullName)) {
            return $fullName;
        }

        // Разбираем ФИО
        $parts = preg_split('/\s+/u', trim($fullName));

        if (count($parts) < 2) {
            return $fullName;
        }

        // Определяем пол, если не указан
        if ($gender === null) {
            $gender = self::detectGender($parts);
        }

        // Распределяем по частям
        if (count($parts) == 2) {
            $lastname = $parts[0];
            $firstname = $parts[1];
            $patronymic = '';
        } else {
            $lastname = $parts[0];
            $firstname = $parts[1];
            $patronymic = $parts[2];
        }

        // Проверяем на латиницу в отдельных частях
        if (self::isLatinString($lastname) || self::isLatinString($firstname)) {
            return $fullName;
        }

        // Склоняем каждую часть
        $declinedLast = self::declineLastName($lastname, $case, $gender);
        $declinedFirst = self::declineFirstName($firstname, $case, $gender);
        $declinedPatro = (!empty($patronymic) && !self::isLatinString($patronymic)) ?
            self::declinePatronymic($patronymic, $case, $gender) : '';

        return trim($declinedLast . ' ' . $declinedFirst . ' ' . $declinedPatro);
    }

    /**
     * Проверка на латиницу или тестовые данные
     */
    private static function isLatinOrTest($string)
    {
        // Проверка на тестовые данные
        if (strpos($string, 'sdfdsf') !== false || strpos($string, 'апавп') !== false) {
            return true;
        }

        // Проверка на латиницу
        return self::isLatinString($string);
    }

    /**
     * Проверка, содержит ли строка латиницу
     */
    private static function isLatinString($string)
    {
        // Убираем пробелы и проверяем каждый символ
        $noSpaces = str_replace(' ', '', $string);
        if (empty($noSpaces)) return false;

        // Если есть кириллица - возвращаем false
        if (preg_match('/[а-яА-ЯёЁ]/u', $string)) {
            return false;
        }

        // Если есть латиница и нет кириллицы
        return preg_match('/[a-zA-Z]/', $string);
    }

    /**
     * Определение пола по отчеству или имени
     */
    private static function detectGender($parts)
    {
        // Сначала проверяем по отчеству
        if (count($parts) >= 3 && !empty($parts[2]) && !self::isLatinString($parts[2])) {
            $patronymic = $parts[2];
            if (mb_substr($patronymic, -2) == 'на') {
                return 'female'; // Ивановна, Петровна
            } elseif (mb_substr($patronymic, -2) == 'ич' || mb_substr($patronymic, -3) == 'ович') {
                return 'male'; // Иванович, Петрович
            }
        }

        // Если нет отчества, проверяем по имени
        if (count($parts) >= 2 && !empty($parts[1]) && !self::isLatinString($parts[1])) {
            $firstname = $parts[1];
            $lastChar = mb_substr($firstname, -1);

            // Женские имена обычно заканчиваются на -а, -я
            if (in_array($lastChar, ['а', 'я'])) {
                // Но есть исключения - мужские имена на -а
                $maleNamesA = ['Никита', 'Илья', 'Кузьма', 'Фома', 'Лука', 'Савелий'];
                if (!in_array($firstname, $maleNamesA)) {
                    return 'female';
                }
            }
        }

        return 'male';
    }

    /**
     * Склонение фамилии
     */
    private static function declineLastName($lastname, $case, $gender)
    {
        if ($case == 'nominative') {
            return $lastname;
        }

        // Проверяем на несклоняемые фамилии
        if (self::isIndeclinable($lastname)) {
            return $lastname;
        }

        $lastChar = mb_substr($lastname, -1);
        $lastTwoChars = mb_substr($lastname, -2);
        $lastThreeChars = mb_substr($lastname, -3);

        if ($gender == 'male') {
            // Мужские фамилии на -ов, -ев, -ин
            if (in_array($lastTwoChars, ['ов', 'ев', 'ин']) || in_array($lastThreeChars, ['шин'])) {
                $endings = [
                    'genitive' => 'а',
                    'dative' => 'у',
                    'accusative' => 'а',
                    'instrumental' => 'ым',
                    'prepositional' => 'е'
                ];
                return isset($endings[$case]) ? $lastname . $endings[$case] : $lastname;
            }

            // Мужские фамилии на -ский, -цкий
            if (in_array($lastTwoChars, ['ий', 'ый'])) {
                $base = mb_substr($lastname, 0, -2);
                $endings = [
                    'genitive' => 'ого',
                    'dative' => 'ому',
                    'accusative' => 'ого',
                    'instrumental' => 'ым',
                    'prepositional' => 'ом'
                ];
                return isset($endings[$case]) ? $base . $endings[$case] : $lastname;
            }

            // Мужские фамилии на согласную
            if (preg_match('/[бвгджзйклмнпрстфхцчшщ]$/u', $lastChar)) {
                $endings = [
                    'genitive' => 'а',
                    'dative' => 'у',
                    'accusative' => 'а',
                    'instrumental' => 'ом',
                    'prepositional' => 'е'
                ];
                return isset($endings[$case]) ? $lastname . $endings[$case] : $lastname;
            }
        } else {
            // Женские фамилии на -ова, -ева, -ина
            if (in_array($lastTwoChars, ['ва', 'на']) && mb_strlen($lastname) > 2) {
                $base = mb_substr($lastname, 0, -1);
                if ($case == 'accusative') {
                    return $base . 'у'; // Иванову
                } elseif ($case != 'nominative') {
                    return $base . 'ой'; // Ивановой
                }
            }

            // Женские фамилии на -ая, -яя
            if (in_array($lastTwoChars, ['ая', 'яя'])) {
                $base = mb_substr($lastname, 0, -2);
                if ($case == 'accusative') {
                    return $base . 'ую';
                } elseif ($case != 'nominative') {
                    return $base . 'ой';
                }
            }
        }

        return $lastname;
    }

    /**
     * Склонение имени
     */
    private static function declineFirstName($firstname, $case, $gender)
    {
        if ($case == 'nominative') {
            return $firstname;
        }

        // Проверяем в исключениях
        if (isset(self::$nameExceptions[$firstname]) && isset(self::$nameExceptions[$firstname][$case])) {
            return self::$nameExceptions[$firstname][$case];
        }

        $lastChar = mb_substr($firstname, -1);
        $lastTwoChars = mb_substr($firstname, -2);

        // Имена на -ий (мужские)
        if ($lastTwoChars == 'ий' && $gender == 'male') {
            $base = mb_substr($firstname, 0, -2);
            $endings = [
                'genitive' => 'ия',
                'dative' => 'ию',
                'accusative' => 'ия',
                'instrumental' => 'ием',
                'prepositional' => 'ии'
            ];
            return isset($endings[$case]) ? $base . $endings[$case] : $firstname;
        }

        // Имена на -а (женские)
        if ($lastChar == 'а' && $gender == 'female') {
            $base = mb_substr($firstname, 0, -1);
            $endings = [
                'genitive' => 'ы',
                'dative' => 'е',
                'accusative' => 'у',
                'instrumental' => 'ой',
                'prepositional' => 'е'
            ];
            return isset($endings[$case]) ? $base . $endings[$case] : $firstname;
        }

        // Имена на -я (женские)
        if ($lastChar == 'я' && $gender == 'female') {
            $base = mb_substr($firstname, 0, -1);
            $endings = [
                'genitive' => 'и',
                'dative' => 'е',
                'accusative' => 'ю',
                'instrumental' => 'ей',
                'prepositional' => 'е'
            ];
            return isset($endings[$case]) ? $base . $endings[$case] : $firstname;
        }

        // Мужские имена на согласную
        if ($gender == 'male' && preg_match('/[бвгджзйклмнпрстфхцчшщ]$/u', $lastChar)) {
            $endings = [
                'genitive' => 'а',
                'dative' => 'у',
                'accusative' => 'а',
                'instrumental' => 'ом',
                'prepositional' => 'е'
            ];
            return isset($endings[$case]) ? $firstname . $endings[$case] : $firstname;
        }

        return $firstname;
    }

    /**
     * Склонение отчества
     */
    private static function declinePatronymic($patronymic, $case, $gender)
    {
        if ($case == 'nominative') {
            return $patronymic;
        }

        if ($gender == 'male') {
            // Мужские отчества (Иванович, Петрович)
            if (mb_substr($patronymic, -2) == 'ич') {
                $endings = [
                    'genitive' => 'а',
                    'dative' => 'у',
                    'accusative' => 'а',
                    'instrumental' => 'ем',
                    'prepositional' => 'е'
                ];
                return isset($endings[$case]) ? $patronymic . $endings[$case] : $patronymic;
            }
        } else {
            // Женские отчества (Ивановна, Петровна)
            if (mb_substr($patronymic, -2) == 'на') {
                $base = mb_substr($patronymic, 0, -1);
                $endings = [
                    'genitive' => 'ы',
                    'dative' => 'е',
                    'accusative' => 'у',
                    'instrumental' => 'ой',
                    'prepositional' => 'е'
                ];
                return isset($endings[$case]) ? $base . $endings[$case] : $patronymic;
            }
        }

        return $patronymic;
    }

    /**
     * Проверка на несклоняемые фамилии
     */
    private static function isIndeclinable($word)
    {
        // Фамилии на -о, -у, -э, -и, -е обычно не склоняются
        if (preg_match('/[оуэие]$/u', $word)) {
            return true;
        }

        return in_array($word, self::$indeclinableNames);
    }
}
/*
// ТЕСТИРОВАНИЕ ВСЕХ СОТРУДНИКОВ
echo "=== ТЕСТИРОВАНИЕ СОТРУДНИКОВ ===\n\n";

$employees = [
    'Романова Дарья Валентиновна',
    'Карапетян Мнацакан Амбарцумович',
    'Скутина Наталья Михайловна',
    'Коновалова Елена Михайловна',
    'Жамалетдинова Мария Анатольевна',
    'Боязитов Эльман Мансурович',
    'Гордеев Сергей Владиславович',
    'Кирюхин Андрей Александрович',
    'Айсин Дамир Ринатович',
    'Абаимова Марина Юрьевна',
    'Евстигнеев Вячеслав',
    'Тестов Тест Тестович',
    'Савелина Вера Александровна',
    'Филиппов Сергей Михайлович',
    'Ляхова Елена Владимировна',
    'Сотрудников Сотрудник Сотрудникович',
    'Иванов Иван Иванович',
    'Сиднев Андрей Вячеславович',
    'Исакова Наталия Анатольевна',
    'Кузнецова Ольга Олеговна',
    'Краснощекова Татьяна Сергеевна',
    'Яцковский Андрей Викторович',
    'Титова Елена Викторовна',
    'Стрекалова Виктория Сергеевна',
    'Ядров Андрей Юрьевич',
    'Дудкина Елена Сергеевна',
    'Молянова Надежда Владимировна',
    'Нечаевский Александр Владимирович',
    'Муранов Алексей Сергеевич',
    'Мурыгин Алексей Иванович',
    'Чиркин Александр Сергеевич',
    'Кузьмин Василий Александрович',
    'Кондратюк Елена Васильевна',
    'Дубровина Галина Григорьевна'
];

foreach ($employees as $employee) {
    echo "Исходное: $employee\n";
    echo 'Родительный: ' . PersonNameDeclension::decline($employee, 'genitive') . "\n";
    echo 'Дательный: ' . PersonNameDeclension::decline($employee, 'dative') . "\n";
    echo 'Творительный: ' . PersonNameDeclension::decline($employee, 'instrumental') . "\n";
    echo str_repeat('-', 80) . "\n";
}

// Тест с ФИО без отчества
echo "\n=== ТЕСТ БЕЗ ОТЧЕСТВА ===\n";
$noPatronymic = 'Евстигнеев Вячеслав';
echo "Исходное: $noPatronymic\n";
echo 'Родительный: ' . PersonNameDeclension::decline($noPatronymic, 'genitive') . "\n";
echo 'Дательный: ' . PersonNameDeclension::decline($noPatronymic, 'dative') . "\n";*/