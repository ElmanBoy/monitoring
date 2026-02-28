<?php

namespace Core;


use Core\Db;
use Core\Date;
use Core\Registry;
use R;
use Core\Templates;

/**
 * Класс Date предназначен для манипулирования датами.
 * @package Core
 * @version 0.1
 */
class Reports
{
    private /*array*/ $_get, $_post, $_session;
    private \Core\Registry $reg;
    private \Core\Db $db;
    private ?array $reportData;
    private ?object $reportProps;
    private \Core\Templates $temp;
    private \Core\Date $date;
    private R $rb;
    private string $startDate;
    private string $endDate;

    /**
	 * Констрктор класса Date.
	 */
	public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->db = new Db();
        $this->date = new Date();
        $this->reg = new Registry();
        $this->rb = new R;
        $this->temp = new Templates();
        $this->startDate = '';
        $this->endDate = '';
        $indicators = [
            0 => '',
            1 => 'Типы проведённых проверок',
            2 => 'Результаты проведённых проверок',
            3 => 'Статусы проверок',
            4 => 'Количество проверок по учреждениям',
            5 => 'Нарушения по категориям',
            6 => 'Количество проверок по инспекторам',
            7 => 'Сводная статистика по учреждениям'
        ];

        $reportProps = new \stdClass();
        $reportData = [];
    }

	/**
	 * Метод утсановки значения извне для приватных параметров класса.
	 * @param mixed $key Имя параметра
	 * @param mixed $val Значение параметра
	 * @return void Ничего не возвращает
	 */
    public function set($key, $val){
        $this->$key = $val;
    }

    private function getPropsFromId(int $reportId)
    {
        $this->reportProps = $this->db->selectOne('reports', ' WHERE id = ?', [$reportId]);
    }

    private function getDataCountCheckExecutors(string $startDate, string $endDate, string $sorting): array
    {
        $data = [];
        $subQuery = '';
        if(strlen($startDate) > 0 && strlen($endDate) > 0) {
            $subQuery = "
                    AND (
                        -- Для диапазона дат
                        (ch.dates LIKE '____-__-__ - ____-__-__'
                         AND SPLIT_PART(ch.dates, ' - ', 1)::date <= '$startDate'::date
                         AND SPLIT_PART(ch.dates, ' - ', 2)::date >= '$endDate'::date)
                        OR
                        -- Для одиночной даты
                        (ch.dates LIKE '____-__-__' AND ch.dates NOT LIKE '% - %'
                         AND ch.dates::date BETWEEN '$startDate'::date AND '$endDate'::date)
                    )";
        }

        $query = "SELECT 
                    us.surname,
                    us.name,
                    us.middle_name,
                    COUNT(ch.id) AS count
                FROM 
                    ".TBL_PREFIX."users us
                LEFT JOIN 
                    ".TBL_PREFIX."checkstaff ch ON us.id = ch.\"user\"
                WHERE 
                    ch.dates IS NOT NULL
                $subQuery
                GROUP BY us.surname, us.name, us.middle_name
                 ORDER BY count $sorting";

        $result = $this->rb::getAll($query);
        foreach($result as $item){
            $data['data'][] = ['name' => trim($item['surname']).' '.trim($item['name']).' '.
                trim($item['middle_name']), 'value' => $item['count']];
        }
        $data['columns'] = ['Ф.И.О. проверяющего', 'Количество проверок'];

        return $data;
    }

    private function getDataCountCheckComplete(string $startDate, string $endDate, string $sorting): array
    {
        $data = [];
        $subQuery = '';
        if (strlen($startDate) > 0 && strlen($endDate) > 0) {
            $subQuery = "
                    AND (
                        -- Пустое значение - нет ограничений по дате
                        add_data->>'check_periods' IS NULL 
                        OR add_data->>'check_periods' = ''
                        
                        OR
                        -- Проверка периода или даты
                        CASE 
                            -- Период дата - дата
                            WHEN add_data->>'check_periods' ~ '^\d{4}-\d{2}-\d{2} - \d{4}-\d{2}-\d{2}$' THEN
                                SPLIT_PART(add_data->>'check_periods', ' - ', 1)::date <= '$endDate'::date
                                AND SPLIT_PART(add_data->>'check_periods', ' - ', 2)::date >= '$startDate'::date
                            -- Одна дата
                            WHEN add_data->>'check_periods' ~ '^\d{4}-\d{2}-\d{2}$' 
                                 AND add_data->>'check_periods' NOT LIKE '% - %' THEN
                                (add_data->>'check_periods')::date = '$startDate'::date
                            -- Некорректный формат - игнорируем
                            ELSE FALSE
                        END
                    )";
        }

        $query = "SELECT 
    CASE 
        WHEN ch.done = 1 THEN 'Завершено'
        ELSE 'Не завершено'
    END AS status,
    COUNT(*) AS count
    FROM 
        ".TBL_PREFIX."checkstaff ch
    WHERE 
        EXISTS (
            SELECT 1
            FROM ".TBL_PREFIX."checksplans cp
            CROSS JOIN LATERAL jsonb_array_elements(
                CASE 
                    WHEN jsonb_typeof(cp.addinstitution) = 'array' THEN cp.addinstitution
                    ELSE '[]'::jsonb
                END
            ) AS add_data
            WHERE 
                cp.addinstitution IS NOT NULL
                AND (add_data->>'institutions')::integer = ch.institution
                $subQuery
        )
    GROUP BY 
        ch.done
    ORDER BY 
        status;";

        $result = $this->rb::getAll($query);
        foreach ($result as $item) {
            $data['data'][] = ['name' => trim($item['status']), 'value' => $item['count']];
        }
        $data['columns'] = ['Статус проверки', 'Количество проверок'];

        return $data;
    }

    private function getDataCountCheckInstitutions(string $startDate, string $endDate, string $sorting): array
    {
        $data = [];
        $subQuery = '';
        if(strlen($startDate) > 0 && strlen($endDate) > 0) {
            $subQuery = "
                AND (
                    (check_data->>'check_periods') LIKE '____-__-__ - ____-__-__'
                AND (
                    SPLIT_PART(check_data->>'check_periods', ' - ', 1)::date <= '$endDate'::date
                    AND SPLIT_PART(check_data->>'check_periods', ' - ', 2)::date >= '$startDate'::date
                )
            )";
        }

        $query = "
        SELECT 
            ci.id AS institution_id,
            ci.short AS institution_name,
            COUNT(*) AS checks_count
        FROM 
            ".TBL_PREFIX."checksplans cp,
            jsonb_array_elements(
                CASE 
                    WHEN jsonb_typeof(cp.addinstitution) = 'array' THEN cp.addinstitution
                    ELSE '[]'::jsonb
                END
            ) AS check_data,
            ".TBL_PREFIX."institutions ci
        WHERE 
            cp.active = 1
            AND (check_data->>'check_periods') IS NOT NULL
            $subQuery
            AND ci.id = (check_data->>'institutions')::integer
        GROUP BY 
            ci.id, ci.name
        ORDER BY 
            checks_count $sorting;";

        $result = $this->rb::getAll($query);
        foreach($result as $item){
            $data['data'][] = ['name' => trim($item['institution_name']), 'value' => $item['checks_count']];
        }
        $data['columns'] = ['Учреждение', 'Количество проверок'];

        return $data;
    }

    private function getDataCheckTypes(string $startDate, string $endDate, string $sorting): array
    {
        $data = [];
        $subQuery = '';
        if(strlen($startDate) > 0 && strlen($endDate) > 0) {
            $subQuery = "
                AND (
                    (check_data->>'check_periods') LIKE '____-__-__ - ____-__-__'
                AND (
                    SPLIT_PART(check_data->>'check_periods', ' - ', 1)::date <= '$endDate'::date
                    AND SPLIT_PART(check_data->>'check_periods', ' - ', 2)::date >= '$startDate'::date
                )
            )";
        }

        $query = "
        SELECT 
            cc.id AS check_type_id,
            cc.name AS check_type_name,
            COUNT(*) AS checks_count
        FROM 
            ".TBL_PREFIX."checksplans cp,
            jsonb_array_elements(
                CASE 
                    WHEN jsonb_typeof(cp.addinstitution) = 'array' THEN cp.addinstitution
                    ELSE '[]'::jsonb
                END
            ) AS check_data,
            ".TBL_PREFIX."checks cc
        WHERE 
            cp.active = 1
            AND (check_data->>'check_periods') IS NOT NULL
            $subQuery
            AND cc.id = (check_data->>'check_types')::integer
        GROUP BY 
            cc.id, cc.name
        ORDER BY 
            checks_count $sorting;";

        $result = $this->rb::getAll($query);
        foreach($result as $item){
            $data['data'][] = ['name' => trim($item['check_type_name']), 'value' => $item['checks_count']];
        }
        $data['columns'] = ['Тип проверки', 'Количество проверок'];

        return $data;
    }

    public function getDataById(int $reportId): array
    {
        $result = null;
        $data = [];
        $subQuery = "";
        $startDate = '';
        $endDate = '';

        $this->getPropsFromId($reportId);
        $sorting = $this->reportProps->sorting == 1 ? 'ASC' : 'DESC';

        if(strlen($this->reportProps->periods) > 0 && $this->reportProps->periods != 'all') {
            $dates = $this->date->convertDateRange($this->reportProps->periods);
            $this->startDate = $startDate = explode(" ", $dates['start'])[0];
            $this->endDate = $endDate = explode(' ', $dates['end'])[0];
        }

        switch($this->reportProps->indicator){
            case 1:
                $data = $this->getDataCheckTypes($startDate, $endDate, $sorting);
                break;
            case 3:
                $data = $this->getDataCountCheckComplete($startDate, $endDate, $sorting);
                break;
            case 4:
                $data = $this->getDataCountCheckInstitutions($startDate, $endDate, $sorting);
                break;
            case 6:
                $data = $this->getDataCountCheckExecutors($startDate, $endDate, $sorting);
                break;
        }

        $this->reportData = $data;
        return $data;
    }

    /**
     * @throws \Exception
     */
    public function buildCharByData(array $data): string
    {
        if(intval($this->reportProps->graphics) > 0) {
            $chart = $this->db->selectOne('graphs', " WHERE id = ?", [$this->reportProps->graphics]);
            $chartSource = $chart->source;
            $vars = [
                'name' => $this->reportProps->name,
                'description' => $this->reportProps->description,
                'data' => array_reverse($data)
            ];

            $chartOptions = $this->temp->twig_parse($chartSource, $vars);
            $chartId = $this->reportProps->id;

            return "<div id='chart".$chartId."' style='width: 90vw;height:400px;margin:0 auto'></div>".
                    "<script>var Chart".$chartId." = echarts.init(document.getElementById('chart".$chartId."'));".
                    "Chart".$chartId.".setOption(".$chartOptions.");
                    window.addEventListener('resize', function() {
                        Chart".$chartId.".resize();
                    });
                </script>";
        }
    }

}