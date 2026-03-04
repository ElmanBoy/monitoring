<?php

namespace Core;

use Core\Date;
use R;
use Core\Db;
use Core\Auth;
use Core\Notifications;

class Gui
{
    public /*int*/
    int $currentPageNumber = 0;
    public /*int*/
    int $rowsLimit = 20;
    public /*int*/
    int $totalRows = 0;
    public /*int*/
    int $module_id = 0;
    public /*array*/
    array $module_props = [];
    public /*string*/
    string $tableName = '';


    private /*array*/
        $_get, $_post, $_session, $_cookie, $_server, $filterFields = [], $sortFields = [], $tableResult = [];
    private /*R*/
        $rb;
    private /*Db*/
        $db;
    private /*Date*/
        $date;
    private /*Auth*/
        $auth;


    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->_server = $_SERVER;
        $this->_cookie = $_COOKIE;
        $this->rb = new R();
        $this->db = new Db();
        $this->date = new Date();
        $this->auth = new Auth();
        $this->notes = new Notifications();
    }

    public function set($key, $val)
    {
        $this->$key = $val;
    }

    public function get($key)
    {
        return $this->$key;
    }

    public function getModuleProps($module_path)
    {
        if (substr_count($module_path, '?') > 0) {
            $mArr = explode('?', $module_path);
            $module_path = $mArr[0];
        }
        $props_file = $_SERVER['DOCUMENT_ROOT'] . '/modules/' . $module_path . '/module.json';
        $module_props = json_decode(file_get_contents($props_file), true);
        $this->set('module_id', $module_props['id']);
        $this->set('module_props', $module_props);
        return $module_props;
    }

    public function buildLeftMenu1(): string
    {
        $dirName = $_SERVER['DOCUMENT_ROOT'] . '/modules';
        $htmlArr = [];
        $insertArr = [];
        $out = '';


        $dir = dir($dirName);
        while ($file = $dir->read()) {
            if ($file != '.' && $file != '..' && $file[0] != '.') {
                if (is_dir($dirName . '/' . $file)) {
                    $props_file = $dirName . '/' . $file . '/module.json';
                    if (is_file($props_file)) {
                        $module_props = json_decode(file_get_contents($props_file), true);

                        if ($this->_session['user_permissions'][$module_props['id']]['view']) {

                            $htmlArr[] = [
                                'number' => $module_props['number'],
                                'html' => '<div class="item" title="' . $module_props['name'] . '"' . ($_COOKIE['widthPage'] == 'thin' ? ' data-tipsy-disabled' : '') . '>
                                        <a href="' . ($module_props['path'] == 'wiki' ? '#' : '/' . $module_props['path']) . '"' . (($this->_get['url'] == $module_props['path']) ?
                                        ' class="active"' : '') . '>
                                            <div class="icon"><span class="material-icons">' . $module_props['icon'] . '</span></div>
                                            <div class="title">' . $module_props['name'] . '</div>
                                        </a>
                                    </div>'
                            ];
                        }

                        $insertArr = array(
                            'id' => intval($module_props['id']),
                            'name' => "'" . addslashes($module_props['name']) . "'",
                            'path' => "'/" . addslashes($module_props['path']) . "'",
                            'active' => 1
                        );
                        $this->rb->exec('INSERT INTO ' . TBL_PREFIX . 'modules (id, name, path, active) VALUES (' . implode(', ', $insertArr) . ') ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, path = EXCLUDED.path, active = EXCLUDED.active');
                    }
                }
            }
        }
        usort($htmlArr, function ($a, $b) {
            return ($a['number'] > $b['number']);
        }
        );
        foreach ($htmlArr as $item) {
            $out .= $item['html'];
        }
        return $out;
    }

    public function buildLeftMenu(): string
    {
        $dirName = $_SERVER['DOCUMENT_ROOT'] . '/modules';
        $htmlArr = [];
        $settingsItems = [];
        $insertArr = [];
        $out = '';


        $dir = dir($dirName);
        while ($file = $dir->read()) {
            if ($file != '.' && $file != '..' && $file[0] != '.') {
                if (is_dir($dirName . '/' . $file)) {
                    $props_file = $dirName . '/' . $file . '/module.json';
                    if (is_file($props_file)) {
                        $module_props = json_decode(file_get_contents($props_file), true);

                        if ($this->_session['user_permissions'][$module_props['id']]['view']) {
                            $menuItem = [
                                'number' => $module_props['number'],
                                'html' => '<div class="item" title="' . $module_props['name'] . '"' . ($_COOKIE['widthPage'] == 'thin' ? ' data-tipsy-disabled' : '') . '>
                                    <a href="' . ($module_props['path'] == 'wiki' ? '#' : '/' . $module_props['path']) . '"' . (($this->_get['url'] == $module_props['path']) ?
                                        ' class="active"' : '') . '>
                                        <div class="icon"><span class="material-icons">' . $module_props['icon'] . '</span></div>
                                        <div class="title">' . $module_props['name'] . '</div>
                                    </a>
                                </div>'
                            ];

                            // Проверяем, является ли элемент дочерним для настроек
                            if (isset($module_props['parentItem']) && $module_props['parentItem'] === 'settings') {
                                $settingsItems[] = $menuItem;
                            } else {
                                $htmlArr[] = $menuItem;
                            }
                        }

                        $insertArr = array(
                            'id' => intval($module_props['id']),
                            'name' => "'" . addslashes($module_props['name']) . "'",
                            'path' => "'/" . addslashes($module_props['path']) . "'",
                            'active' => 1
                        );
                        $this->rb->exec('INSERT INTO ' . TBL_PREFIX . 'modules (id, name, path, active) VALUES (' . implode(', ', $insertArr) . ') ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, path = EXCLUDED.path, active = EXCLUDED.active');
                    }
                }
            }
        }

        // Добавляем меню настроек, если есть дочерние элементы
        if (!empty($settingsItems)) {
            // Сортируем элементы настроек
            usort($settingsItems, function ($a, $b) {
                return ($a['number'] > $b['number']);
            }
            );

            // Собираем HTML для дочерних элементов
            $settingsContent = '';
            foreach ($settingsItems as $item) {
                $settingsContent .= $item['html'];
            }

            // Добавляем родительский пункт "Настройки" с дочерними элементами
            $htmlArr[] = [
                'number' => 9999, // Устанавливаем высокий номер для сортировки в конец
                'html' => '<div class="settings-group' . ($_COOKIE['settings_show'] == 'true' ? ' expanded" style="display:block"' : '') . '">
                        <div class="item parent-item" title="Настройки"' . ($_COOKIE['widthPage'] == 'thin' ? ' data-tipsy-disabled' : '') . '>
                            <a href="#" class="parent_item">
                                <div class="icon"><span class="material-icons">settings</span></div>
                                <div class="title">Настройки</div>
                                <div class="arrow"><span class="material-icons">chevron_right</span></div>
                            </a>
                        </div>
                        <div class="settings-submenu">' . $settingsContent . '</div>
                    </div>'
            ];
        }

        // Сортируем основное меню
        usort($htmlArr, function ($a, $b) {
            return ($a['number'] > $b['number']);
        }
        );

        // Собираем окончательный HTML
        foreach ($htmlArr as $item) {
            $out .= $item['html'];
        }

        return $out;
    }

    public function getTableData(string $tableName, string $defaultParams = ''): array
    {
        if ($this->auth->isLogin()) {
            if ($defaultParams == '') {
                $sortQuery = ' ORDER BY id DESC';
            }

            $filterQuery = '';
            $filterSlots = [];
            $defaultDates = '';
            if ($tableName == 'main') {
                $defaultDates = $this->date->getDefaultRange();
            }
            $this->set('tableName', TBL_PREFIX . $tableName);
            if (intval($this->_get['pn']) > 0) {
                $this->set('currentPageNumber', intval($this->_get['pn']));
            }
            if (isset($this->_post['params']) > 0) {
                parse_str($this->_post['params'], $params);
                $this->set('currentPageNumber', intval($params['pn']));
            }

            if (isset($this->_post['params'])) {
                parse_str($this->_post['params'], $out);
                $this->_get['sort'] = $out['sort'];
                $this->_get['filter'] = $out['filter'];
            }

            if (isset($this->_get['sort'])) {
                $sortArr = explode(':', $this->_get['sort']);
                $sortQueryArr = [];

                for ($i = 0; $i < count($sortArr); $i++) {
                    $direction = 'ASC';
                    if (substr_count($sortArr[$i], '_r') > 0) {
                        $direction = 'DESC';
                        $sortArr[$i] = str_replace('_r', '', $sortArr[$i]);
                        $this->sortFields[$sortArr[$i]]['arrow'] = 'north';
                        $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i];
                    } else {
                        $this->sortFields[$sortArr[$i]]['arrow'] = 'south';
                        $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i] . '_r';
                    }
                    if (substr_count($sortArr[$i], 'user_fio') > 0) {
                        $sortQueryArr[] = 'name ' . $direction . ', surname ' . $direction . ', middle_name ' . $direction;
                    } else {
                        $sortQueryArr[] = $sortArr[$i] . ' ' . $direction;
                    }
                }
                if (count($sortQueryArr) > 0) {
                    $sortQuery = ' ORDER BY ' . implode(', ', $sortQueryArr);
                }
            }

            if (isset($this->_get['filter'])) {

                $filterArr = explode(';', $this->_get['filter']);

                $filterSection = [];
                $this->filterFields = [];

                //Листаем фильтруемые поля
                for ($f = 0; $f < count($filterArr); $f++) {
                    //Листаем фильтруемые значения
                    $filterValuesArr = explode(':', $filterArr[$f]);
                    $filterValues = explode('|', $filterValuesArr[1]);
                    $filterQueryArr = [];
                    for ($v = 0; $v < count($filterValues); $v++) {
                        if ($filterValuesArr[0] == 'user_fio') {
                            $filterQueryArr[] = 'name LIKE ?';
                            $filterQueryArr[] = 'surname LIKE ?';
                            $filterQueryArr[] = 'middle_name LIKE ?';
                        } else {
                            if (substr_count($filterValuesArr[0], '_from') > 0) {
                                $filterQueryArr[] = str_replace('_from', '', $filterValuesArr[0]) . ' >= ?';
                            } elseif (substr_count($filterValuesArr[0], '_to') > 0) {
                                $filterQueryArr[] = str_replace('_to', '', $filterValuesArr[0]) . ' <= ?';
                            } else {
                                $filterQueryArr[] = $filterValuesArr[0] . ((is_numeric($filterValues[$v])) ? ' = ?' : ' LIKE ?');
                            }
                        }
                        if ($filterValuesArr[0] == 'user_fio') {
                            $user_fio = explode(' ', $filterValues[$v]);
                            $filterSlots[] = '%' . $user_fio[0] . '%';
                            $filterSlots[] = '%' . $user_fio[1] . '%';
                            $filterSlots[] = '%' . $user_fio[2] . '%';
                        } else {
                            $filterSlots[] = (is_numeric($filterValues[$v])) ? $filterValues[$v] : '%' . $filterValues[$v] . '%';
                        }
                        $this->filterFields[$filterValuesArr[0]][] = $filterValues[$v];
                    }
                    $filterSection[] = '(' . implode(' OR ', $filterQueryArr) . ')';
                }
                $filterQuery .= implode(' AND ', $filterSection);
            } else {
                if ($defaultDates != '') {
                    $datesArr = explode(' - ', $defaultDates);
                    $filterQuery = " date >= '" . $datesArr[0] . "' AND date <= '" . $datesArr[1] . "'";
                }
            }

            if($defaultParams == 'AND active = -1'){
                $filterQueryMain = ' WHERE id > 0 AND active = -1 ' . $defaultParams;
            }else {
                $filterQueryMain = ' WHERE id > 0 AND (active IS NULL OR active != -1) ' . $defaultParams;
            }
            $filterQueryTotal = '';
            if ($filterQuery != ''/* && $defaultParams != ''*/) {
                $filterQueryMain = ' WHERE id > 0 ' . $defaultParams . ' AND ' . $filterQuery;
                $filterQueryTotal = ' WHERE id > 0 AND ' . $filterQuery;
            } else {
                $filterSlots = [];
            }
            /*$filterQueryMain = $defaultParams . (($filterQuery != '' && $defaultParams != '') ? ' AND ' . $filterQuery : '');
            $filterQueryTotal = ' WHERE id > 0 '.(($filterQuery != '' && $defaultParams != '') ? ' AND ' . $filterQuery : '');*/
            //echo $filterQuery.' / '.$defaultParams.' / '.$filterQueryTotal; print_r($filterSlots);
            $this->set('totalRows', $this->rb::count(TBL_PREFIX . $tableName, $filterQueryMain, $filterSlots));

            $sortQuery .= ' LIMIT ? OFFSET ? ';
            $filterSlots[] = $this->rowsLimit;
            $filterSlots[] = $this->currentPageNumber * $this->rowsLimit;

            /*if($defaultParams == " parent = '14'" && ($this->auth->haveUserRole(1) || $this->auth->haveUserRole(3))){
                $filterQuery = str_replace(" parent = '14'", '', $filterQuery);
    /*echo "SELECT COUNT(".TBL_PREFIX . "ext_answers.answer) AS ext_answers, ".TBL_PREFIX . "registryitems.*
                    FROM ".TBL_PREFIX . "registryitems
                    LEFT JOIN ".TBL_PREFIX . "ext_answers ON ".TBL_PREFIX . "registryitems.id = ".TBL_PREFIX . "ext_answers.question_id
                    WHERE ".TBL_PREFIX . "registryitems.parent=14 $filterQuery GROUP BY ".TBL_PREFIX . "registryitems.id $sortQuery"; print_r($filterSlots);*/
            /*return $this->rb->getAll("SELECT COUNT(".TBL_PREFIX . "ext_answers.answer) AS ext_answers, ".TBL_PREFIX . "registryitems.*
                FROM ".TBL_PREFIX . "registryitems
                LEFT JOIN ".TBL_PREFIX . "ext_answers ON ".TBL_PREFIX . "registryitems.id = ".TBL_PREFIX . "ext_answers.question_id
                WHERE ".TBL_PREFIX . "registryitems.parent=14 $filterQuery GROUP BY ".TBL_PREFIX . "registryitems.id $sortQuery", $filterSlots);
        }else{

        }*/
            //echo $filterQueryMain . $sortQuery; print_r($filterSlots);
            return $this->db->select($tableName, $filterQueryMain . $sortQuery, $filterSlots);
        } else {
            echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
        }
    }

    public function getMainTableData(string $defaultParams = ''): array
    {
        $sortQuery = ' ORDER BY m.id DESC';
        $filterQuery = '';
        $filterSlots = [];
        $defaultDates = $this->date->getDefaultRange($this->module_id);

        $this->set('tableName', TBL_PREFIX . 'appeals');

        $fields_appeals = $this->rb::inspect(TBL_PREFIX . 'appeals');
        $fields_main = $this->rb::inspect(TBL_PREFIX . 'main');

        if (intval($this->_get['pn']) > 0) {
            $this->set('currentPageNumber', intval($this->_get['pn']));
        }
        if (isset($this->_post['params']) > 0) {
            parse_str($this->_post['params'], $params);
            $this->set('currentPageNumber', intval($params['pn']));
        }

        if (isset($this->_post['params'])) {
            parse_str($this->_post['params'], $out);
            $this->_get['sort'] = $out['sort'];
            $this->_get['filter'] = $out['filter'];
        }

        if (isset($this->_get['sort'])) {
            $sortArr = explode(':', $this->_get['sort']);
            $sortQueryArr = [];

            for ($i = 0; $i < count($sortArr); $i++) {
                $direction = 'ASC';
                if (substr_count($sortArr[$i], '_r') > 0) {
                    $direction = 'DESC';
                    $sortArr[$i] = str_replace('_r', '', $sortArr[$i]);
                    $this->sortFields[$sortArr[$i]]['arrow'] = 'north';
                    $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i];
                } else {
                    $this->sortFields[$sortArr[$i]]['arrow'] = 'south';
                    $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i] . '_r';
                }
                $sortQueryArr[] = $sortArr[$i] . ' ' . $direction;
            }
            if (count($sortQueryArr) > 0) {
                $sortQuery = ' ORDER BY ' . implode(', ', $sortQueryArr);
            }
        }

        if (isset($this->_get['filter'])) {

            $filterArr = explode(';', $this->_get['filter']);

            $filterSection = [];
            $this->filterFields = [];
            $fieldType = '';

            //Листаем фильтруемые поля
            for ($f = 0; $f < count($filterArr); $f++) {
                //Листаем фильтруемые значения
                $filterValuesArr = explode(':', $filterArr[$f]);
                $filterValues = explode('|', $filterValuesArr[1]);
                $filterQueryArr = [];
                for ($v = 0; $v < count($filterValues); $v++) {

                    if ($filterValuesArr[0] === 'm.age') {
                        switch (intval($filterValuesArr[1])) {
                            case 7318:
                                $filterQueryArr[] = 'm.age < ?';
                                $filterSlots[] = 18;
                                break;
                            case 7319:
                                $filterQueryArr[] = 'm.age >= ? AND m.age <= ?';
                                $filterSlots[] = 18;
                                $filterSlots[] = 35;
                                break;
                            case 7320:
                                $filterQueryArr[] = 'm.age >= ? AND m.age <= ?';
                                $filterSlots[] = 36;
                                $filterSlots[] = 55;
                                break;
                            case 7321:
                                $filterQueryArr[] = 'm.age >= ?';
                                $filterSlots[] = 56;
                                break;
                        }
                    } else if ($filterValuesArr[0] === 'a.claim_category') {
                        $filterQueryArr[] = "a.is_claim = '1' AND a.category = ?";
                        $filterSlots[] = $filterValues[$v];
                    } else {

                        if (substr_count($filterValuesArr[0], '_from') > 0) {
                            $filterQueryArr[] = str_replace('_from', '', $filterValuesArr[0]) . ' >= ?';
                        } elseif (substr_count($filterValuesArr[0], '_to') > 0) {
                            $filterQueryArr[] = str_replace('_to', '', $filterValuesArr[0]) . ' <= ?';
                        } else {

                            if (substr_count($filterValuesArr[0], 'm.') > 0) {
                                $fieldType = $fields_main[str_replace('m.', '', $filterValuesArr[0])];
                            }
                            if (substr_count($filterValuesArr[0], 'a.') > 0) {
                                $fieldType = $fields_appeals[str_replace('a.', '', $filterValuesArr[0])];
                            }
                            if ($filterValuesArr[0] === 'a.urgent') {
                                $fieldType = 'smallint';
                                $filterValuesArr[0] = 'urgent';
                            }

                            switch ($fieldType) {
                                case 'integer':
                                case 'bigint':
                                    $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                    $filterValues[$v] = intval($filterValues[$v]);
                                    break;
                                case 'time without time zone':
                                case 'date':
                                    break;
                                    $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                    break;
                                case 'smallint':
                                    $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                    $filterValues[$v] = intval($filterValues[$v]);
                                    break;
                                default:
                                    $filterQueryArr[] = 'LOWER(' . $filterValuesArr[0] . ') LIKE ?';
                                    $filterValues[$v] = '%' . mb_strtolower($filterValues[$v]) . '%';
                            }

                            //$filterQueryArr[] = $filterValuesArr[0] . " = ?";
                        }
                        $filterSlots[] = $filterValues[$v];
                        $this->filterFields[$filterValuesArr[0]][] = $filterValues[$v];
                    }
                }
                $filterSection[] = '(' . implode(' OR ', $filterQueryArr) . ')';
            }
            $filterQuery .= implode(' AND ', $filterSection);
        } else {
            if ($defaultDates != '') {
                $datesArr = explode(' - ', $defaultDates);
                $filterQuery = " m.date >= '" . $datesArr[0] . "' AND m.date <= '" . $datesArr[1] . "'";
            }
        }

        $filterQuery = $defaultParams . (($filterQuery != '' && $defaultParams != '') ? ' AND ' . $filterQuery : $filterQuery);

        if ($_SESSION['user_id'] == 6 && isset($_GET['debug'])) {
            //print_r($fields_main); print_r($fields_appeals);
            echo 'SELECT *, a.question AS quest, a.answer AS answ, a.resolved AS resolv,
       a.category AS cat, a.subcategory AS subcat, a.redirected AS redir, a.appeal_status AS status
        FROM ' . TBL_PREFIX . 'appeals a, ' . TBL_PREFIX . 'main m
        WHERE a.appeal_id = m.id AND ' . $filterQuery . $sortQuery; //print_r($filterSlots);
        }


        $total = $this->rb->getAll('SELECT 
        COUNT(a.id) AS total_appeals
        FROM ' . TBL_PREFIX . 'appeals a, ' . TBL_PREFIX . 'main m
        WHERE a.appeal_id = m.id AND ' . $filterQuery, $filterSlots
        ); //print_r($total);
        $this->set('totalRows', $total[0]['total_appeals']);

        $sortQuery .= ' LIMIT ? OFFSET ? ';
        $filterSlots[] = $this->rowsLimit;
        $filterSlots[] = $this->currentPageNumber * $this->rowsLimit;


        return $this->rb->getAll('SELECT *, a.question AS quest, a.answer AS answ, a.resolved AS resolv, 
       a.category AS cat, a.subcategory AS subcat, a.redirected AS redir, a.appeal_status AS status, a.is_claim AS claim,
       a.urgency AS urgent
        FROM ' . TBL_PREFIX . 'appeals a, ' . TBL_PREFIX . 'main m
        WHERE a.appeal_id = m.id AND ' . $filterQuery . $sortQuery, $filterSlots
        );
    }


    public function getVdnTableData(string $defaultParams = ''): array
    {
        $sortQuery = ' ORDER BY date DESC, time DESC';
        $filterQuery = '';
        $filterSlots = [];
        $defaultDates = $this->date->getDefaultRange($this->module_id);

        $this->set('tableName', TBL_PREFIX . 'vdn');

        $fields_main = $this->rb::inspect(TBL_PREFIX . 'vdn');

        if (intval($this->_get['pn']) > 0) {
            $this->set('currentPageNumber', intval($this->_get['pn']));
        }
        if (isset($this->_post['params']) > 0) {
            parse_str($this->_post['params'], $params);
            $this->set('currentPageNumber', intval($params['pn']));
        }

        if (isset($this->_post['params'])) {
            parse_str($this->_post['params'], $out);
            $this->_get['sort'] = $out['sort'];
            $this->_get['filter'] = $out['filter'];
        }

        if (isset($this->_get['sort'])) {
            $sortArr = explode(':', $this->_get['sort']);
            $sortQueryArr = [];

            for ($i = 0; $i < count($sortArr); $i++) {
                $direction = 'ASC';
                if (substr_count($sortArr[$i], '_r') > 0) {
                    $direction = 'DESC';
                    $sortArr[$i] = str_replace('_r', '', $sortArr[$i]);
                    $this->sortFields[$sortArr[$i]]['arrow'] = 'north';
                    $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i];
                } else {
                    $this->sortFields[$sortArr[$i]]['arrow'] = 'south';
                    $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i] . '_r';
                }
                $sortQueryArr[] = $sortArr[$i] . ' ' . $direction;
            }
            if (count($sortQueryArr) > 0) {
                $sortQuery = ' ORDER BY ' . implode(', ', $sortQueryArr);
            }
        }

        if (isset($this->_get['filter'])) {

            $filterArr = explode(';', $this->_get['filter']);

            $filterSection = [];
            $this->filterFields = [];
            $fieldType = '';

            //Листаем фильтруемые поля
            for ($f = 0; $f < count($filterArr); $f++) {
                //Листаем фильтруемые значения
                $filterValuesArr = explode(':', $filterArr[$f]);
                $filterValues = explode('|', $filterValuesArr[1]);
                $filterQueryArr = [];
                for ($v = 0; $v < count($filterValues); $v++) {

                    /*if($filterValuesArr[0] === "m.age"){
                        switch(intval($filterValuesArr[1])){
                            case 7318:
                                $filterQueryArr[] = "m.age < ?";
                                $filterSlots[] = 18;
                                break;
                            case 7319:
                                $filterQueryArr[] = "m.age >= ? AND m.age <= ?";
                                $filterSlots[] = 18;
                                $filterSlots[] = 35;
                                break;
                            case 7320:
                                $filterQueryArr[] = "m.age >= ? AND m.age <= ?";
                                $filterSlots[] = 36;
                                $filterSlots[] = 55;
                                break;
                            case 7321:
                                $filterQueryArr[] = "m.age >= ?";
                                $filterSlots[] = 56;
                                break;
                        }
                    }else if($filterValuesArr[0] === "a.claim_category") {
                        $filterQueryArr[] = "a.is_claim = '1' AND a.category = ?";
                        $filterSlots[] = $filterValues[$v];
                    }else {*/

                    if (substr_count($filterValuesArr[0], '_from') > 0) {
                        $filterQueryArr[] = str_replace('_from', '', $filterValuesArr[0]) . ' >= ?';
                    } elseif (substr_count($filterValuesArr[0], '_to') > 0) {
                        $filterQueryArr[] = str_replace('_to', '', $filterValuesArr[0]) . ' <= ?';
                    } else {

                        /*if(substr_count($filterValuesArr[0], 'm.') > 0){
                            $fieldType = $fields_main[str_replace('m.', '', $filterValuesArr[0])];
                        }
                        if(substr_count($filterValuesArr[0], 'a.') > 0){
                            $fieldType = $fields_appeals[str_replace('a.', '', $filterValuesArr[0])];
                        }
                        if($filterValuesArr[0] === "a.urgent") {
                            $fieldType = 'smallint';
                            $filterValuesArr[0] = 'urgent';
                        }*/

                        switch ($fieldType) {
                            case 'integer':
                            case 'bigint':
                                $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                $filterValues[$v] = intval($filterValues[$v]);
                                break;
                            case 'time without time zone':
                            case 'date':
                                break;
                                $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                break;
                            case 'smallint':
                                $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                $filterValues[$v] = intval($filterValues[$v]);
                                break;
                            default:
                                $filterQueryArr[] = 'LOWER(' . $filterValuesArr[0] . ') LIKE ?';
                                $filterValues[$v] = '%' . mb_strtolower($filterValues[$v]) . '%';
                        }

                        //$filterQueryArr[] = $filterValuesArr[0] . " = ?";
                    }
                    $filterSlots[] = $filterValues[$v];
                    $this->filterFields[$filterValuesArr[0]][] = $filterValues[$v];
                    //}
                }
                $filterSection[] = '(' . implode(' OR ', $filterQueryArr) . ')';
            }
            $filterQuery .= implode(' AND ', $filterSection);
        } else {
            if ($defaultDates != '') {
                $datesArr = explode(' - ', $defaultDates);
                $filterQuery = " date >= '" . $datesArr[0] . "' AND date <= '" . $datesArr[1] . "'";
            }
        }

        $filterQuery = $defaultParams . (($filterQuery != '' && $defaultParams != '') ? ' AND ' . $filterQuery : $filterQuery);

        if ($_SESSION['user_id'] == 6 && isset($_GET['debug'])) {
            print_r($fields_main);
            echo 'SELECT 
            COUNT(id) AS total_vdn
            FROM ' . TBL_PREFIX . 'vdn
            WHERE id > 0 AND ' . $filterQuery . $sortQuery; //print_r($filterSlots);
        }


        $total = $this->rb->getAll('SELECT 
        COUNT(id) total_vdn
        FROM ' . TBL_PREFIX . 'vdn
        WHERE id > 0 AND ' . $filterQuery, $filterSlots
        ); //print_r($total);
        $this->set('totalRows', $total[0]['total_vdn']);

        $sortQuery .= ' LIMIT ? OFFSET ? ';
        $filterSlots[] = $this->rowsLimit;
        $filterSlots[] = $this->currentPageNumber * $this->rowsLimit;


        return $this->rb->getAll('SELECT * 
        FROM ' . TBL_PREFIX . 'vdn
        WHERE id > 0 AND ' . $filterQuery . $sortQuery, $filterSlots
        );
    }

    public function getOperatorsTableData(string $defaultParams = ''): array
    {
        $sortQuery = ' ORDER BY date DESC, time DESC';
        $filterQuery = '';
        $filterSlots = [];
        $defaultDates = $this->date->getDefaultRange($this->module_id);

        $this->set('tableName', TBL_PREFIX . 'vdn_operators');

        $fields_main = $this->rb::inspect(TBL_PREFIX . 'vdn');

        if (intval($this->_get['pn']) > 0) {
            $this->set('currentPageNumber', intval($this->_get['pn']));
        }
        if (isset($this->_post['params']) > 0) {
            parse_str($this->_post['params'], $params);
            $this->set('currentPageNumber', intval($params['pn']));
        }

        if (isset($this->_post['params'])) {
            parse_str($this->_post['params'], $out);
            $this->_get['sort'] = $out['sort'];
            $this->_get['filter'] = $out['filter'];
        }

        if (isset($this->_get['sort'])) {
            $sortArr = explode(':', $this->_get['sort']);
            $sortQueryArr = [];

            for ($i = 0; $i < count($sortArr); $i++) {
                $direction = 'ASC';
                if (substr_count($sortArr[$i], '_r') > 0) {
                    $direction = 'DESC';
                    $sortArr[$i] = str_replace('_r', '', $sortArr[$i]);
                    $this->sortFields[$sortArr[$i]]['arrow'] = 'north';
                    $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i];
                } else {
                    $this->sortFields[$sortArr[$i]]['arrow'] = 'south';
                    $this->sortFields[$sortArr[$i]]['value'] = $sortArr[$i] . '_r';
                }
                $sortQueryArr[] = $sortArr[$i] . ' ' . $direction;
            }
            if (count($sortQueryArr) > 0) {
                $sortQuery = ' ORDER BY ' . implode(', ', $sortQueryArr);
            }
        }

        if (isset($this->_get['filter'])) {

            $filterArr = explode(';', $this->_get['filter']);

            $filterSection = [];
            $this->filterFields = [];
            $fieldType = '';

            //Листаем фильтруемые поля
            for ($f = 0; $f < count($filterArr); $f++) {
                //Листаем фильтруемые значения
                $filterValuesArr = explode(':', $filterArr[$f]);
                $filterValues = explode('|', $filterValuesArr[1]);
                $filterQueryArr = [];
                for ($v = 0; $v < count($filterValues); $v++) {

                    /*if($filterValuesArr[0] === "m.age"){
                        switch(intval($filterValuesArr[1])){
                            case 7318:
                                $filterQueryArr[] = "m.age < ?";
                                $filterSlots[] = 18;
                                break;
                            case 7319:
                                $filterQueryArr[] = "m.age >= ? AND m.age <= ?";
                                $filterSlots[] = 18;
                                $filterSlots[] = 35;
                                break;
                            case 7320:
                                $filterQueryArr[] = "m.age >= ? AND m.age <= ?";
                                $filterSlots[] = 36;
                                $filterSlots[] = 55;
                                break;
                            case 7321:
                                $filterQueryArr[] = "m.age >= ?";
                                $filterSlots[] = 56;
                                break;
                        }
                    }else if($filterValuesArr[0] === "a.claim_category") {
                        $filterQueryArr[] = "a.is_claim = '1' AND a.category = ?";
                        $filterSlots[] = $filterValues[$v];
                    }else {*/

                    if (substr_count($filterValuesArr[0], '_from') > 0) {
                        $filterQueryArr[] = str_replace('_from', '', $filterValuesArr[0]) . ' >= ?';
                    } elseif (substr_count($filterValuesArr[0], '_to') > 0) {
                        $filterQueryArr[] = str_replace('_to', '', $filterValuesArr[0]) . ' <= ?';
                    } else {

                        /*if(substr_count($filterValuesArr[0], 'm.') > 0){
                            $fieldType = $fields_main[str_replace('m.', '', $filterValuesArr[0])];
                        }
                        if(substr_count($filterValuesArr[0], 'a.') > 0){
                            $fieldType = $fields_appeals[str_replace('a.', '', $filterValuesArr[0])];
                        }
                        if($filterValuesArr[0] === "a.urgent") {
                            $fieldType = 'smallint';
                            $filterValuesArr[0] = 'urgent';
                        }*/

                        switch ($fieldType) {
                            case 'integer':
                            case 'bigint':
                                $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                $filterValues[$v] = intval($filterValues[$v]);
                                break;
                            case 'time without time zone':
                            case 'date':
                                break;
                                $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                break;
                            case 'smallint':
                                $filterQueryArr[] = $filterValuesArr[0] . ' = ?';
                                $filterValues[$v] = intval($filterValues[$v]);
                                break;
                            default:
                                $filterQueryArr[] = 'LOWER(' . $filterValuesArr[0] . ') LIKE ?';
                                $filterValues[$v] = '%' . mb_strtolower($filterValues[$v]) . '%';
                        }

                        //$filterQueryArr[] = $filterValuesArr[0] . " = ?";
                    }
                    $filterSlots[] = $filterValues[$v];
                    $this->filterFields[$filterValuesArr[0]][] = $filterValues[$v];
                    //}
                }
                $filterSection[] = '(' . implode(' OR ', $filterQueryArr) . ')';
            }
            $filterQuery .= implode(' AND ', $filterSection);
        } else {
            if ($defaultDates != '') {
                $datesArr = explode(' - ', $defaultDates);
                $filterQuery = " date >= '" . $datesArr[0] . "' AND date <= '" . $datesArr[1] . "'";
            }
        }

        $filterQuery = $defaultParams . (($filterQuery != '' && $defaultParams != '') ? ' AND ' . $filterQuery : $filterQuery);

        if ($_SESSION['user_id'] == 6 && isset($_GET['debug'])) {
            print_r($fields_main);
            echo 'SELECT 
            COUNT(id) AS total_vdn
            FROM ' . TBL_PREFIX . 'vdn_operators
            WHERE id > 0 AND ' . $filterQuery . $sortQuery; //print_r($filterSlots);
        }


        $total = $this->rb->getAll('SELECT 
        COUNT(id) total_vdn
        FROM ' . TBL_PREFIX . 'vdn_operators
        WHERE id > 0 AND ' . $filterQuery, $filterSlots
        ); //print_r($total);
        $this->set('totalRows', $total[0]['total_vdn']);

        $sortQuery .= ' LIMIT ? OFFSET ? ';
        $filterSlots[] = $this->rowsLimit;
        $filterSlots[] = $this->currentPageNumber * $this->rowsLimit;


        return $this->rb->getAll('SELECT * 
        FROM ' . TBL_PREFIX . 'vdn_operators
        WHERE id > 0 AND ' . $filterQuery . $sortQuery, $filterSlots
        );
    }

    public function getQueryString(): string
    {
        $queryString = '';
        if ($this->_post['ajax'] == 1) {
            $ajaxParams = [];
            $ajaxQuery = explode('&', $_POST['params']);

            foreach ($ajaxQuery as $name => $value) {
                $valu = explode('=', $value);
                $key = $valu[0];
                $val = $valu[1];
                if (is_array($val)) {
                    for ($i = 0; $i < count($val); $i++) {
                        if (stristr($key, 'pn') == false &&
                            stristr($key, 'url') == false &&
                            stristr($key, 'path') == false)
                            $ajaxParams[] = $key . '[]=' . $val[$i];
                    }
                } else {
                    if (is_string($val) &&
                        strlen($val) > 0 &&
                        stristr($key, 'pn') == false &&
                        stristr($key, 'url') == false &&
                        stristr($key, 'path') == false)
                        $ajaxParams[] = $key . '=' . $val;
                }
            }

            $this->_server['QUERY_STRING'] = implode('&', $ajaxParams);
            $queryString = (implode('&', $ajaxParams));
        }
        if (!empty($this->_server['QUERY_STRING'])) {
            $params = explode('&', $this->_server['QUERY_STRING']);
            $newParams = [];
            foreach ($params as $param) {
                if (stristr($param, 'pn') == false &&
                    stristr($param, 'tr') == false &&
                    stristr($param, 'url') == false &&
                    stristr($param, 'path') == false
                ) {
                    array_push($newParams, $param);
                }
            }
            $newParams = array_unique($newParams);
            if (count($newParams) > 0) {
                $queryString = htmlentities(implode('&', $newParams));
            }
        }
        return $queryString;
    }

    public function paging(): string
    {
        $rows = $this->totalRows;
        $totalPages = ceil($rows / $this->rowsLimit);
        $pn = $this->currentPageNumber;
        $out = '';
        $path = (isset($this->_get['url'])) ? '/' . $this->_get['url'] : '/' . $this->_post['url'];
        $path = str_replace('//', '/', $path);
        $queryString = $this->getQueryString();

        if ($totalPages == 0 || $this->rowsLimit == 0) {
            return '';
        }

        if ($totalPages == 1) {
            return '';
        }

        $out .= '<div class="pagination">';

        if ($pn > 0) {
            if (max(0, $pn - 1) == 0) {

                $out .= '<div class="paginate">
                    <a href="' . $path . '?' . $queryString . '" title="Назад" tabindex="0">
                        <span class="material-icons">navigate_before</span></a>
                </div>';

            } else {

                $out .= '<div class="paginate">
                    <a href="' . $path . '?pn=' . max(0, $pn - 1) . '&' . $queryString . '" title="Назад" tabindex="0">
                        <span class="material-icons">navigate_before</span></a>
                </div>';
            }
        }

        if (($pn < $totalPages) || ($pn > 0)) {

            $startcount = $pn - 3;
            $startcount = ($startcount < 0) ? 0 : $startcount;
            $maxcount = $pn + 3;
            $maxcount = ($maxcount > $totalPages) ? $totalPages : $maxcount;
            $maxcount = ($startcount == 0 && $totalPages >= 6) ? 5 : $maxcount;
            $page = $startcount + 1;

            $countpage = ceil($rows / $this->rowsLimit) - 1;
            if ($startcount > 0) {
                $out .= '<div class="page dotted"><a href="' . $path . ((($startcount - 1) > 0) ? '?pn=' .
                        ($startcount - 1) . '&' : '?') . $queryString . '" tabindex="0">....</a></div>';
            }
            for ($pagen = $startcount; $pagen < $maxcount; $pagen++) {
                if ($countpage >= 0) {
                    if ($pn != $pagen) {
                        if ($pagen == 0) {
                            $out .= '<div class="page"><a href="' . $path . '?' . $queryString . '" tabindex="0">' . $page . '</a></div>';
                        } else {
                            $out .= '<div class="page"><a href="' . $path . '?pn=' . $pagen . '&' . $queryString . '" tabindex="0">' . $page . '</a></div>';
                        }
                    } else {
                        $out .= '<div class="page current">' . $page . '</div> ';
                    }
                    $page++;
                    $countpage--;
                }
            }
            if ($countpage >= 0 && $maxcount < $totalPages) {
                $out .= '<div class="page dotted"><a href="' . $path . '?pn=' . ($maxcount + 1) . '&' . $queryString . '" tabindex="0">....</a></div>';
            }
        }
        if ($pn < round($rows / $this->rowsLimit)) {
            $out .= '<div class="paginate">
                <a href="' . $path . '?pn=' . min($totalPages, $pn + 1) . '&' . $queryString . '"
                   title="Вперёд" tabindex="0"><span class="material-icons">navigate_next</span></a>
            </div>';
        }
        $out .= '</div>';
        return $out;
    }


    public function buildSortFilter(
        string $tableName,
        string $columnText,
        string $columnName,
        string $columnType,
        array $filterItems = [],
        string $action = 'suggest',
        string $inputType = 'text',
        bool $idAsValue = false,
        string $filterName = '',
        string $ext_option = ''): string
    {
        $filter_selected = (is_array($this->filterFields[$columnName]) && count($this->filterFields[$columnName]) > 0);
        $filterName = ($filterName == '') ? $columnName : $filterName;
        $sorterName = $filterName;
        $icon = 'north';

        if ($filterName != '') {
            $sortArr = explode(':', $this->_get['sort']);
            $sorterName = $sortArr[array_search($filterName, $sortArr)];
            if (substr_count($sorterName, '_r') == 0) {
                $sorterName = $filterName . '_r';
                $icon = 'south';
            } else {
                $sorterName = $filterName;
                $icon = 'north';
            }

        } else {
            $icon = (is_array($this->sortFields[$columnName])) ?
                $this->sortFields[$columnName]['arrow'] : 'north';
        }

        $columnHtml = '<div class="head_sort_filter">
                <div class="button icon text sorter"
                     title="Сортировать"
                     data-field="' . ((is_array($this->sortFields[$columnName])) ? $this->sortFields[$columnName]['value'] :
                (($filterName != '' || $filterName != $columnName) ? $sorterName : $columnName)) . '">
                    ' . $columnText . '<span class="material-icons">' . $icon . '</span></div>';
        if ($inputType != 'date') {
            $columnHtml .= '<div class="button icon filterer' . (($filter_selected) ? ' active' : '') . '" title="Фильтр">
                    <span class="material-icons">filter_alt</span></div>';
        }
        $columnHtml .= '</div>';
        if ($columnType == 'constant') {

            $columnHtml .= '<div class="data_filter_select ' . $columnType . '"
                 style="display:' . (($filter_selected && $this->_cookie['role_show_filter_' . $filterName] == 'open') ? 'block' : 'none') . '">
                <div class="el_suggest_list bottom">';
            if (is_array($filterItems) && count($filterItems) > 0) {
                foreach ($filterItems as $value => $text) {
                    if (is_array($text)) {
                        $text = implode(' ', $text);
                    }
                    $columnHtml .= '<div class="el_option"><label class="container">' . $text . '
                        <input type="checkbox"' . ((is_array($this->filterFields[$columnName])
                            && in_array($value, $this->filterFields[$columnName])) ? ' checked' : '') . '
                         name="filter_' . $filterName . '[]" value="' . $value . '" class="filterer">
                        <span class="checkmark"></span></label></div>';
                }
            }
            $columnHtml .= '</div></div>';
        } else {
            $columnHtml .= '<div class="data_filter_select ' . $columnType . '" style="display:' . (($filter_selected &&
                    $this->_cookie['role_show_filter_' . $filterName] == 'open') ? 'block' : 'none') . '">
                            <input type="' . $inputType . '" class="el_input el_suggest"
                            autocomplete="off"
                                   data-src=\'{"action": "' . $action . '", "source": "' . $tableName . '", "value": "' . $columnName . '", 
                                   "column": "' . $columnName . '"' . (($idAsValue) ? ', "idAsValue": true' : '') .
                (($ext_option != '') ? ', "ext_option": "' . $ext_option . '"' : '') . '}\'
                                   multiple name="filter_' . $filterName . '[]" placeholder="Начните вводить...">';

            if (is_array($this->filterFields[$columnName]) && count($this->filterFields[$columnName]) > 0) {
                $columnHtml .= '<div class="el_suggest_list bottom">
                                <div class="el_multi_bar" style="">
                                <div class="button icon uncheck_all"><span class="material-icons">remove_done</span></div>
                                <div class="button icon done close_select"><span class="material-icons">highlight_off</span></div>
                                </div>';
                $this->filterFields[$columnName] = array_unique($this->filterFields[$columnName]);
                foreach ($this->filterFields[$columnName] as $fItem) {

                    $text = ($idAsValue) ? $filterItems[$fItem] : $fItem;

                    $columnHtml .= '<div class="el_option" data-value="' . $fItem . '"><label class="container">
                                    ' . $text . '<input type="checkbox" name="filter_' . $columnName . '[]" value="' . $fItem . '" checked>
                                    <span class="checkmark"></span></label></div>';
                }
                $columnHtml .= '</div>';
            }
            $columnHtml .= '</div>';
        }

        return $columnHtml;
    }

    public function buildTopNav(array $items): string
    {
        $navHtml = '';
        $perms = $this->auth->checkModulePermissions($this->module_id);

        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item => $title) {
                switch ($item) {
                    case 'title':
                        $navHtml .= '<div class="title">' . $title . '</div>';
                        break;
                    case 'renew':
                        $navHtml .= '<button tabindex="0" class="button icon" id="button_nav_refresh" title="' . $title . '">
                                    <span class="material-icons">autorenew</span></button>';
                        break;
                    case 'colored':
                        $navHtml .= '<button tabindex="0" class="button icon" id="button_nav_colored" title="' . $title . '">
                                    <span class="material-icons">invert_colors</span></div></div></button>';
                        break;
                    case 'create':
                        if ($perms['edit']) {
                            $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_create" title="' . $title . '">
                                    <span class="material-icons">control_point</span><span>Создать</span></button>';
                        }
                        break;
                    case 'create_block':
                        if ($perms['edit']) {
                            $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_create_block" title="' . $title . '">
                                    <span class="material-icons">control_point</span><span>Создать блок</span></button>';
                        }
                        break;
                    case 'clone':
                        if ($perms['edit']) {
                            $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_clone" title="' . $title . '">
                                    <span class="material-icons">control_point_duplicate</span><span>Клонировать</span></button>';
                        }
                        break;
                    case 'spend':
                        $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_spend" title="' . $title . '">
                                    <span class="material-icons">task_alt</span><span>Провести</span></button>';
                        break;
                    case 'unspend':
                        $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_unspend" title="' . $title . '">
                                    <span class="material-icons">radio_button_unchecked</span><span>Отменить</span></button>';
                        break;

                    case 'for_del':
                        $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_fordel" title="' . $title . '">
                                    <span class="material-icons">remove_circle_outline</span><span>На удаление</span></button>';
                        break;
                    case 'delete':
                        if ($this->auth->isAdmin() || $perms['delete']) {
                            $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_delete" title="' . $title . '">
                                    <span class="material-icons">delete_forever</span><span>Удалить</span></button>';
                        }
                        break;
                    case 'archive':
                        if ($this->auth->isAdmin() || $perms['delete']) {
                            $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_archive" title="' . $title . '">
                                    <span class="material-icons">archive</span><span>В архив</span></button>';
                        }
                        break;
                    case 'restore':
                        if ($this->auth->isAdmin() || $perms['delete']) {
                            $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_restore" title="' . $title . '">
                                    <span class="material-icons">unarchive</span><span>Восстановить</span></button>';
                        }
                        break;
                    case 'setRole':
                        $navHtml .= '<button tabindex="0" class="button icon text disabled group_action" id="button_nav_setRole" title="' . $title . '">
                                    <span class="material-icons">admin_panel_settings</span><span>Роль</span></button>';
                        break;
                    case 'viewSettings':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_settings" title="' . $title . '">
                                    <span class="material-icons">tune</span><span>Настройки</span></button>';
                        break;
                    case 'export_excel':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="export_excel" title="' . $title . '">
                                    <span class="material-icons">download</span><span>Экспорт в Excel</span></button>';
                        break;
                    case 'registry':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_registry" title="Назад к справочникам">
                                        <span class="material-icons">folder</span><span>Все справочники</span>
                                    </button>';
                        break;
                    case 'plans':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_plans" title="Назад к планам проверок">
                                        <span class="material-icons">assignment</span><span>Планы проверок</span>
                                    </button>';
                        break;
                    case 'checklists':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_checks" title="Назад к чек-листам">
                                        <span class="material-icons">checklist</span><span>Чек-листы</span>
                                    </button>';
                        break;
                    case 'list_props':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_list_props" title="Набор полей справочников">
                                        <span class="material-icons">list</span><span>Поля справочников</span>
                                    </button>';
                        break;
                    case 'check_props':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_check_props" title="Набор полей чек-листов">
                                        <span class="material-icons">list</span><span>Поля чек-листов</span>
                                    </button>';
                        break;
                    case 'check_items':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_check_items" title="Набор пунктов чек-листов">
                                        <span class="material-icons">list</span><span>Пункты чек-листов</span>
                                    </button>';
                        break;
                    case 'switch_plan':
                        $navHtml .= '<div class="toggle-switch">
                                        Вид: 
                                        <div class="toggle-switch-item one" title="Табличный вид">
                                          <input type="radio" id="switch_table" name="toggle_view"' . ($_COOKIE['calendar_view'] == 'table' ? ' checked="checked"' : '') . '>
                                          <label for="switch_table" class="switch"><span class="material-icons">view_list</span> <span>Таблица</span></label>
                                        </div>
                                        <div class="toggle-switch-item two" title="Классический календарь">
                                          <input type="radio" id="switch_calendar" name="toggle_view"' . ($_COOKIE['calendar_view'] == 'calendar' ? ' checked="checked"' : '') . '>
                                          <label for="switch_calendar" class="switch"><span class="material-icons">date_range</span> <span>Календарь</span></label>
                                        </div>
                                        <div class="toggle-switch-item three" title="График Ганта">
                                          <input type="radio" id="switch_gantt" name="toggle_view" ' . ($_COOKIE['calendar_view'] == 'gantt' ? ' checked="checked"' : '') . '>
                                          <label for="switch_gantt" class="switch"><span class="material-icons">view_timeline</span> <span>Гант</span></label>
                                        </div>
                                    </div>';
                        break;
                    case 'reg_settings':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="button_nav_reg_settings" title="Настройки реестра">
                                        <span class="material-icons">construction</span> <span>Настройки</span> 
                                    </button>';
                        break;
                    case 'dates':
                        $interval = [];

                        $dates = $this->date->getDefaultRange($this->module_id);
                        if ($dates != '') {
                            $datesArr = explode(' - ', $dates);
                            $dates = $datesArr[0] . ' - ' . $datesArr[1];
                        } else {
                            $interval[] = $_GET['date_from'];
                            $interval[] = $_GET['date_to'];
                            $dates = (count($interval) > 0) ? implode(' - ', $interval) : '';
                            $dates = ($dates == ' - ') ? '' : $dates;
                        }
                        $navHtml .= '<div class="group">
                                        <div class="date_range">
                                            <div class="el_data" title="Установить дату или период">
                                                <input class="el_input" type="date" id="top_calendar" tabindex="0">
                                            </div>
                                        </div>
                                    </div>';
                        break;
                    case 'registryList':
                        $regId = (isset($_GET['id']) && intval($_GET['id']) > 0)
                            ? intval($_GET['id']) : intval(str_replace('id=', '', $_POST['params']));
                        $regs = $this->db->getRegistry('registry');
                        $registrys = $regs['array'];
                        $navHtml .= '<form id="registry_list">
                            <div class="nav_02">
                                <div class="widget_01">
                                    <div class="nav_select">
                                        <select name="guide" data-label="" tabindex="0">
                                        <option value="0">Все справочники</option>';
                        foreach ($registrys as $value => $text) {
                            $sel = ($value == $regId) ? ' selected' : '';
                            $navHtml .= '<option value="' . $value . '"' . $sel . '>' . $text . '</option>';
                        }
                        $navHtml .= '</select>
                                    </div>
                                </div>
                            </div>
                        </form>';
                        break;
                    case 'planList':
                        $regId = (isset($_GET['id']) && intval($_GET['id']) > 0)
                            ? intval($_GET['id']) : intval(str_replace('id=', '', $_POST['params']));
                        $regs = $this->db->getRegistry('checksplans', ' WHERE active = 1 ORDER BY year DESC',
                            [], ['short', 'year', 'id']
                        );
                        $registrys = $regs['array'];
                        $navHtml .= '<form id="plan_list">
                            <div class="nav_02">
                                <div class="widget_01">
                                    <div class="nav_select">
                                        <select name="guide" data-label="" tabindex="0">
                                        <option value="">Выберите план</option>
                                        <option value="0"' . ($regId == 0 ? ' selected' : '') . '>Задачи без плана</option>';
                        foreach ($registrys as $value => $text) {
                            $sel = ($value == $regId || intval($_COOKIE['current_plan_id']) == $value) ? ' selected' : '';
                            $navHtml .= '<option value="' . $value . '"' . $sel . '>' . $text[1] . 'г. ' . $text[0] . '</option>';
                        }
                        $navHtml .= '</select>
                                    </div>
                                </div>
                            </div>
                        </form>';
                        break;
                    case 'statList':
                        $statId = (isset($_GET['stat_type']) && intval($_GET['stat_type']) > 0)
                            ? intval($_GET['stat_type']) : intval(str_replace('stat_type=', '', $_POST['params']));

                        $navHtml .= '
                            <div class="nav_02">
                                <div class="widget_01">
                                    <div class="nav_select">
                                        <select name="stat_type" id="stat_type_list" data-label="" tabindex="2" title="' . $title . '">
                                            <option value="0"' . ($statId == 0 ? ' selected' : '') . '>Статистика по обращениям</option>
                                            <option value="1"' . ($statId == 1 ? ' selected' : '') . '>Статистика АТС по входящим звонкам</option>
                                            <option value="2"' . ($statId == 2 ? ' selected' : '') . '>Статистика АТС по операторам</option>
                                            <option value="3"' . ($statId == 3 ? ' selected' : '') . '>Статистика по обзвонам</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        ';
                        break;
                    case 'callsList':
                        $statId = (isset($_GET['calls_list']) && intval($_GET['calls_list']) > 0)
                            ? intval($_GET['calls_list']) : intval(str_replace('calls_list=', '', $_POST['params']));

                        $navHtml .= '
                            <div class="nav_02">
                                <div class="widget_01">
                                    <div class="nav_select">
                                        <select name="calls_list" id="calls_list" data-label="" tabindex="2" title="' . $title . '">
                                            <option value="0"' . ($statId == 0 ? ' selected' : '') . '>Задания по обзвонам</option>
                                            <option value="1"' . ($statId == 1 ? ' selected' : '') . '>Результаты обзвонов</option>
                                            <option value="2"' . ($statId == 2 ? ' selected' : '') . '>Шаблоны обзвонов</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        ';
                        break;
                    case 'search':
                        $navHtml .= '
                                <div class="group">
                                        <div class="date_range">
                                            <div class="search_field el_data" title="' . $title . '">
                                <input type="text" class="el_input el_suggest" autocomplete="off" 
                                data-src="{&quot;source&quot;: &quot;main&quot;, &quot;value&quot;: &quot;search&quot;, 
                                   &quot;column&quot;: &quot;all&quot;}" multiple="" name="filter_search" 
                                   placeholder="Начните вводить..." id="el_suggest_main">
                                   </div>
                                        </div>
                                    </div>';
                        /* $navHtml .= '<div class="group">
                                         <div class="date_range">
                                             <div class="search_field el_data" title="' . $title . '">
                                                 <input type="text" name="search" class="el_input " tabindex="3" value="' . $_GET['search'] . '">
                                             </div>
                                         </div>
                                     </div>';*/
                        break;
                    case 'filter_panel':
                        $navHtml .= '<button tabindex="0" class="button icon text" id="filter_panel" title="' . $title . '">
                                <span class="material-icons">filter_alt</span><span>Фильтр</span></button>';
                        break;
                    case 'importEAIS':
                        if ($this->auth->isLogin()) {
                            $navHtml .= '<button tabindex="0" class="button icon text" id="eais_import" title="' . $title . '">
                                    <span class="material-icons">file_download</span><span>ЕАИС</span></button>';
                        }
                        break;
                    case 'import':
                        if ($this->auth->isLogin()) {
                            $navHtml .= '<button tabindex="0" class="button icon text" id="reg_import" title="' . $title . '">
                                    <span class="material-icons">file_download</span><span>Импорт</span></button>';
                        }
                        break;
                    case 'logout':
                        $roles = $this->db->getRegistry('roles');
                        $roleString = '';
                        if (substr_count($_SESSION['user_roles'], ',') > 0) {
                            $userRolesArr = explode(',', $_SESSION['user_roles']);
                            $roleNamesArr = [];
                            foreach ($userRolesArr as $role) {
                                $roleNamesArr[] = $roles['array'][intval($role)];
                            }
                            $roleString = implode(', ', $roleNamesArr);
                        } else {
                            $roleString = $roles['array'][intval($_SESSION['user_roles'])];
                        }
                        $notsHtml = '';
                        if (intval($_SESSION['user_id']) > 0) {
                            $nots = $this->notes->getRecordsToPanel($_SESSION['user_id']);
                            $notsTotal = intval($nots['countTotal']);
                            $notsUnseens = intval($nots['countUnseens']);

                            //if($notsTotal > 0){
                            $countText = 'Всего ' . $notsTotal . ' уведомлени' . $this->postfix($notsTotal, 'е', 'я', 'й');
                            $notsHtml = '<button tabindex="0" class="button icon right" title="' . $countText . '" id="notification_panel">' .
                                '<span id="messageCount">' . $notsTotal . '</span>
                                    <span class="material-icons">notifications</span></button>';
                            //}
                            if ($notsUnseens > 0) {
                                $countText = $notsUnseens . ' нов' . $this->postfix($notsTotal, 'ое', 'ых', 'ых') . ' уведомлени' .
                                    $this->postfix($notsTotal, 'е', 'я', 'й');
                                $notsHtml = '<button tabindex="0" class="button icon right shake" title="' . $countText . '" id="notification_panel">' .
                                    '<span id="messageCount">' . $notsUnseens . '</span>
                                    <span class="material-icons">notifications_active</span></button>';
                            }

                            $navHtml .= '<button tabindex="0" class="button icon" id="fullscreen" title="Перейти в полноэкранный режим">
                                    <span class="material-icons">open_in_full</span></button>' .
                                '<span class="user_fio right">Вы вошли как ' . $roleString . ' '
                                . $_SESSION['user_surname'] . ' ' . $_SESSION['user_name'] . ' ' . $_SESSION['user_middle_name'] . '</span>' .
                                $notsHtml .
                                '<button tabindex="0" class="button icon right" title="' . $title . '">
                                    <span class="material-icons" id="logout">logout</span></button>';
                        }
                        break;
                }
            }
        }
        return $navHtml;
    }

    /**
     * Строит список вариантов выбора из реестра.
     *
     * @param array $items Массив элементов реестра
     * @param array $selected Массив выбранных id элементов
     * @param bool $firstEmpty True, чтобы добавить пустой вариант в качестве первого элемента
     * @param array $fields Массив полей для генерации значений
     * @param string $separator Разделитель для объединения полей
     * @param array $dataFields Массив имен полей для формирования data-атрибутов у option
     *
     * @return string HTML строка вариантов выбора
     */
    public function buildSelectFromRegistry(
        array $items,
        array $selected = [],
        bool $firstEmpty = false,
        array $fields = [],
        string $separator = ' ',
        array $dataFields = [],
        bool $hideOptions = false
    ): string
    {
        $list = ($firstEmpty) ? '<option value=""' . ($hideOptions ? ' style="display: none"' : '') . '>&nbsp;</option>' : '';
        foreach ($items as $item) {
            $iid = is_numeric($item->id) ? intval($item->id) : (string)$item->id;
            $sel = (in_array($iid, $selected)) ? ' selected="selected"' : '';
            $dataOptions = [];
            $dataString = '';
            $value = $item->name;
            if (count($fields) > 0) {
                $fArr = [];
                foreach ($fields as $field) {
                    $fArr[] = $item->$field;
                }
                $value = implode($separator, $fArr);
            }
            if (count($dataFields) > 0) {
                foreach ($dataFields as $df) {
                    if (strlen(trim($item->$df)) > 0)
                        $dataOptions[] = 'data-' . $df . '="' . htmlspecialchars($item->$df) . '"';
                }
                $dataString = ' ' . implode(' ', $dataOptions);
            }
            $list .= '<option value="' . $item->id . '"' . $sel . $dataString . ($hideOptions ? ' style="display: none"' : '') . '>' . stripslashes($value) . '</option>' . "\n";
        }
        return $list;
    }

    public function allowShowColumn(int $module, string $colName): bool
    {
        if (strlen(trim($this->_session['user_settings'][0][$module]['view_settings']['columns'])) > 0) {
            $allowColumns = explode(',', $this->_session['user_settings'][0][$module]['view_settings']['columns']);
            return in_array($colName, $allowColumns);
        } else {
            return true;
        }
    }

    public function dateToString($date): string
    {
        if (strlen(trim($date)) > 0) {
            $dateArr = [];
            $time = '';
            if (substr_count($date, ' ') > 0) {
                $dateArr = explode(' ', $date);
                $date = $dateArr[0];
                $timeArr = explode('.', $dateArr[1]);
                $time = $timeArr[0];
            }
            $year = strtok($date, '-');
            $month = strtok('-');
            $day = strtok('');
            switch ($month) {
                case 1:
                    $mont = 'янв';
                    break;
                case 2:
                    $mont = 'фев';
                    break;
                case 3:
                    $mont = 'мар';
                    break;
                case 4:
                    $mont = 'апр';
                    break;
                case 5:
                    $mont = 'мая';
                    break;
                case 6:
                    $mont = 'июн';
                    break;
                case 7:
                    $mont = 'июл';
                    break;
                case 8:
                    $mont = 'авг';
                    break;
                case 9:
                    $mont = 'сен';
                    break;
                case 10:
                    $mont = 'окт';
                    break;
                case 11:
                    $mont = 'ноя';
                    break;
                case 12:
                    $mont = 'дек';
                    break;
            }
            return $day . ' ' . $mont . ' ' . $year . 'г. ' . ((is_array($dateArr)) ? $time : '');
        } else {
            return '';
        }
    }

    public function clearFio($fio): string
    {
        if (strlen($fio) > 0 && !is_null($fio)) {
            $fio = mb_strtolower($fio);
            $fio = str_replace('  ', ' ', $fio);
            $fio = mb_convert_case(trim(preg_replace('/[^а-яА-ЯёЁ\s-]/imu', '', $fio)), MB_CASE_TITLE, 'UTF-8');
            return str_replace("\t", ' ', $fio);
        } else {
            return '';
        }
    }

    public function getUserFio(?int $user_id, $mode = 'full'): string
    {
        if (intval($user_id) > 0) {
            $users = $this->db->getRegistry('users');
            return $mode == 'full' ? $users['result'][$user_id]->surname . ' ' .
                $users['result'][$user_id]->name . ' ' .
                $users['result'][$user_id]->middle_name :
                $users['result'][$user_id]->surname . ' ' .
                mb_substr($users['result'][$user_id]->name, 0, 1) . '. ' .
                mb_substr($users['result'][$user_id]->middle_name, 0, 1) . '.';
        } else {
            return '';
        }
    }

    public function clearPhoneNumber($phone, $formatting = true): string
    {
        $phone = str_replace(['(', ')', '+', '-', ' '], '', trim($phone));
        if (strlen($phone) == 11 && in_array(substr($phone, 0, 1), ['7', '8', '9'])) {
            $phone = substr_replace($phone, '7', 0, 1);
            return $formatting ? sprintf('+%s (%s) %s-%s-%s',
                substr($phone, 0, 1),
                substr($phone, 1, 3),
                substr($phone, 4, 3),
                substr($phone, 7, 2),
                substr($phone, 9)
            ) : '+' . $phone;
        } else {
            return '';
        }
    }

    public function arrayMaxLength($array): string
    {
        $out = '';
        $length = 0;
        foreach ($array as $item) {
            $lengthItem = strlen($item);
            if ($lengthItem > $length) {
                $out = $item;
                $length = $lengthItem;
            }
        }
        return $out;
    }

    public function postfix($number, $one, $two, $five)
    {
        $number = intval($number);
        $out = $one;
        if ($number > 20) {
            $numArr = str_split($number);
            $number = $numArr[count($numArr) - 1];
            $out = $this->postfix($number, $one, $two, $five);
        } elseif ($number == 1) {
            $out = $one;
        } elseif ($number > 1 && $number < 5) {
            $out = $two;
        } elseif ($number >= 5 || $number == 0) {
            $out = $five;
        }
        return $out;
    }

    //Функция перевода русских слов в транслит в url
    public function translit_url($string, $type = '')
    {
        $string = ($type == 'file') ? preg_replace('/\\.(?![^.]*$)/', '_', $string) : $string;
        $r_trans = array(
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м',
            'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'э',
            'ю', 'я', 'ъ', 'ы', 'ь', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М',
            'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Э',
            'Ю', 'Я', 'Ъ', 'Ы', 'Ь', '', "'"
        );
        $e_trans = array(
            'a', 'b', 'v', 'g', 'd', 'e', 'e', 'j', 'z', 'i', 'i', 'k', 'l', 'm',
            'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch',
            'e', 'yu', 'ya', '', 'i', '', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'j', 'z', 'i', 'i', 'k', 'l', 'm',
            'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch',
            'e', 'yu', 'ya', '', 'i', '', '', ''
        );
        $string = str_replace($r_trans, $e_trans, $string);
        return $string;
    }


