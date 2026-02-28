<?php

namespace Core;


use DateInterval;
use DatePeriod;
use DateTime;
use InvalidArgumentException;

/**
 * Класс Date предназначен для манипулирования датами.
 * @package Core
 * @version 0.1
 */
class Date
{
    private /*array*/ $_get, $_post, $_session, $_headers;

	/**
	 * Констрктор класса Date.
	 */
	public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        //$this->_headers = getallheaders();
    }

	/**
	 * Метод установки значения извне для приватных параметров класса.
	 * @param mixed $key Имя параметра
	 * @param mixed $val Значение параметра
	 * @return void Ничего не возвращает
	 */
    public function set($key, $val){
        $this->$key = $val;
    }


	/**
	 * Метод определения номера квартала по текущему месяцу.
	 *
	 * @return integer Возвращает номер квартала
	 */
	public function getCurrentQuarterNumber(): int
    {
        $mapArray = array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
            6 => 2,
            7 => 3,
            8 => 3,
            9 => 3,
            10 => 4,
            11 => 4,
            12 => 4
        );
        return $mapArray[date('n')];
    }


	/**
	 * Метод возвращает интервал дат квартала по заданному номеру квартала.
	 *
	 * @param int $quarterNum Номер квартала
	 * @return array Массив из двух элементов - начальной и конечной даты квартала
	 */
	public function getQuarterRange(int $quarterNum): array
    {

        $currentDateTime = new \DateTime();

        $firstDayOfQuarter = (new \DateTime())
            ->setDate(
                $currentDateTime->format('Y'),
                $quarterNum * 3 - 2,
                1
            )
            ->setTime(0, 0, 0);

        $lastDayOfQuarter = (new \DateTime())
            ->setDate(
                $currentDateTime->format('Y'),
                $quarterNum * 3,
                1
            )
            ->setTime(23, 59, 59)
            ->modify('last day of this month');

        return [
            date_format($firstDayOfQuarter, 'Y-m-d'),
            date_format($lastDayOfQuarter, 'Y-m-d')
        ];
    }


	/**
	 * Метод возвращает интервал дат заданного месяца.
	 *
	 * @param int $monthNum Номер месяца
	 * @return array Массив из двух элементов - начальной и конечной даты месяца
	 */
    public function getMonthRange(int $monthNum, ?int $year = null): array
    {
        $currentDateTime = new \DateTime();

        // Если год не передан, используем текущий
        $year = $year ?? (int)$currentDateTime->format('Y');

        $firstDayOfMonth = (new \DateTime())
            ->setDate($year, $monthNum, 1)
            ->setTime(0, 0, 0);

        $lastDayOfMonth = (new \DateTime())
            ->setDate($year, $monthNum, 1)
            ->setTime(23, 59, 59)
            ->modify('last day of this month');

        return [
            $firstDayOfMonth->format('Y-m-d'),
            $lastDayOfMonth->format('Y-m-d')
        ];
    }

    /**
     * Выводит массив дат в интервале между начальной и конечной датой (включительно)
     *
     * @param string $startDate Начальная дата в формате 'Y-m-d'
     * @param string $endDate Конечная дата в формате 'Y-m-d'
     * @throws \Exception
     */
    public function getDatesInRange(string $startDate = '', string $endDate = ''): array
    {
        if(strlen(trim($startDate)) > 0 && strlen(trim($endDate)) > 0) {
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $end = $end->modify('+1 day');

            $dates = [];
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach ($period as $date) {
                $dates[] = $date->format('Y-m-d');
            }

            return $dates;
        }else{
            return [$startDate ?? $endDate];
        }
    }

    public function getDatesFromMonths(?array $months, ?int $year = null): array{
	    $dates = [];
	    if(is_array($months) && count($months) > 0) {
            for ($i = 0; $i < count($months); $i++) {
                if ($i == 0) {
                    $dates['start'] = $this->getMonthRange($months[$i], $year)[0];
                }
                if ($i <= count($months)) {
                    $dates['end'] = $this->getMonthRange($months[$i], $year)[1];
                }
            }
        }
	    return $dates;
    }


	/**
	 * Метод возвращает интервал дат в виде строки по указанной дате.
	 *
	 * @param string $date Дата дня в составе недели
	 * @return string Строка вида "YYYY-mm-dd - YYYY-mm-dd"
	 */
	public function getWeekRange(string $date): string
    {
        $ts = strtotime($date);
        $start = (date('w', $ts) == 1) ? $ts : strtotime('last monday', $ts);
        return date('Y-m-d', $start).' - '.date('Y-m-d', strtotime('next sunday', $start));
    }


	/**
	 * Метод получения даты предыдущего дня от текущего дня.
	 *
	 * @return string Дата в формате "YYYY-mm-dd"
	 */
	public function getPrevDay(): string
    {
        return date('Y-m-d', strtotime(date('Y-m-d H:i:s') .' -1 day'));
    }


	/**
	 * Метод получения интервала дат по умолчанию для отображения операций в модуле "Данные".
	 *
	 * Интервал формируется из настроек отображения для текущего пользователя.
	 *
	 * @return string Строка вида "YYYY-mm-dd - YYYY-mm-dd"
	 */
	public function getDefaultRange($module_id = 3): string
    {
        $default_range = $this->_session['user_settings'][0][$module_id]['view_settings']['default_range'];
        $dates = '';

        switch($default_range){

            case 'yesterday':
                $dates = $this->getPrevDay().' - '.$this->getPrevDay();
                break;
            case 'curr_week':
                $dates = $this->getWeekRange(date('Y-m-d'));
                break;
            case 'prev_week':
                $dates = $this->getWeekRange(date("Y-m-d", strtotime(" -1 week")));
                break;
            case 'curr_month':
                $dates = implode(' - ', $this->getMonthRange(date('n')));
                break;
            case 'prev_month':
                $dates = implode(' - ',
	                $this->getMonthRange(date("n", strtotime(" -1 month"))));
                break;
            case 'curr_quarter':
                $dates = implode(' - ', $this->getQuarterRange($this->getCurrentQuarterNumber()));
                break;
            case 'prev_quarter':
                $dates = implode(' - ', $this->getQuarterRange($this->getCurrentQuarterNumber() - 1));
                break;
            case 'curr_year':
                $dates = date('Y').'-01-01 - '.date('Y').'-12-31';
                break;
            case 'prev_year':
                $prevYear = date("Y", strtotime(" -1 year"));
                $dates = $prevYear.'-01-01 - '.$prevYear.'-12-31';
                break;
            case 'today' :
            default:
                $dates = date('Y-m-d').' - '.date('Y-m-d');
                break;
        }

        return $dates;
    }

    public function dateToString(?string $date): string
    {
        if(strlen($date) > 0) {
            $dateArr = [];
            $divider = '-';
            $mont = '';
            if (substr_count($date, '.')) {
                $divider = '.';
            }
            //Если дата содержит время
            if (substr_count($date, ' ') > 0) {
                $dateArr = explode(' ', $date);
                $date = $dateArr[0];
            }
            $dArr = explode($divider, $date);
            $month = $dArr[1];
            //Определяем год в дате
            if (strlen($dArr[0]) == 4) {
                $year = $dArr[0];
                $day = $dArr[2];
            } else {
                $year = $dArr[2];
                $day = $dArr[0];
            }
            switch ($month) {
                case 1:
                    $mont = 'января';
                    break;
                case 2:
                    $mont = 'февраля';
                    break;
                case 3:
                    $mont = 'марта';
                    break;
                case 4:
                    $mont = 'апреля';
                    break;
                case 5:
                    $mont = 'мая';
                    break;
                case 6:
                    $mont = 'июня';
                    break;
                case 7:
                    $mont = 'июля';
                    break;
                case 8:
                    $mont = 'августа';
                    break;
                case 9:
                    $mont = 'сентября';
                    break;
                case 10:
                    $mont = 'октября';
                    break;
                case 11:
                    $mont = 'ноября';
                    break;
                case 12:
                    $mont = 'декабря';
                    break;
            }
            return $day . ' ' . $mont . ' ' . $year . 'г. ' . ((is_array($dateArr)) ? $dateArr[1] : '');
        }else{
            return '';
        }
    }

    public function periodToString(?string $period): string
    {
        if(strlen($period) > 0) {
            if (substr_count($period, ' - ') > 0) {
                $periodArr = explode(' - ', $period);
                return $this->dateToString($periodArr[0]) . ' - ' . $this->dateToString($periodArr[1]);
            } else {
                return $this->dateToString($period);
            }
        }else{
            return '';
        }
    }


	/**
	 * Метод принимает дату в формате "YYYY-mm-dd" и возвращает её в формате "dd.mm.YYYY".
	 *
	 * @param string $dateString Дата в формате "YYYY-mm-dd"
	 * @return string Дата в формате "dd.mm.YYYY"
	 */
	public function correctDateFormatFromMysql (?string $dateString ): string
    {
        if(strlen($dateString) > 0) {
            return vsprintf("%02d.%02d.%4d", array_reverse(explode('-', $dateString)));
        }else{
            return '';
        }
    }

    public function formatPostgresDate($dateString) {
        if (empty($dateString)) return '';

        $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        if ($date === false) return $dateString;

        return $date->format('d.m.Y H:i:s');
    }

	/**
	 * Метод принимает дату в формате "dd.mm.YYYY" и возвращает её в формате "YYYY-mm-dd".
	 *
	 * @param string $dateString Дата в формате "dd.mm.YYYY"
     * @param string $separator Разделитель в "неправильной" дате
	 * @return string Дата в формате "YYYY-mm-dd"
	 */
    public function correctDateFormatToMysql (string $dateString, string $separator = '.' ): string
    {
        if(strlen($dateString) > 0) {
            return vsprintf("%04d-%02d-%02d", array_reverse(explode($separator, $dateString)));
        }else{
            return '';
        }
    }

    /**
     * Метод принимает время в формате "H:i:s" и возвращает секунд.
     *
     * @param string $str_time Иремя в формате "H:i:s"
     * @return integer Количество секунд
     */
    public function timeToSeconds(string $str_time): int
    {
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $str_time = preg_replace("/^([\d]{1,2})\:([\d]{1,2})\:([\d]{2})$/", "$1:$2:$3", $str_time);
        sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function getMinMaxDates(string $dateRange): array
    {
        // Разделяем строку по разделителю " - "
        $dates = explode(' - ', $dateRange);

        // Проверяем, что получили ровно две даты
        if (count($dates) !== 2) {
            throw new InvalidArgumentException('Неверный формат даты. Ожидается формат "YYYY-MM-DD - YYYY-MM-DD"');
        }

        // Создаем объекты DateTime для каждой даты
        $date1 = new DateTime(trim($dates[0]));
        $date2 = new DateTime(trim($dates[1]));

        // Определяем минимальную и максимальную даты
        if ($date1 < $date2) {
            return [
                'min' => $dates[0],
                'max' => $dates[1]
            ];
        } else {
            return [
                'min' => $dates[1],
                'max' => $dates[0]
            ];
        }
    }

    /**
     * Преобразует строку с годом/годами или кварталом в диапазон дат
     *
     * @param string $periodString Строка с периодом (форматы: "2024", "2023-2024", "I кв. 2025")
     * @return string Строка с диапазоном дат в формате "01.01.2023 - 31.12.2024" или "01.01.2025 - 31.03.2025"
     */
    function convertYearToDateRange(string $periodString): string
    {
        $periodString = trim($periodString);

        // Проверяем формат квартала (например, "I кв. 2025")
        if (preg_match('/^([IV]+)\s*кв\.?\s*(\d{4})$/i', $periodString, $matches)) {
            $quarter = strtoupper($matches[1]);
            $year = (int)$matches[2];

            $quarterMonths = [
                'I'   => ['start' => '01.01', 'end' => '31.03'],
                'II'  => ['start' => '01.04', 'end' => '30.06'],
                'III' => ['start' => '01.07', 'end' => '30.09'],
                'IV'  => ['start' => '01.10', 'end' => '31.12']
            ];

            if (!isset($quarterMonths[$quarter])) {
                throw new InvalidArgumentException("Некорректный квартал: $quarter");
            }

            return $quarterMonths[$quarter]['start'] . ".$year - " . $quarterMonths[$quarter]['end'] . ".$year";
        }
        // Обработка обычных годов (прежняя логика)
        elseif (preg_match('/^\d{4}$/', $periodString)) {
            $year = (int)$periodString;
            return "01.01.$year - 31.12.$year";
        }
        elseif (preg_match('/^(\d{4})-(\d{4})$/', $periodString, $matches)) {
            $startYear = (int)$matches[1];
            $endYear = (int)$matches[2];

            if ($startYear > $endYear) {
                throw new InvalidArgumentException("Некорректный диапазон лет: $periodString");
            }

            return "01.01.$startYear - 31.12.$endYear";
        }

        throw new InvalidArgumentException("Неподдерживаемый формат периода: $periodString");
    }

    /**
     * Преобразует строку с кварталами в массив месяцев и текстовое описание
     *
     * @param string $quartersStr Строка с кварталами (форматы: "I", "I-II", "II,IV")
     * @return array [
     *     'months' => ['01', '02', ...], // номера месяцев
     *     'description' => 'I квартал'   // текстовое описание
     * ]
     */
    function convertQuartersToMonths(string $quartersStr): array
    {
        // Нормализация строки
        $quartersStr = strtoupper(str_replace([' ', 'кв.', 'квартал', 'кварталы'], '', $quartersStr));

        // Словарь кварталов
        $quartersMap = [
            'I'   => ['months' => ['01', '02', '03'], 'name' => 'I квартал'],
            'II'  => ['months' => ['04', '05', '06'], 'name' => 'II квартал'],
            'III' => ['months' => ['07', '08', '09'], 'name' => 'III квартал'],
            'IV'  => ['months' => ['10', '11', '12'], 'name' => 'IV квартал'],
        ];

        $months = [];
        $quarterNames = [];
        $quarters = preg_split('/[,-]/', $quartersStr);

        foreach ($quarters as $q) {
            if (!isset($quartersMap[$q])) {
                throw new InvalidArgumentException("Некорректный квартал: $q");
            }

            $months = array_merge($months, $quartersMap[$q]['months']);
            $quarterNames[$q] = $quartersMap[$q]['name'];
        }

        // Формируем описание
        $description = '';
        if (count($quarterNames) === 1) {
            $description = current($quarterNames);
        } else {
            $first = reset($quarterNames);
            $last = end($quarterNames);
            $description = preg_replace('/ квартал$/', '', $first)
                . ' - '
                . preg_replace('/ квартал$/', '', $last)
                . ' кварталы';
        }

        return [
            'months' => array_values(array_unique($months)),
            'description' => $description
        ];
    }

    /**
     * Преобразует название периода типа curr_week в массив из начальной и конечной даты периода
     * @param string $periodValue
     * @return array
     */
    public function convertDateRange(string $periodValue): array
    {
        $now = new DateTime();

        switch ($periodValue) {
            case 'today':
                return [
                    'start' => $now->format('Y-m-d 00:00:00'),
                    'end' => $now->format('Y-m-d 23:59:59')
                ];

            case 'yesterday':
                $yesterday = (clone $now)->modify('-1 day');
                return [
                    'start' => $yesterday->format('Y-m-d 00:00:00'),
                    'end' => $yesterday->format('Y-m-d 23:59:59')
                ];

            case 'curr_week':
                $startOfWeek = (clone $now)->modify('monday this week');
                $endOfWeek = (clone $now)->modify('sunday this week');
                return [
                    'start' => $startOfWeek->format('Y-m-d 00:00:00'),
                    'end' => $endOfWeek->format('Y-m-d 23:59:59')
                ];

            case 'prev_week':
                $startOfPrevWeek = (clone $now)->modify('monday last week');
                $endOfPrevWeek = (clone $now)->modify('sunday last week');
                return [
                    'start' => $startOfPrevWeek->format('Y-m-d 00:00:00'),
                    'end' => $endOfPrevWeek->format('Y-m-d 23:59:59')
                ];

            case 'curr_month':
                $startOfMonth = (clone $now)->modify('first day of this month');
                $endOfMonth = (clone $now)->modify('last day of this month');
                return [
                    'start' => $startOfMonth->format('Y-m-d 00:00:00'),
                    'end' => $endOfMonth->format('Y-m-d 23:59:59')
                ];

            case 'prev_month':
                $startOfPrevMonth = (clone $now)->modify('first day of last month');
                $endOfPrevMonth = (clone $now)->modify('last day of last month');
                return [
                    'start' => $startOfPrevMonth->format('Y-m-d 00:00:00'),
                    'end' => $endOfPrevMonth->format('Y-m-d 23:59:59')
                ];

            case 'curr_quarter':
                $month = (int)$now->format('n');
                $quarter = ceil($month / 3);
                $startMonth = ($quarter - 1) * 3 + 1;

                $startOfQuarter = (clone $now)
                    ->modify('first day of january')
                    ->modify('+' . ($startMonth - 1) . ' months');

                $endOfQuarter = (clone $startOfQuarter)
                    ->modify('+2 months')
                    ->modify('last day of this month');

                return [
                    'start' => $startOfQuarter->format('Y-m-d 00:00:00'),
                    'end' => $endOfQuarter->format('Y-m-d 23:59:59')
                ];

            case 'prev_quarter':
                $month = (int)$now->format('n');
                $quarter = ceil($month / 3) - 1;
                if ($quarter === 0) {
                    $quarter = 4;
                    $year = (int)$now->format('Y') - 1;
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $startOfQuarter = (new DateTime())
                        ->setDate($year, $startMonth, 1)
                        ->modify('first day of this month');
                } else {
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $startOfQuarter = (clone $now)
                        ->modify('first day of january')
                        ->modify('+' . ($startMonth - 1) . ' months');
                }

                $endOfQuarter = (clone $startOfQuarter)
                    ->modify('+2 months')
                    ->modify('last day of this month');

                return [
                    'start' => $startOfQuarter->format('Y-m-d 00:00:00'),
                    'end' => $endOfQuarter->format('Y-m-d 23:59:59')
                ];

            case 'curr_year':
                $startOfYear = (clone $now)->modify('first day of january');
                $endOfYear = (clone $now)->modify('last day of december');
                return [
                    'start' => $startOfYear->format('Y-m-d 00:00:00'),
                    'end' => $endOfYear->format('Y-m-d 23:59:59')
                ];

            case 'prev_year':
                $startOfPrevYear = (clone $now)
                    ->modify('-1 year')
                    ->modify('first day of january');
                $endOfPrevYear = (clone $now)
                    ->modify('-1 year')
                    ->modify('last day of december');
                return [
                    'start' => $startOfPrevYear->format('Y-m-d 00:00:00'),
                    'end' => $endOfPrevYear->format('Y-m-d 23:59:59')
                ];

            case 'next_year':
                $startOfNextYear = (clone $now)
                    ->modify('+1 year')
                    ->modify('first day of january');
                $endOfNextYear = (clone $now)
                    ->modify('+1 year')
                    ->modify('last day of december');
                return [
                    'start' => $startOfNextYear->format('Y-m-d 00:00:00'),
                    'end' => $endOfNextYear->format('Y-m-d 23:59:59')
                ];

            default:
                throw new InvalidArgumentException('Неизвестный период: ' . $periodValue);
        }
    }

    /**
     * Вычленяет проверяемый период и период проверки для каждого учреждения
     * из json-объекта в БД из таблицы cam_checksplans
     * @param object $jsonData
     * @param int $year
     * @return array|string[] - массив вида:
     * Array(
        [47] => Array
            (
                [actionPeriod] => 01.07.2025 - 31.12.2025 - Когда будет проходить проверка
                [action_start_date] => 01.07.2025
                [action_end_date] => 31.12.2025
                [check_period] => 01.01.2025 - 31.12.2025 - Проверяемый период
                [check_start_date] => 01.01.2025
                [check_end_date] => 31.12.2025
            )
        )
     *
     */
    public function getReviewPeriodsFromJson(string $jsonData, int $year): array
    {
        $data = json_decode($jsonData, true);

        if ($data === null) {
            return ['error' => 'Неверный формат JSON'];
        }

        $result = [];

        foreach ($data as $item) {
            // Проверяем наличие необходимых полей
            if (!isset($item['periods_hidden']) || !isset($item['institutions']) || !isset($item['check_periods'])) {
                continue;
            }

            //В какие месяцы будет проходить проверка
            $months = json_decode($item['periods_hidden'], true);

            if (empty($months)) {
                continue;
            }

            // Получаем ID учреждения
            $institutionId = $item['institutions'];

            // Если учреждение с таким ID уже есть в результате, пропускаем
            if (isset($result[$institutionId])) {
                continue;
            }

            // === 1. Форматируем период проверки (review period) из periods_hidden ===
            sort($months);
            $firstMonth = $months[0];
            $lastMonth = end($months);

            // Форматируем даты начала и окончания проверки
            $reviewStartDate = sprintf('01.%02d.%d', $firstMonth, $year);
            $lastDay = date('t', mktime(0, 0, 0, $lastMonth, 1, $year));
            $reviewEndDate = sprintf('%02d.%02d.%d', $lastDay, $lastMonth, $year);
            $reviewPeriod = $reviewStartDate . ' - ' . $reviewEndDate;

            // === 2. Парсим общий период проверки (check period) из check_periods ===
            $checkPeriodStr = $item['check_periods'];
            $checkPeriodFormatted = '';
            $checkStartDate = '';
            $checkEndDate = '';

            if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $checkPeriodStr, $matches)) {
                // Конвертируем из формата YYYY-MM-DD в DD.MM.YYYY
                $checkStartDate = date('d.m.Y', strtotime($matches[1]));
                $checkEndDate = date('d.m.Y', strtotime($matches[2]));
                $checkPeriodFormatted = $checkStartDate . ' - ' . $checkEndDate;
            } else {
                // Если не удалось распарсить, используем годовые границы
                $checkStartDate = sprintf('01.01.%d', $year);
                $checkEndDate = sprintf('31.12.%d', $year);
                $checkPeriodFormatted = $checkStartDate . ' - ' . $checkEndDate;
            }

            // Формируем массив с ID учреждения в качестве ключа
            $result[$institutionId] = [
                'actionPeriod' => $reviewPeriod, //Когда будет проходить проверка
                'action_start_date' => $reviewStartDate,
                'action_end_date' => $reviewEndDate,
                'checkPeriod' => $checkPeriodFormatted, //Проверяемый период
                'check_start_date' => $checkStartDate,
                'check_end_date' => $checkEndDate
            ];
        }

        return $result;
    }

    /**
     * Преобразует массив месяцев в строку вида 'YYYY-mm-dd - YYYY-mm-dd'
     * @param array $months
     * @param int|null $year
     * @return string
     */
    public function getMonthDateRange(array $months, ?int $year = null): string
    {
        // Если год не указан, используем текущий
        if ($year === null) {
            $year = (int)date('Y');
        }

        // Проверяем, что массив не пустой
        if (empty($months)) {
            throw new InvalidArgumentException('Массив месяцев не может быть пустым');
        }

        // Фильтруем массив: оставляем только корректные номера месяцев (1-12)
        $validMonths = array_filter($months, function ($month) {
            $month = intval($month);
            return is_numeric($month) && $month >= 1 && $month <= 12;
        }
        );

        // Если после фильтрации массив пуст
        if (empty($validMonths)) {
            throw new InvalidArgumentException('Массив должен содержать корректные номера месяцев (1-12)');
        }

        // Сортируем месяцы
        sort($validMonths);

        // Получаем первый и последний месяц
        $firstMonth = (int)min($validMonths);
        $lastMonth = (int)max($validMonths);

        // Формируем начальную дату (первое число первого месяца)
        $startDate = DateTime::createFromFormat('Y-m-d', sprintf('%d-%02d-01', $year, $firstMonth));

        // Формируем конечную дату (последнее число последнего месяца)
        $endDate = DateTime::createFromFormat('Y-m-d', sprintf('%d-%02d-01', $year, $lastMonth));
        $endDate->modify('last day of this month');

        // Форматируем результат
        return sprintf('%s - %s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    public function getMonthNameByNumber(int $month): string
    {
        switch ($month) {
            case 1:
                $mont = 'январь';
                break;
            case 2:
                $mont = 'февраль';
                break;
            case 3:
                $mont = 'март';
                break;
            case 4:
                $mont = 'апрель';
                break;
            case 5:
                $mont = 'май';
                break;
            case 6:
                $mont = 'июнь';
                break;
            case 7:
                $mont = 'июль';
                break;
            case 8:
                $mont = 'август';
                break;
            case 9:
                $mont = 'сентябрь';
                break;
            case 10:
                $mont = 'октябрь';
                break;
            case 11:
                $mont = 'ноябрь';
                break;
            case 12:
                $mont = 'декабрь';
                break;
            default:
                $mont = '';
        }
        return $mont;
    }

    public function getYearsFromPeriod(string $period): string
    {
        // Разбиваем строку на начальную и конечную даты
        $dates = explode(' - ', $period);

        if (count($dates) != 2) {
            return false; // Неверный формат периода
        }

        $startDate = trim($dates[0]);
        $endDate = trim($dates[1]);

        // Получаем года из дат
        $startYear = (int)date('Y', strtotime($startDate));
        $endYear = (int)date('Y', strtotime($endDate));

        // Если начальный и конечный год совпадают
        if ($startYear === $endYear) {
            return (string)$startYear;
        }

        // Если годы разные
        return $startYear . '-' . $endYear;
    }

}