//Функция перевода русских слов в транслит
    public function translit($string, $type = '', $mode = 'no_whitespace')
    {
        $string = ($type == 'file') ? preg_replace('/\\.(?![^.]*$)/', '_', $string) : $string;
        $r_trans = array(
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м',
            'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'э',
            'ю', 'я', 'ъ', 'ы', 'ь', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М',
            'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Э',
            'Ю', 'Я', 'Ъ', 'Ы', 'Ь', '(', ')', "'"
        );
        $e_trans = array(
            'a', 'b', 'v', 'g', 'd', 'e', 'e', 'j', 'z', 'i', 'i', 'k', 'l', 'm',
            'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch',
            'e', 'yu', 'ya', '', 'i', '', 'A', 'B', 'V', 'G', 'D', 'E', 'E', 'J', 'Z', 'I', 'I', 'K', 'L', 'M',
            'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch',
            'E', 'Yu', 'Ya', '', 'I', '', '', ''
        );
        if ($mode == 'no_whitespace') {
            $string = str_replace(' ', '-', $string);
        }
        $string = str_replace($r_trans, $e_trans, $string);
        return $string;
    }

    public function genpass(int $numchar = 8): string
    {
        $str = 'abcefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $password = substr(str_shuffle($str), 0, $numchar);
        } while (
            strlen($password) < $numchar ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        );
        return $password;
    }

    public function getOperatorName($users, $id): string
    {
        return trim($users['result'][$id]->surname) . ' ' . trim($users['result'][$id]->name) . ' ' .
            trim($users['result'][$id]->middle_name);
    }

    public function unsetFromParams(string $params, string $paramName): string
    {
        $paramsArr = explode(';', $params);
        for ($i = 0; $i < count($paramsArr); $i++) {
            $item = $paramsArr[$i];
            $itemArr = explode(':', $item);
            if ($itemArr[0] == $paramName) {
                array_splice($paramsArr, $i);
            }
        }
        return implode(';', $paramsArr);
    }

    public function buildOUSRSelect()
    {
        $ousr = $this->db->select('users', 'roles = 13');
    }

    public function limitStrlen($input, $length, $ellipses = true, $strip_html = true)
    {
        //strip tags, if desired
        if ($strip_html) {
            $input = strip_tags($input);
        }

        //no need to trim, already shorter than trim length
        if (strlen($input) <= $length) {
            return $input;
        }

        //find last space within length
        $last_space = strrpos(mb_substr($input, 0, $length), ' ');
        if ($last_space !== false) {
            $trimmed_text = mb_substr($input, 0, $last_space);
        } else {
            $trimmed_text = mb_substr($input, 0, $length);
        }
        //add ellipses (...)
        if ($ellipses) {
            $trimmed_text .= '...';
        }

        return $trimmed_text;
    }

    /**
     * Форматирует номер телефона в единый стандарт +7 (XXX) XXX-XX-XX
     *
     * @param string $phone Номер телефона в любом формате
     * @return string Отформатированный номер или исходная строка, если не удалось распознать номер
     */
    public function formatPhone(string $phone): string
    {
        // Удаляем все нецифровые символы
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Определяем длину номера
        $length = strlen($cleaned);

        // Обрабатываем номера, начинающиеся с 7 или 8 (Россия)
        if ($length === 11 && ($cleaned[0] === '7' || $cleaned[0] === '8')) {
            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($cleaned, 1, 3),
                substr($cleaned, 4, 3),
                substr($cleaned, 7, 2),
                substr($cleaned, 9, 2)
            );
        }

        // Обрабатываем номера без кода страны (10 цифр)
        if ($length === 10) {
            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6, 2),
                substr($cleaned, 8, 2)
            );
        }

        // Обрабатываем международные номера (начинающиеся с других цифр)
        if ($length > 3) {
            return '+' . $cleaned; // Просто добавляем + в начале
        }

        // Возвращаем исходное значение, если не удалось распознать
        return $phone;
    }

    // Функция для преобразования размера из формата php.ini (например, 2M) в байты
    public function parse_size($size): float
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }

    /*
     * Генератор пастельных цветов
     */
    public function generateRandomPastelColor(): string
    {
        $r = mt_rand(150, 255);
        $g = mt_rand(150, 255);
        $b = mt_rand(150, 255);
        return "rgb($r, $g, $b)";
    }

    public function generateDarkHslHexColor(): string
    {
        $hue = mt_rand(0, 360);
        $saturation = mt_rand(70, 100);
        $lightness = mt_rand(10, 30);

        return $this->hslToHex($hue, $saturation, $lightness);
    }

    public function hslToHex($h, $s, $l): string
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod(($h * 6), 2) - 1));
        $m = $l - ($c / 2);

        if ($h < 1 / 6) {
            list($r, $g, $b) = [$c, $x, 0];
        } elseif ($h < 2 / 6) {
            list($r, $g, $b) = [$x, $c, 0];
        } elseif ($h < 3 / 6) {
            list($r, $g, $b) = [0, $c, $x];
        } elseif ($h < 4 / 6) {
            list($r, $g, $b) = [0, $x, $c];
        } elseif ($h < 5 / 6) {
            list($r, $g, $b) = [$x, 0, $c];
        } else {
            list($r, $g, $b) = [$c, 0, $x];
        }

        $r = str_pad(dechex(round(($r + $m) * 255)), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex(round(($g + $m) * 255)), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex(round(($b + $m) * 255)), 2, '0', STR_PAD_LEFT);

        return '#' . $r . $g . $b;
    }
}