<?php

namespace Core;

use Exception;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Excel2007;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RedBeanPHP\RedException\SQL;
use Core\Db;
use Core\Date;
use Core\Gui;
use Throwable;


class Registry
{

    private /*array*/
        $_get, $_post, $_session, $_cookie, $_server;
    private /*R*/
        $rb;
    /**
     * @var \Core\Db
     */
    private $db;
    /**
     * @var string[]
     */
    public $props_array;
    /**
     * @var \Core\Gui
     */
    private $gui;
    /**
     * @var \Core\Date
     */
    private $date;
    /**
     * @var \Core\Auth
     */
    private Auth $auth;


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
        //$this->gui = new Gui();
        $this->props_array = [
            'text' => 'Однострочный текст',
            'textarea' => 'Многострочный текст',
            'radio' => 'Радио-кнопка',
            'checkbox' => 'Чек-кнопка',
            'list_fromdb' => 'Список из другого справочника',
            'list_fromdb_multi' => 'Список из другого справочника с множественным выбором',
            'select' => 'Выпадающий список',
            'multiselect' => 'Выпадающий список с множественным выбором',
            'depend_list' => 'Родительский справочник',
            'addInstitution' => 'Добавить учреждение',
            'addObject' => 'Добавить объект проверки в задание',
            'addSignatory' => 'Добавить подписанта',
            'addAligner' => 'Добавить согласование',
            'addAgreement' => 'Добавить секцию согласования',
            'checklists' => 'Выбор чек-листа',
            'integer' => 'Целое число',
            'float' => 'Дробное число',
            'phone' => 'Номер телефона',
            'price' => 'Цена',
            'email' => 'E-mail адрес',
            'file' => 'Файл',
            'years' => 'Года',
            'calendar' => 'Дата',
            'time' => 'Время',
            'quarterSelect' => 'Выбор квартала',
            'datetime' => 'Дата и время',
            'multi_date' => 'Множественный выбор дат',
            'range_date' => 'Диапозон дат',
            'password' => 'Пароль',
            'inn' => 'ИНН',
            'html' => 'Визуальный редактор',
            'comments' => 'Комментарии',
            'import_button' => 'Кнопка импорта плана',
            'violations' => 'Добавить нарушения'
        ];
    }

    public function set($key, $val)
    {
        $this->$key = $val;
    }

    public function get($key)
    {
        return $this->$key;
    }

    /**
     * Проверяет, все ли значения в массиве пусты
     * Пустыми считаются значения: null, пустая строка, false, 0 (только строковый '0'), пустой массив
     *
     * @param array $array Проверяемый массив
     * @return bool Возвращает true, если все значения пусты, иначе false
     */
    public function allValuesEmpty(array $array): bool
    {
        // Если массив пуст
        if (empty($array)) {
            return true;
        }

        foreach ($array as $value) {
            // Более строгая проверка на "пустоту"
            if ($value !== null && $value !== '' && $value !== false && $value !== '0' && $value !== 0
                && !(is_array($value) && empty($value))) {
                return false;
            }
        }

        return true;
    }

    public function renderTextInput(array $f, array $editData, string $mode): string
    {
        $field_name_ws = str_replace('[]', '', $f['field_name']);
        $default_value = strlen($editData[$field_name_ws]) > 0 ? $editData[$field_name_ws] : $f['default_value'];
        $class = '';
        switch ($f['type']) {
            case 'password':
                $field_type = 'text';
                $default_value = '';
                $f['required'] = strlen($editData[$field_name_ws]) > 0 ? 0 : 1;
                break;
            case 'integer':
                $field_type = 'number';
                break;
            case 'single_date':
                $field_type = 'date';
                $class = ' single_date';
                break;
            case 'date_time':
                $field_type = 'datetime-local';
                $class = ' single_date_time';
                // datetime-local требует формат "yyyy-MM-ddTHH:mm", конвертируем пробел в T
                $default_value = str_replace(' ', 'T', $default_value);
                break;
            default:
                $field_type = $f['type'];
        }
        return '<div class="item w_50' . ($f['required'] == '1' ? ' required' : '') . '">
                        <div class="el_data">
                            <label>' . $f['label'] . '</label>
                            <input' . ($f['required'] == '1' ? ' required' : '') . ' class="el_input' . $class . '" type="' . $field_type . '"
                             ' . (strlen($f['placeholder']) > 0 ? ' placeholder="' . $f['placeholder'] . '"' : '') .
            ($mode == 'result' ? ' disabled="disabled"' : '') .
            (strlen($f['mask']) > 0 ? ' pattern="' . $f['mask'] . '"' : '') .
            (intval($f['step']) > 0 ? ' step="' . $f['step'] . '"' : '') .
            (intval($f['size']) > 0 ? ' size="' . $f['size'] . '"' : '') .
            (intval($f['min_value']) > 0 ? ' minlength="' . $f['min_value'] . '"' : '') .
            (intval($f['max_value']) > 0 ? ' maxlength="' . $f['max_value'] . '"' : '') . ' 
                             name="' . $f['field_name'] . '" value="' . trim(stripslashes(htmlspecialchars($default_value))) . '">
                        </div>' .
            ($f['type'] == 'password' ? '<div class="button icon" id="gen_pass" title="Сгенерировать"><span class="material-icons">bolt</span></div>' : '')
            . '</div>';
    }

    public function renderTextarea(array $f, array $editData, string $mode): string
    {
        $html = '';
        $elemId = 'textarea' . uniqid();
        $default_value = strlen($editData[$f['field_name']]) > 0 ? $editData[$f['field_name']] : $f['default_value'];
        $html .= '<div class="item w_100' . ($f['required'] == '1' ? ' required' : '') . '">
                        <div class="el_data">
                            <label>' . $f['label'] . '</label>
                            <textarea class="el_textarea" name="' . $f['field_name'] . '"' . ' id="' . $elemId . '"' .
            ($mode == 'result' ? ' disabled="disabled"' : '') .
            (intval($f['cols']) > 0 ? ' cols="' . $f['cols'] . '"' : '') .
            (intval($f['rows']) > 0 ? ' rows="' . $f['rows'] . '"' : '') . '>' .
            trim(str_replace('<br>', "\n", stripslashes(htmlspecialchars($default_value)))) . '</textarea>
                        </div>
                    </div>';
        if ($f['type'] == 'html') {
            $html .= '<script>
                tinymce.init({
                  target: document.querySelector("#' . $elemId . '"),
                  language: "ru",
                  plugins: "code link table autoresize lists",
                  width: "100%",
                  license_key: "gpl",
                  branding: false,
                  statusbar: false,
                  menubar: false,
                  extended_valid_elements: "code[*]", // Разрешает теги <code>
                  protect: [
                    /\{\{.*?\}\}/g,     // Защищает {{ переменные }}
                    /\{\%.*?\%\}/g      // Защищает {% операторы %}
                  ],
                  default_font_stack: [ "Times New Roman" ],
                  paste_postprocess: function(plugin, args) {
        // Очищаем после обработки TinyMCE
                        args.node.innerHTML = args.node.innerHTML
                            .replace(/<(\/)?(meta|link|o:|w:|style|xml)[^>]*?>/gi, "")
                            .replace(/class="[^"]*"/gi, "")
                            .replace(/style="[^"]*"/gi, "")
                            .replace(/<!--[\s\S]*?-->/gi, "")
                            .replace(/\n/g, " ")
                            .replace(/\s+/g, " ");
                    },
                  toolbar: "undo redo | pastetext| styles | fontsize | bold italic | alignleft aligncenter alignright alignjustify | outdent indent | bullist numlist | link | table | code"
                });';
            if ($mode == 'result') {
                $html .= 'tinymce.activeEditor.mode.set("readonly");';
            }
            $html .= '</script>';
        }
        return $html;
    }

    public function renderSelect(?array $f, ?array $editData, string $mode = ''): string
    {
        $items = [];
        $html = '';
        $multi = $f['multiselect'] == '1';

        if ($f['type'] == 'years') {
            $options_list_arr = [];
            $currentYear = date('Y');
            $startYear = $currentYear - 5;
            $endYear = $currentYear + 20;
            $years = range($startYear, $endYear);
            foreach ($years as $year) {
                $options_list_arr[] = ['label' => $year, 'value' => $year];
            }
            $f['options_list'] = json_encode($options_list_arr);
            $f['default_value'] = date('Y') + 1;
        }

        $field_name_ws = str_replace('[]', '', $f['field_name']);
        $field_name_ws_hidden = $multi || substr($f['field_name'], -2) == '[]' ? $field_name_ws . '_hidden[]' : $field_name_ws . '_hidden';
        $default_value = strlen($editData[$field_name_ws]) > 0 ? $editData[$field_name_ws] : $f['default_value'];
        if ($multi && strlen($editData[$field_name_ws]) > 0) {
            $default_value = json_decode($default_value);
        }
        $value_selected = strlen($editData['value_selected']) > 0 ? $editData['value_selected'] :
            (is_array($default_value) ? json_encode($default_value) : $default_value);
        if (strlen(trim($f['options_list'])) > 0) {
            $items = json_decode($f['options_list'], true);
        }
        $html .=
            '<div class="item w_' . ($f['multiselect'] == '1' ? '100' : '50') . ($f['required'] == '1' ? ' required' : '') . '"' . ($multi ? ' multiple' : '') . '>
            <input type="hidden" name="' . $field_name_ws_hidden . '" value=\'' . $value_selected . '\'>' . '
                <select data-label="' . $f['label'] . '" name="' . $f['field_name'] . ($multi ? '[]' : '') . '"' .
            ($mode == 'result' ? ' disabled="disabled"' : '') . ($multi ? ' multiple' : '') . '>
                <option value="">&nbsp;</option>';
        foreach ($items as $i) {
            $selected = '';
            if ($multi && strlen($editData[$f['field_name']]) > 0) {
                $selected = @in_array($i['value'], $default_value) ? ' selected="selected"' : '';
            } else {
                $selected = $default_value == $i['value'] ? ' selected="selected"' : '';
            }
            $html .= '<option value="' . $i['value'] . '"' . $selected . '>' .
                stripslashes(htmlspecialchars($i['label'])) . '</option>';
        }
        $html .= '</select></div>';
        return $html;
    }

    public function renderListFromDB(array $f, array $editData, string $mode = '', array $fields = [], int $valueField = 0): string
    {
        $items = [];
        $fieldArr = [];
        $html = '';
        $field_name = '';
        $itemValue = '';
        $from_db_checkboxes = intval($f['from_db_view']);
        $containerEnd = $containerStart = '';

        $field_name_ws = str_replace('[]', '', $f['field_name']);

        $default_value = strlen($editData[$field_name_ws]) > 0 ? $editData[$field_name_ws] : $f['default_value'];
        $multi = $f['from_db_view'] == '1' || $f['type'] == 'list_fromdb_multi';
        $item_class = ' w_' . ($multi ? '100' : '50');
        $field_name_ws_hidden = $multi || substr($f['field_name'], -2) == '[]' ? $field_name_ws . '_hidden[]' : $field_name_ws . '_hidden';
        if ($multi && strlen($editData[$field_name_ws]) > 0) {
            $default_value = json_decode($default_value);
        }

        //Пользователи
        if (intval($f['from_db']) == 40) {
            $fields = ['surname', 'name', 'middle_name'];
            $ins = $this->db->getRegistry('institutions', '', [], ['short']);
            $mins = $this->db->getRegistry('ministries');
            $units = $this->db->getRegistry('units');
        }

        //Шаблоны листов согласования
        if ($f['field_name'] == 'agreementtemplate') {
            $f['subQuery'] = ' AND documentacial = 6';
        } elseif ($f['field_name'] == 'document') {
            $f['subQuery'] = ' AND documentacial <> 6';
        }
        if ($f['label'] == 'Шаблон печатной формы плана') {
            $f['subQuery'] = ' AND documentacial = 3';
        }

        $r = $this->db->selectOne('registry', ' WHERE id = ?', [$f['from_db']]);
        $n = $this->db->selectOne('regprops', ' WHERE id = ?', [$f['from_db_text']]);
        $v = $this->db->selectOne('regprops', ' WHERE id = ?', [$f['from_db_value']]);

        if ($fields == []) {
            $fieldArr = [$n->field_name];
        } else {
            $fieldArr = $fields;
        }

        if ($valueField == 0) {
            $fieldValue = $v->field_name;
        } else {
            $fieldValue = $valueField;
        }

        $subQuery = (strlen($f['subQuery']) > 0) ? $f['subQuery'] : ' order by id';

        if (strlen($r->table_name) > 0) {
            /*$items = $this->db->getRegistry($r->table_name, ' WHERE active = 1' . $f['subQuery'],
                [], array_merge($fieldArr, [$v->field_name]));*/
            $itemsRes = $this->db->select($r->table_name, ' WHERE active = 1' . $f['subQuery']);
            $items['result'] = $itemsRes;
            $field_name = $n->field_name;
        }

        $value_selected = strlen($editData['value_selected']) > 0 ? $editData['value_selected'] :
            (is_array($default_value) ? json_encode($default_value) : $default_value);

        if (strlen($f['width']) > 0) {
            $item_class = ' w_' . $f['width'];
        }

        switch ($from_db_checkboxes) {
            case 0:
            case 1:
                $containerStart = '<div class="item' . $item_class . ' ' . $f['class'] . ($f['required'] == '1' ? ' required' : '') . '">
                <input type="hidden" name="' . $field_name_ws_hidden . '" value=\'' . $value_selected . '\'>
                <select data-label="' . $f['label'] . '" data-reg="' . $f['from_db'] . '" name="' . $f['field_name'] . ($multi ? '[]' : '') . '" ' .
                    ($mode == 'result' ? ' disabled="disabled"' : '') .
                    ($multi ? ' multiple' : '') . '>
                <option value="0">&nbsp;</option>';
                $containerEnd = '</select>';
                break;
            case 2:
            case 3:
                $containerStart = '<div class="item w_50"><div class="el_data vertical">';
                $containerEnd = '</div>';
                break;
        }

        $html .= $containerStart;
        if (strlen($r->table_name) > 0) {
            foreach ($items['result'] as $i) {
                $selected = '';
                $itemTitle = '';
                if ($multi && strlen($editData[$f['field_name']]) > 0) {
                    $selected = @in_array($i->id, $default_value) ? ' selected="selected"' : '';
                } else {
                    $selected = $default_value == $i->id ? ' selected="selected"' : '';
                }
                if (count($fieldArr) > 1) {
                    $itemValueArr = [];
                    foreach ($fieldArr as $field) {
                        $itemValueArr[] = trim($i->$field);
                    }
                    $itemValue = implode(' ', $itemValueArr);
                } else {
                    $itemValue = trim($i->$field_name);
                }
                if (intval($f['from_db']) == 40) {
                    $dataParamsArr = [];
                    $dataAttr = ['institution', 'ministries', 'division', 'position'];
                    $itemTitle = ' title="' . htmlspecialchars($ins['result'][$i->institution]->short .
                            (intval($i->ministries) > 0 ? '<br>' . $mins['array'][$i->ministries] : '') .
                            (intval($i->division) > 0 ? '<br>' . $units['array'][$i->division] : '') .
                            (strlen($i->position) > 0 ? '<br>' . $i->position : '')
                        ) . '"';
                }
                if (intval($f['from_db']) == 70) {
                    $itemTitle = ' title="' . htmlspecialchars($i->name) . '"';
                }
                if ($from_db_checkboxes < 2) {
                    $html .= '<option value="' . ($fieldValue != '' ? $i->$fieldValue : $i->id) . '"' . $selected . $itemTitle . '>' .
                        stripslashes(htmlspecialchars($itemValue)) . '</option>';
                } else {
                    $html .= '
                    <div class="custom_checkbox toggle">
                        <label class="container"> 
                            <span class="label-text">' . stripslashes(htmlspecialchars($itemValue)) . '</span>
                            <input type="' . ($from_db_checkboxes == 2 ? 'checkbox' : 'radio') . '" name="from_db_view" value="' . ($fieldValue != '' ? $i->$fieldValue : $i->id) .
                        ($selected == ' selected="selected"' ? ' checked="checked"' : '') . ($mode == 'result' ? ' disabled="disabled"' : '') . '">
                            <span class="checkmark"></span>
                        </label>
                    </div>';
                }
            }
        }
        $html .= $containerEnd . ($this->auth->isAdmin() && $from_db_checkboxes < 2 ?
                '<div class="button icon button_registry" title="Добавить элемент справочника" data-reg="' . $f['from_db'] . '">
                                            <span class="material-icons">folder</span></div>
                <div class="button icon button_registry_edit" data-reg="' . $f['from_db'] . '" title="Редактировать элемент справочника">
                                            <span class="material-icons">edit</span></div>' : '') . '</div>';
        return $html;
    }

    public function renderRadio(?array $f, ?array $editData, string $mode): string
    {
        $items = [];
        $html = '';
        $default_value = strlen($editData[$f['field_name']]) > 0 ? $editData[$f['field_name']] : $f['default_value'];

        if (strlen(trim($f['radio_values'])) > 0) {
            $items = json_decode($f['radio_values'], true);
        }
        $el_data_class = ' vertical';
        $custom_checkbox_class = ' toggle';
        $item_class = ' w_50';
        if ($f['view_mode'] == 'horizontal') {
            $el_data_class = ' horizontal';
            $custom_checkbox_class = '';
        }
        if (strlen($f['width']) > 0) {
            $item_class = ' w_' . $f['width'];
        }
        $html .=
            '<div class="item' . $item_class . ' ' . $f['class'] . ($f['required'] == '1' ? ' required' : '') . '">
            <div class="el_data' . $el_data_class . '">
                <label>' . stripslashes(htmlspecialchars($items[0]['title'])) . '</label>';
        for ($r = 1; $r < count($items); $r++) {
            $html .= '<div class="custom_checkbox' . $custom_checkbox_class . '">
                    <label class="container"> 
                        <span class="label-text">' . stripslashes(htmlspecialchars($items[$r]['label'])) . '</span>
                        <input type="radio" class="for_self" name="' . $f['field_name'] . '" value="' . $items[$r]['value'] . '"'
                . ($mode == 'result' ? ' disabled="disabled"' : '')
                . ($default_value == $items[$r]['value'] ? ' checked="checked"' : '') . '>
                        <span class="checkmark radio"></span>
                    </label>
                </div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function renderCheckbox(array $f, array $editData, string $mode): string
    {
        $items = [];
        $html = '';

        $values = json_decode($f['checkbox_values'], true)[0];
        $default_value = strlen($editData[$f['field_name']]) > 0 ? $editData[$f['field_name']] : $f['default_value'];

        if (strlen(trim($f['checkbox_values'])) > 0) {
            $items = json_decode($f['checkbox_values'], true);
        }
        $html .=
            '<div class="item w_50">
                <div class="el_data">
                    <!--label style="margin-left: 30px" class="is_claimLabel" for="' . $f['field_name'] . $f['id'] . '">' .
            stripslashes(htmlspecialchars($f['label'])) . '</label-->
                    <div class="custom_checkbox">
                        <label class="container">
                            <span class="label-text">' . stripslashes(htmlspecialchars($f['label'])) . '</span>
                            <input type="checkbox" name="' . $f['field_name'] . '" id="' .
            $f['field_name'] . $f['id'] . '" class="is_claim" tabindex="-1" value="' . $values['value'] . '"' .
            ($mode == 'result' ? ' disabled="disabled"' : '') .
            ($default_value == $values['value'] ? ' checked="checked"' : '') . '>
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
            </div>';
        return $html;
    }

    public function renderQuarter(array $f, array $editData, string $mode = '', $required = false): string
    {
        $field_name_ws = str_replace('[]', '', $f['field_name']);
        $objId = str_replace('[]', '', $f['field_name']) . $f['id'] . (strlen($f['field_number']) > 0 ? '_' . $f['field_number'] : '');
        $default_value = strlen($editData[$field_name_ws]) > 0 ? $editData[$field_name_ws] : $f['default_value'];
        $default_value_hidden = strlen($editData[$field_name_ws . '_hidden']) > 0 ? $editData[$field_name_ws . '_hidden'] : $f['default_value_hidden'];
        $defHiddenArr = strlen($default_value_hidden) > 0 ? json_decode($default_value_hidden, true) : [];
        $requiredStr = $f['required'] == '1' ? ' required' : '';
        return '
        <div class="item w_50' . $requiredStr . '">
            <div class="el_data">
                <label>' . $f['label'] . '</label>
                <input class="el_input quarter_select" type="text" readonly="readonly" name="' . $f['field_name'] . '" 
                id="' . $objId . '" value="' . stripslashes(htmlspecialchars($default_value)) . '"' . $requiredStr . '>
                <div class="quarterWrapper">
                    <ul>
                        <li class="ui label quarter' . ($default_value == 'I квартал' ? ' selected' : '') . '"><b data-value="quarter01" title="Первый квартал">I</b>
                            <span class="ui label' . (in_array('01', $defHiddenArr) ? ' selected' : '') . '" data-value="month01" title="Январь">01</span>
                            <span class="ui label' . (in_array('02', $defHiddenArr) ? ' selected' : '') . '" data-value="month02" title="Февраль">02</span>
                            <span class="ui label' . (in_array('03', $defHiddenArr) ? ' selected' : '') . '" data-value="month03" title="Март">03</span>
                        </li>
                        <li class="ui label quarter' . ($default_value == 'II квартал' ? ' selected' : '') . '"><b data-value="quarter2" title="Второй квартал">II</b>
                            <span class="ui label' . (in_array('04', $defHiddenArr) ? ' selected' : '') . '" data-value="month04" title="Апрель">04</span>
                            <span class="ui label' . (in_array('05', $defHiddenArr) ? ' selected' : '') . '" data-value="month05" title="Май">05</span>
                            <span class="ui label' . (in_array('06', $defHiddenArr) ? ' selected' : '') . '" data-value="month06" title="Июнь">06</span>
                        </li>
                        <li class="ui label quarter' . ($default_value == 'III квартал' ? ' selected' : '') . '"><b data-value="quarter3" title="Третий квартал">III</b>
                            <span class="ui label' . (in_array('07', $defHiddenArr) ? ' selected' : '') . '" data-value="month07" title="Июль">07</span>
                            <span class="ui label' . (in_array('08', $defHiddenArr) ? ' selected' : '') . '" data-value="month08" title="Август">08</span>
                            <span class="ui label' . (in_array('09', $defHiddenArr) ? ' selected' : '') . '" data-value="month09" title="Сентябрь">09</span>
                        </li>
                        <li class="ui label quarter' . ($default_value == 'IV квартал' ? ' selected' : '') . '"><b data-value="quarter4" title="Четвертый квартал">IV</b>
                            <span class="ui label' . (in_array('10', $defHiddenArr) ? ' selected' : '') . '" data-value="month10" title="Октябрь">10</span>
                            <span class="ui label' . (in_array('11', $defHiddenArr) ? ' selected' : '') . '" data-value="month11" title="Ноябрь">11</span>
                            <span class="ui label' . (in_array('12', $defHiddenArr) ? ' selected' : '') . '" data-value="month12" title="Декабрь">12</span>
                        </li>
                    </ul>
                </div>
                <input type="hidden" name="' . str_replace('[]', '', $f['field_name']) . '_hidden[]" 
                id="' . $objId . '_hidden" value=\'' . $default_value_hidden . '\'>
            </div>
        </div>
        <script>quarter.bindQuarter("#' . $objId . '");</script>';
    }

    public function renderAddInstitution(array $f, array $editData, string $mode): string
    {
        $html = '<a href="#" id="clear_institutions"><span class="material-icons">delete</span> Удалить все учреждения</a>';
        $default_value = strlen($editData[$f['field_name']]) > 0 ? json_decode($editData[$f['field_name']]) : $f['default_value'];

        if ($editData[$f['field_name']] != 'null' && strlen($editData[$f['field_name']]) > 0) {
            $ed = json_decode($editData[$f['field_name']]);
            $insNumber = 1;
            foreach ($ed as $d) {
                $d = (array)$d;
                $html .= '
                <div class="group institutions question">
                <h5 class="item w_100 question_number">Объект контроля №' . $insNumber . '</h5>';
                //if($insNumber > 1) {
                $html .= '<span class="material-icons drag_handler" title="Переместить">drag_handle</span>' .
                    '<div class="button icon clear" title="Удалить"><span class="material-icons">close</span></div>';
                //}
                /*$t = [
                    'type' => 'list_fromdb',
                    'field_name' => 'check_types[]',
                    'from_db' => 36,
                    'from_db_text' => 13,
                    'label' => 'Тип проверки'
                ];
                $html .= $this->renderListFromDB($t, $d, $mode);
                $i = [
                    'type' => 'select',
                    'field_name' => 'institutions[]',
                    'label' => 'Учреждение'
                ];
                $d['value_selected'] = $d['institutions'];
                $html .= $this->renderSelect($i, $d, $mode);*/
                $t = [
                    'type' => 'list_fromdb',
                    'field_name' => 'institutions[]',
                    'from_db' => 34,
                    'from_db_text' => 13,
                    'label' => 'Учреждение'
                ];
                $html .= $this->renderListFromDB($t, $d, $mode);
                $i = [
                    'type' => 'select',
                    'field_name' => 'units[]',
                    'label' => 'Юр. адрес'
                ];
                $html .= $this->renderSelect($i, $d, $mode);
                $p = ['field_name' => 'periods[]'];
                $p['id'] = $f['id'];
                $p['label'] = 'Срок проведения проверки';
                $p['field_number'] = $insNumber;
                $html .= $this->renderQuarter($p, $d, $mode);
                /*$i = [
                    'type' => 'list_fromdb',
                    'field_name' => 'inspections[]',
                    'from_db' => 39,
                    'from_db_text' => 13,
                    'label' => 'Предмет проверки'
                ];
                $html .= $this->renderListFromDB($i, $d, $mode);*/
                $t = [
                    'type' => 'date',
                    'field_name' => 'check_periods[]',
                    'label' => 'Проверяемый период'
                ];
                $html .= $this->renderTextInput($t, $d, $mode);
                $html .= '</div>';
                $insNumber++;
            }
            $html .= '<script>$(document).ready(function (){$("select[name=\'check_types[]\']").trigger("change")})</script>
            <div class="item w_100"><button class="button icon text new_institution"><span class="material-icons">add</span>Еще учреждение</button></div>';
        } else {
            $html .= '
            <div class="group institutions question">
            <h5 class="item w_100 question_number">Объект контроля №1</h5>';
            /*$t = [
                'type' => 'list_fromdb',
                'field_name' => 'check_types[]',
                'from_db' => 36,
                'from_db_text' => 13,
                'label' => 'Тип проверки'
            ];
            $html .= $this->renderListFromDB($t, $editData, $mode);
            $i = [
                'type' => 'select',
                'field_name' => 'institutions[]',
                'label' => 'Учреждение'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);*/
            $t = [
                'type' => 'list_fromdb',
                'field_name' => 'institutions[]',
                'from_db' => 34,
                'from_db_text' => 13,
                'label' => 'Учреждение'
            ];
            $html .= $this->renderListFromDB($t, $editData, $mode);
            $i = [
                'type' => 'select',
                'field_name' => 'units[]',
                'label' => 'Юр. адрес'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);
            $p = ['field_name' => 'periods[]'];
            $p['id'] = $f['id'];
            $p['label'] = 'Срок проведения проверки';
            $html .= $this->renderQuarter($p, $editData, $mode);
            /*$i = [
                'type' => 'list_fromdb',
                'field_name' => 'inspections[]',
                'from_db' => 39,
                'from_db_text' => 13,
                'label' => 'Предмет проверки'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);*/
            $t = [
                'type' => 'date',
                'field_name' => 'check_periods[]',
                'label' => 'Проверяемый период'
            ];
            $html .= $this->renderTextInput($t, $editData, $mode);
            $html .= '
            <script>$("select[name=\'check_types[]\']").trigger("change")</script>
            </div>
            <div class="item w_100"><button class="button icon text new_institution"><span class="material-icons">add</span>Еще учреждение</button></div>';
        }

        return $html;

    }

    public function renderAddObject(array $f, array $editData, string $mode): string
    {
        $html = '';
        $default_value = strlen($editData[$f['field_name']]) > 0 ? json_decode($editData[$f['field_name']]) : $f['default_value'];

        if ($editData[$f['field_name']] != 'null' && strlen($editData[$f['field_name']]) > 0) {
            $ed = json_decode($editData[$f['field_name']]);
            $insNumber = 1;
            foreach ($ed as $d) {
                $d = (array)$d;
                $html .= '
                <div class="group institutions question">
                <h5 class="item w_100 question_number">Учреждение №' . $insNumber . '</h5>
                <div class="button icon clear"><span class="material-icons">close</span></div>';
                $t = [
                    'type' => 'list_fromdb',
                    'field_name' => 'check_types[]',
                    'from_db' => 36,
                    'from_db_text' => 13,
                    'label' => 'Тип проверки'
                ];
                $html .= $this->renderListFromDB($t, $d, $mode);
                $i = [
                    'type' => 'select',
                    'field_name' => 'institutions[]',
                    'label' => 'Учреждение'
                ];
                $d['value_selected'] = $d['institutions'];
                $html .= $this->renderSelect($i, $d, $mode);
                $i = [
                    'type' => 'select',
                    'field_name' => 'units[]',
                    'label' => 'Фдрес'
                ];
                $html .= $this->renderSelect($i, $d, $mode);
                $p = ['field_name' => 'periods[]'];
                $p['id'] = $f['id'];
                $p['label'] = 'Период проверки';
                $p['field_number'] = $insNumber;
                $html .= $this->renderQuarter($p, $d, $mode);
                $i = [
                    'type' => 'list_fromdb',
                    'field_name' => 'inspections[]',
                    'from_db' => 39,
                    'from_db_text' => 13,
                    'label' => 'Предмет проверки'
                ];
                $html .= $this->renderListFromDB($i, $d, $mode);
                $t = [
                    'type' => 'date',
                    'field_name' => 'check_periods[]',
                    'label' => 'Проверяемый период'
                ];
                $html .= $this->renderTextInput($t, $d, $mode);
                $html .= '</div>';
                $insNumber++;
            }
            $html .= '<script>$(document).ready(function (){$("select[name=\'check_types[]\']").trigger("change")})</script>
            <div class="item w_100"><button class="button icon text new_institution"><span class="material-icons">add</span>Еще учреждение</button></div>';
        } else {
            $html .= '
            <div class="group institutions question">
            <h5 class="item w_100 question_number">Учреждение №1</h5>';
            $t = [
                'type' => 'list_fromdb',
                'field_name' => 'check_types[]',
                'from_db' => 36,
                'from_db_text' => 13,
                'label' => 'Тип проверки'
            ];
            $html .= $this->renderListFromDB($t, $editData, $mode);
            $i = [
                'type' => 'select',
                'field_name' => 'institutions[]',
                'label' => 'Учреждение'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);
            $i = [
                'type' => 'select',
                'field_name' => 'units[]',
                'label' => 'Адрес'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);
            $p = ['field_name' => 'periods[]'];
            $p['id'] = $f['id'];
            $p['label'] = 'Период проверки';
            $html .= $this->renderQuarter($p, $editData, $mode);
            $i = [
                'type' => 'list_fromdb',
                'field_name' => 'inspections[]',
                'from_db' => 39,
                'from_db_text' => 13,
                'label' => 'Предмет проверки'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);
            $t = [
                'type' => 'date',
                'field_name' => 'check_periods[]',
                'label' => 'Проверяемый период'
            ];
            $html .= $this->renderTextInput($t, $editData, $mode);
            $html .= '
            <script>$("select[name=\'check_types[]\']").trigger("change")</script>
            </div>
            <div class="item w_100"><button class="button icon text new_institution"><span class="material-icons">add</span>Еще учреждение</button></div>';
        }

        return $html;

    }

    public function renderAddSignatory(array $f, array $editData, string $mode): string
    {
        $default_value = strlen($editData[$f['field_name']]) > 0 ? $editData[$f['field_name']] : $f['default_value'];

        $t = [
            'type' => 'list_fromdb_multi',
            'field_name' => 'signators',
            'from_db' => 40,
            'from_db_text' => 13,
            'label' => 'Подписанты',
            'subQuery' => ' AND institution = 1'
        ];
        return $this->renderListFromDB($t, $editData, $mode, ['surname', 'name', 'middle_name']);
    }

    public function renderAddAligner(array $f, array $editData, string $mode): string
    {
        $default_value = strlen($editData[$f['field_name']]) > 0 ? $editData[$f['field_name']] : $f['default_value'];

        $t = [
            'type' => 'list_fromdb_multi',
            'field_name' => 'coordination',
            'from_db' => 40,
            'from_db_text' => 13,
            'label' => 'Согласующие',
            'subQuery' => ' AND institution = 1'
        ];
        return $this->renderListFromDB($t, $editData, $mode, ['surname', 'name', 'middle_name']);
    }

    //Метод исправления json, приходящего с фронта
    public function fixJsonArray($ajax): array
    {
        $result = [];
        if (is_array($ajax)) {
            foreach ($ajax as $item) {
                if (is_string($item)) {
                    // Если это строка, пытаемся декодировать как JSON
                    $decoded = json_decode($item, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $result[] = $decoded;
                    } else {
                        // Если не JSON, оставляем как есть
                        $result[] = $item;
                    }
                } else {
                    // Если уже массив, оставляем как есть
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

    //Метод исправления json, записанного, как строка
    public function fixJsonString(string $jsonString): array
    {
        $jsonString = stripslashes($jsonString);

        $result = [];
        $currentPos = 0;
        $length = strlen($jsonString);

        while ($currentPos < $length) {
            // Пропускаем разделители между JSON-объектами
            while ($currentPos < $length &&
                in_array($jsonString[$currentPos], [',', '"', ' ', "\n", "\r", "\t"])) {
                $currentPos++;
            }

            if ($currentPos >= $length) {
                break;
            }

            // Ищем начало JSON
            $firstChar = $jsonString[$currentPos];
            if ($firstChar !== '[' && $firstChar !== '{') {
                // Пропускаем невалидные символы
                $currentPos++;
                continue;
            }

            $startChar = $firstChar;
            $endChar = ($startChar === '[') ? ']' : '}';

            $openCount = 0;
            $inString = false;
            $escapeNext = false;
            $endPos = -1;

            for ($i = $currentPos; $i < $length; $i++) {
                $char = $jsonString[$i];

                if ($escapeNext) {
                    $escapeNext = false;
                    continue;
                }

                if ($char === '\\') {
                    $escapeNext = true;
                    continue;
                }

                if ($char === '"') {
                    $inString = !$inString;
                    continue;
                }

                if (!$inString) {
                    if ($char === $startChar) {
                        $openCount++;
                    } elseif ($char === $endChar) {
                        $openCount--;
                        if ($openCount === 0) {
                            $endPos = $i;
                            break;
                        }
                    }
                }
            }

            if ($endPos !== -1) {
                $jsonPart = substr($jsonString, $currentPos, $endPos - $currentPos + 1);
                $decoded = json_decode($jsonPart, true);

                if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                    $result[] = $decoded;
                } else {
                    // Пробуем очистить от лишних символов
                    $jsonPart = preg_replace('/[^\x20-\x7E]/', '', $jsonPart);
                    $decoded = json_decode($jsonPart, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $result[] = $decoded;
                    }
                }

                $currentPos = $endPos + 1;
            } else {
                break;
            }
        }

        return $result;
    }

    public function renderAddAgreement(?array $f, ?array $editData, string $mode): string
    {
        $html = '';
        $urgent_types = [
            0 => '',
            1 => 'Обычный',
            2 => 'Срочный',
            3 => 'Незамедлительно'
        ];
        $maxSignersCount = intval($editData['oneSignOnly']) == 1 ? 1 : 2;
        $default_value = strlen($editData[$f['field_name']]) > 0 ? json_decode($editData[$f['field_name']]) : $f['default_value'];
        $users = $this->db->getRegistry('users', '', [], ['surname', 'name', 'middle_name',
                'institution', 'ministries', 'division', 'position']
        );
        $ins = $this->db->getRegistry('institutions', '', [], ['short']);
        $mins = $this->db->getRegistry('ministries');
        $units = $this->db->getRegistry('units');

        $userArr = [];
        foreach ($users['array'] as $id => $u) {
            $itemTitle = htmlspecialchars($ins['result'][$u[3]]->short .
                (intval($u[4]) > 0 ? "\n" . $mins['array'][$u[4]] : '') .
                (intval($u[5]) > 0 ? "\n" . $units['array'][$u[5]] : '') .
                (strlen($u[6]) > 0 ? "\n" . $u[6] : '')
            );
            $userArr[$id] = ['id' => $id, 'fio' => $u[0] . ' ' . $u[1] . ' ' . $u[2], 'title' => $itemTitle];
        }
        //print_r($editData[$f['field_name']]);
        $editArr = json_decode($editData[$f['field_name']]);
        if ($editData[$f['field_name']] != 'null' && is_array($editArr) && count($editArr) == 1) {
            array_unshift($editArr, '[{"stage":"1","list_type":"1","urgent":"1"}]');//print_r($editArr);
            $editData[$f['field_name']] = json_encode($editArr);//print_r($editData[$f['field_name']]);
            $html = $this->renderAddAgreement($f, $editData, $mode);

        } elseif ($editData[$f['field_name']] != 'null' && is_array($editArr) && count($editArr) > 1) {
            $ed = json_decode($editData[$f['field_name']]); //print_r($ed);
            $insNumber = 1;
            $insCount = 0;
            foreach ($ed as $d) {

                $d = (array)$d;
                $editData = $d[0];  //print_r($editData);
                $jsonData = $d;
                $is_signers = $editData->stage == '';//$insNumber == count($ed);

                $html .= '
                <div class="group sections question' . ($is_signers ? ' signers' : '') . '">
                <h5 class="item w_100 section_number">' . ($is_signers ? 'Подписанты' : 'Этап №' . $insNumber) . '</h5>' .
                    '<input class="el_input" type="hidden" name="stages[]" min="1" step="1" value="' . $editData->stage . '">';

                if (!$is_signers) {
                    $html .= '<div class="button icon clear"><span class="material-icons">close</span></div>';
                }
                $i = [
                    'type' => 'radio',
                    'field_name' => 'section_types[' . ($insNumber - 1) . ']',
                    'view_mode' => 'horizontal',
                    'class' => 'thin',
                    'width' => '66',
                    'default_value' => ($is_signers ? '1' : '2'),
                    'radio_values' => json_encode([
                            ['title' => 'Тип согласования:'],
                            ['label' => 'Последовательное', 'value' => '1'],
                            ['label' => 'Параллельное', 'value' => '2']
                        ]
                    )
                ];
                $d['section_types[' . ($insNumber - 1) . ']'] = $editData->list_type;
                $html .= $this->renderRadio($i, $d, $mode);
                $i = [
                    'type' => 'radio',
                    'field_name' => 'urgent[' . ($insNumber - 1) . ']',
                    'view_mode' => 'horizontal',
                    'width' => '66',
                    'class' => 'thin',
                    'default_value' => '1',
                    'radio_values' => json_encode([
                            ['title' => 'Срок согласования:'],
                            ['label' => 'Обычный', 'value' => '1'],
                            ['label' => 'Срочный', 'value' => '2'],
                            ['label' => 'Незамедлительно', 'value' => '3']
                        ]
                    )
                ];
                $d['urgent[' . ($insNumber - 1) . ']'] = $editData->urgent;
                $html .= $this->renderRadio($i, $d, $mode);

                $html .= '<div class="item w_100">' .
                    '<ol class="agreement_list">';

                if ($d) {
                    $liCount = 0;
                    $maxLi = $is_signers ? $maxSignersCount : 10000;
                    foreach ($d as $li) {

                        if (intval($li->id) > 0 && $liCount < $maxLi) {
                            $user_fio = '<ruby title="' . nl2br($userArr[$li->id]['title']) . '">' . $userArr[$li->id]['fio'] . /*' <div>' . $users['array'][$li->id][3] . '</div> ' .*/
                                /*($li->type == '1' ? '<span>Согласование</span>' : '<span>Подписание</span>') .*/
                                '</ruby><select name="urgent' . $li->id . '" title="Срок согласования" class="user_urgent viewmode-select">';
                            for ($s = 1; $s < count($urgent_types); $s++) {
                                $sel = intval($li->urgent) == $s ? ' selected="selected"' : '';
                                $user_fio .= '<option value="' . $s . '"' . $sel . '>' . $urgent_types[$s] . '</option>';
                            }
                            $userList = '<option></option>';
                            foreach ($userArr as $u) {
                                $sel = $li->vrio == $u['id'] ? ' selected' : '';
                                if (strlen(trim($u['fio'])) > 0) {
                                    $userList .= '<option title="' . $u['title'] . '" value="' . $u['id'] . '"' . $sel . '>' . $u['fio'] . '</option>' . "\n";
                                }
                            }
                            $user_fio .= '</select>' .
                                "<select name='vrio" . $li->id . "' title='Отсутствующий сотрудник' class='viewmode-select vrio'>" .
                                $userList . '</select>' .
                                ($is_signers ? "<select name='role" . $li->id . "' title='Роль' class='viewmode-select role'>" .
                                    '<option' . (!isset($li->role) ? ' selected' : '') . '></option>' .
                                    "<option value='0'" . (isset($li->role) && intval($li->role) == 0 ? ' selected' : '') . '>Утверждает</option>' .
                                    "<option value='1'" . (intval($li->role) == 1 ? ' selected' : '') . '>Подписывает</option></select>' : '') .
                                '<span class="material-icons drag_handler" title="Переместить">drag_handle</span>' .
                                '<span class="material-icons clear" title="Удалить">close</span></li>';
                            $html .= '<li data-id="' . $li->id . '" data-type="' . $li->type . '">' . $user_fio . '</li>';

                            $liCount++;
                        }
                    }
                }
                $html .= '</ol>' .
                    '<input type="hidden" name="agreementlist[]" value=\'' . json_encode($jsonData) . '\'> </div>';


                /*$i = [
                    'type' => 'list_fromdb',
                    'from_db' => 34,
                    'from_db_value' => 0,
                    'from_db_text' => 13,
                    'default_value' => 1,
                    'field_name' => 'institutions[]',
                    'label' => 'Учреждение'
                ];
                $html .= $this->renderListFromDB($i, $d, $mode);

                $i = [
                    'type' => 'list_fromdb',
                    'from_db' => 68,
                    'from_db_value' => 0,
                    'from_db_text' => 13,
                    'field_name' => 'ministries[]',
                    'label' => 'Управление'
                ];
                $html .= $this->renderListFromDB($i, $d, $mode);

                $i = [
                    'type' => 'select',
                    'field_name' => 'units[]',
                    'label' => 'Отдел'
                ];
                $html .= $this->renderSelect($i, $editData, $mode);

                $i = [
                    'type' => 'select',
                    'field_name' => 'users[]',
                    'label' => 'Сотрудник'
                ];
                $html .= $this->renderSelect($i, $d, $mode);*/
                $i = [
                    'type' => 'list_fromdb',
                    'subQuery' => ' ORDER BY surname',
                    'from_db' => 40,
                    'from_db_value' => 0,
                    'from_db_text' => 13,
                    'field_name' => 'users[]',
                    'label' => 'Поиск сотрудника'
                ];
                $html .= $this->renderListFromDB($i, $d, $mode);

                $html .= '<div class="item w_50">' .
                    '<button class="button icon text new_signer"><span class="material-icons">add</span>Добавить</button>';
                $i = [
                    'type' => 'radio',
                    'field_name' => 'approve_types[]',
                    'view_mode' => 'horizontal',
                    'class' => 'new_signer thin',
                    'width' => '100',
                    'radio_values' => json_encode([
                            ['title' => 'Срок:'],
                            ['label' => 'Обычный', 'value' => '1'],
                            ['label' => 'Срочный', 'value' => '2'],
                            ['label' => 'Незамедлительно', 'value' => '3']
                        ]
                    )
                ];
                $d['value_selected'] = $editData->approve_types;
                $html .= $this->renderRadio($i, (array)$editData, $mode);
                $html .= '<span class="add_agreement_message"></span>
                        </div>
                    </div>';
                if (!$is_signers) {
                    $insNumber++;
                    if ($insNumber == count($ed)) {
                        $html .= '<div class="item w_100 thin"><button class="button icon text new_section">' .
                            '<span class="material-icons">add</span>Еще этап</button></div>';
                    }
                    $html .= '<script>sections_counter = ' . $insNumber . '</script>';
                } else {
                    //$html .= '</div>';
                }
            }
        } else {
            $html .= '
            <div class="group sections question">
            <h5 class="item w_100 section_number">Этап №1</h5>' .
                '<input class="el_input" type="hidden" name="stages[]" min="1" step="1" value="1">';

            $i = [
                'type' => 'radio',
                'field_name' => 'section_types[0]',
                'view_mode' => 'horizontal',
                'class' => 'thin',
                'width' => '66',
                'default_value' => '2',
                'radio_values' => json_encode([
                        ['title' => 'Тип согласования'],
                        ['label' => 'Последовательное', 'value' => '1'],
                        ['label' => 'Параллельное', 'value' => '2']
                    ]
                )
            ];
            $html .= $this->renderRadio($i, $editData, $mode);

            $i = [
                'type' => 'radio',
                'field_name' => 'urgent[0]',
                'view_mode' => 'horizontal',
                'width' => '66',
                'class' => 'thin',
                'default_value' => '1',
                'radio_values' => json_encode([
                        ['title' => 'Срок согласования:'],
                        ['label' => 'Обычный', 'value' => '1'],
                        ['label' => 'Срочный', 'value' => '2'],
                        ['label' => 'Незамедлительно', 'value' => '3']
                    ]
                )
            ];
            $html .= $this->renderRadio($i, $editData, $mode);

            $html .= '<div class="item w_100"><ol class="agreement_list"></ol><input type="hidden" name="agreementlist[]"> </div>';

            /*$i = [
                'type' => 'list_fromdb',
                'from_db' => 34,
                'from_db_value' => 0,
                'from_db_text' => 13,
                'default_value' => 1,
                'field_name' => 'institutions[]',
                'label' => 'Учреждение'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);

            $i = [
                'type' => 'list_fromdb',
                'from_db' => 68,
                'from_db_value' => 0,
                'from_db_text' => 13,
                'field_name' => 'ministries[]',
                'label' => 'Управление'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);

            $i = [
                'type' => 'select',
                'field_name' => 'units[]',
                'label' => 'Отдел'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);

            $i = [
                'type' => 'select',
                'field_name' => 'users[]',
                'label' => 'Сотрудник'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);*/
            $i = [
                'type' => 'list_fromdb',
                'subQuery' => ' ORDER BY surname',
                'from_db' => 40,
                'from_db_value' => 0,
                'from_db_text' => 13,
                'field_name' => 'users[0]',
                'label' => 'Поиск сотрудника'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);


            $html .= '<div class="item w_50">' .
                '<button class="button icon text new_signer"><span class="material-icons">add</span>Добавить</button>';
            $i = [
                'type' => 'radio',
                'field_name' => 'approve_types[0]',
                'view_mode' => 'horizontal',
                'class' => 'new_signer thin',
                'width' => '50',
                'radio_values' => json_encode([
                        ['title' => 'Срок:'],
                        ['label' => 'Обычный', 'value' => '1'],
                        ['label' => 'Срочный', 'value' => '2'],
                        ['label' => 'Незамедлительно', 'value' => '3']
                    ]
                )
            ];
            $d['value_selected'] = $editData['approve_types'];
            $html .= $this->renderRadio($i, $editData, $mode);
            $html .= '<span class="add_agreement_message"></span>
                </div>
            </div>
            
            <div class="item w_100 thin"><button class="button icon text new_section">
            <span class="material-icons">add</span>Еще этап</button></div>';


            $html .= '
            <div class="group sections question signers">
            <h5 class="item w_100 section_number">Подписанты</h5>' .
                '<input class="el_input" type="hidden" name="stages[]" min="1" step="1">';

            $i = [
                'type' => 'radio',
                'field_name' => 'section_types[]',
                'view_mode' => 'horizontal',
                'class' => 'thin',
                'width' => '66',
                'default_value' => '1',
                'radio_values' => json_encode([
                        ['title' => 'Тип согласования'],
                        ['label' => 'Последовательное', 'value' => '1'],
                        ['label' => 'Параллельное', 'value' => '2']
                    ]
                )
            ];
            $html .= $this->renderRadio($i, $editData, $mode);

            $i = [
                'type' => 'radio',
                'field_name' => 'urgent[]',
                'view_mode' => 'horizontal',
                'width' => '66',
                'class' => 'thin',
                'default_value' => '1',
                'radio_values' => json_encode([
                        ['title' => 'Срок согласования:'],
                        ['label' => 'Обычный', 'value' => '1'],
                        ['label' => 'Срочный', 'value' => '2'],
                        ['label' => 'Незамедлительно', 'value' => '3']
                    ]
                )
            ];
            $html .= $this->renderRadio($i, $editData, $mode);

            $html .= '<div class="item w_100"><ol class="agreement_list"></ol><input type="hidden" name="agreementlist[]"> </div>';

            /*$i = [
                'type' => 'list_fromdb',
                'from_db' => 34,
                'from_db_value' => 0,
                'from_db_text' => 13,
                'default_value' => 1,
                'field_name' => 'institutions[]',
                'label' => 'Учреждение'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);

            $i = [
                'type' => 'list_fromdb',
                'from_db' => 68,
                'from_db_value' => 0,
                'from_db_text' => 13,
                'field_name' => 'ministries[]',
                'label' => 'Управление'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);

            $i = [
                'type' => 'select',
                'field_name' => 'units[]',
                'label' => 'Отдел'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);

            $i = [
                'type' => 'select',
                'field_name' => 'users[]',
                'label' => 'Сотрудник'
            ];
            $html .= $this->renderSelect($i, $editData, $mode);*/
            $i = [
                'type' => 'list_fromdb',
                'subQuery' => ' ORDER BY surname',
                'from_db' => 40,
                'from_db_value' => 0,
                'from_db_text' => 13,
                'field_name' => 'users[]',
                'label' => 'Поиск сотрудника'
            ];
            $html .= $this->renderListFromDB($i, $editData, $mode);


            $html .= '<div class="item w_50">' .
                '<button class="button icon text new_signer"><span class="material-icons">add</span>Добавить</button>';
            $i = [
                'type' => 'radio',
                'field_name' => 'approve_types[]',
                'view_mode' => 'horizontal',
                'class' => 'new_signer thin',
                'width' => '50',
                'radio_values' => json_encode([
                        ['title' => 'Срок:'],
                        ['label' => 'Обычный', 'value' => '1'],
                        ['label' => 'Срочный', 'value' => '2'],
                        ['label' => 'Незамедлительно', 'value' => '3']
                    ]
                )
            ];
            $d['value_selected'] = $editData['approve_types'];
            $html .= $this->renderRadio($i, $editData, $mode);
            $html .= '<span class="add_agreement_message"></span>
                </div>
            </div>';
        }

        return $html;

    }

    //Метод отрисовки листа согласования
    /*
    $itemArr,           // текущая секция
    $i,                 // индекс секции
    $users,             // данные пользователей
    $urgent_types,      // типы срочности
    $user_signs,        // подписи
    $reg,               // реестр
    0,                  // уровень (0 - первый)
    $agreementList      // ВЕСЬ МАССИВ ВСЕХ СЕКЦИЙ - ЭТО $allSections!
    */
    public function buildAgreementList($itemArr, $section, $users, $urgent_types, $user_signs, $reg, $level = 0, $allSections = null): string
    {
        $html = '';
        static $rowNumber = 1;
        $processedUsers = [];
        $level = intval($level);
        $section = intval($section);
        global $ins, $mins, $units;

        $itemArr = is_string($itemArr) ? json_decode($itemArr, true) : $itemArr;
        if (empty($itemArr) || !is_array($itemArr)) {
            return '<tr><td colspan="5">Нет данных согласования</td></tr>';
        }

        // Функция определения статуса согласующего
        $getApproverStatus = function ($approver) use ($user_signs, $section) {
            $result = $approver['result'] ?? null;

            if (!$result || !is_array($result)) {
                if (isset($user_signs[$approver['id']][$section])) {
                    $signType = intval($user_signs[$approver['id']][$section]['type']);
                    $signDate = $user_signs[$approver['id']][$section]['date'];
                    return ['status' => 'approved', 'result_id' => $signType, 'date' => $signDate];
                }
                return ['status' => 'pending', 'result_id' => 0];
            }

            $resultId = intval($result['id'] ?? 0);
            switch ($resultId) {
                case 1:
                case 2:
                case 3:
                    return ['status' => 'approved', 'result_id' => $resultId, 'date' => $result['date'] ?? ''];
                case 4:
                    return ['status' => 'redirected', 'result_id' => 4, 'date' => $result['date'] ?? ''];
                case 5:
                    return ['status' => 'rejected', 'result_id' => 5, 'date' => $result['date'] ?? ''];
                default:
                    return ['status' => 'pending', 'result_id' => 0];
            }
        };

        // Функция для проверки, завершено ли перенаправление
        $isRedirectCompleted = function ($redirectArr) use (&$isRedirectCompleted, $getApproverStatus) {
            if (!is_array($redirectArr)) return true;

            // Пропускаем заголовок секции (если есть)
            $startIdx = isset($redirectArr[0]['stage']) ? 1 : 0;

            for ($i = $startIdx; $i < count($redirectArr); $i++) {
                $approver = $redirectArr[$i];
                if (!isset($approver['id'])) continue;

                // ПРОВЕРЯЕМ ТОЛЬКО ПОЛЕ result, БЕЗ user_signs!
                $result = $approver['result'] ?? null;

                if (!$result || !is_array($result)) {
                    // Нет результата - ждём
                    return false;
                }

                $resultId = intval($result['id'] ?? 0);

                if ($resultId === 4) {
                    // Перенаправление - проверяем вложенное
                    if (isset($approver['redirect']) && is_array($approver['redirect'])) {
                        return $isRedirectCompleted($approver['redirect']);
                    }
                    return false;
                }

                if ($resultId === 5) {
                    // Отклонение - считаем завершённым
                    continue;
                }

                if (!in_array($resultId, [1, 2, 3])) {
                    // Неизвестный статус - ждём
                    return false;
                }

                // approved - продолжаем проверку
            }

            return true;
        };

        // Определяем начало списка сотрудников
        $startIndex = ($level == 0 && isset($itemArr[0]['stage'])) ? 1 : 0;

        // Получаем информацию об этапе
        $stageInfo = $startIndex == 1 ? $itemArr[0] : [];
        $listType = isset($stageInfo['list_type']) ? intval($stageInfo['list_type']) : 1;
        $stage = $stageInfo['stage'] ?? '';
        $sectionUrgent = isset($stageInfo['urgent']) ? $stageInfo['urgent'] : '0';

        // Определяем, является ли это секцией подписантов
        $isSignersSection = ($level == 0 && isset($itemArr[0]['stage']) && $itemArr[0]['stage'] === '');

        // ============ ПРОВЕРКА ЗАВЕРШЕНИЯ ПРЕДЫДУЩИХ ЭТАПОВ ============
        $allPreviousStagesCompleted = true;

        // Только для секции подписантов и если передан полный список секций
        if ($isSignersSection && is_array($allSections)) {
            $currentSectionIndex = $section;

            // Проверяем ВСЕ предыдущие секции
            for ($s = 0; $s < $currentSectionIndex; $s++) {
                if (!isset($allSections[$s]) || !is_array($allSections[$s])) {
                    continue;
                }

                $prevSection = $allSections[$s];
                $prevStartIndex = isset($prevSection[0]['stage']) ? 1 : 0;

                for ($p = $prevStartIndex; $p < count($prevSection); $p++) {
                    if (!isset($prevSection[$p]['id'])) continue;

                    $prevStatus = $getApproverStatus($prevSection[$p]);

                    if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                        $allPreviousStagesCompleted = false;
                        break 2;
                    }
                }
            }
        }

        // ============ ФИЛЬТРАЦИЯ ПОВТОРНЫХ ЗАПИСЕЙ ============
        $showRepeatEntry = [];
        if ($level == 0) {
            $seenUsers = [];

            for ($i = $startIndex; $i < count($itemArr); $i++) {
                if (!isset($itemArr[$i]['id'])) continue;

                $userId = $itemArr[$i]['id'];

                if (!isset($seenUsers[$userId])) {
                    $seenUsers[$userId] = [
                        'index' => $i,
                        'has_redirect' => isset($itemArr[$i]['redirect']) && is_array($itemArr[$i]['redirect'])
                    ];
                    $showRepeatEntry[$i] = true;
                } else {
                    $prevData = $seenUsers[$userId];

                    if ($prevData['has_redirect']) {
                        $prevIndex = $prevData['index'];
                        if (isset($itemArr[$prevIndex]['redirect'])) {
                            $redirectCompleted = $isRedirectCompleted($itemArr[$prevIndex]['redirect']);
                            $showRepeatEntry[$i] = $redirectCompleted;
                        } else {
                            $showRepeatEntry[$i] = true;
                        }
                    } else {
                        $showRepeatEntry[$i] = true;
                    }
                }
            }
        }

        // ============ ПРОВЕРКА НАЛИЧИЯ ОТКЛОНЕНИЯ ============
        $hasAnyRejection = false;
        $rejectionIndex = -1;

        for ($k = $startIndex; $k < count($itemArr); $k++) {
            if (!isset($itemArr[$k]['id'])) continue;
            $status = $getApproverStatus($itemArr[$k]);
            if ($status['status'] === 'rejected') {
                $hasAnyRejection = true;
                $rejectionIndex = $k;
                break;
            }
        }

        // Функция для формирования номера строки
        $getRowNumber = function ($userId, &$processedUsers, &$rowNumber) {
            if (!isset($processedUsers[$userId])) {
                // Первое появление сотрудника
                $processedUsers[$userId] = [
                    'main_number' => $rowNumber,
                    'sub_number' => 0,
                    'incremented' => false
                ];
                return (string)$rowNumber;
            } else {
                // Повторное появление (после перенаправления)
                $processedUsers[$userId]['sub_number']++;
                return $processedUsers[$userId]['main_number'] . '.' . $processedUsers[$userId]['sub_number'];
            }
        };

        // Листаем списки в секциях
        for ($i = $startIndex; $i < count($itemArr); $i++) {
            if (!isset($itemArr[$i]['id'])) continue;

            $item = $itemArr[$i];
            $userId = $item['id'];
            $isCurrentUser = ($_SESSION['user_id'] == $userId);

            // Пропускаем повторные записи, если перенаправление не завершено
            if ($level == 0 && isset($showRepeatEntry[$i]) && $showRepeatEntry[$i] === false) {
                continue;
            }

            $statusInfo = $getApproverStatus($item);
            $resultId = $statusInfo['result_id'];
            $resultDate = $statusInfo['date'] ?? ($item['result']['date'] ?? '');

            // Определяем срочность
            $urgent = $sectionUrgent;
            if (isset($item['urgent']) && $item['urgent'] !== '') {
                $urgent = $item['urgent'];
            }

            // Формируем номер строки
            $displayNumber = '';
            if ($level == 0) {
                $displayNumber = $getRowNumber($userId, $processedUsers, $rowNumber);
            }

            // Получаем ФИО
            $userInfo = isset($users['array'][$userId]) ? $users['array'][$userId] : ['', '', ''];
            $userFio = $userInfo[0] .
                (!empty($userInfo[1]) ? ' ' . mb_substr($userInfo[1], 0, 1) . '.' : '') .
                (!empty($userInfo[2]) ? ' ' . mb_substr($userInfo[2], 0, 1) . '.' : '');

            // Обработка ВРИО
            if (isset($item['vrio']) && intval($item['vrio']) > 0) {
                $vrioId = intval($item['vrio']);
                $vrioInfo = isset($users['array'][$vrioId]) ? $users['array'][$vrioId] : ['', '', ''];
                $userFio .= '<br><small>ВРИО ' . $vrioInfo[0] .
                    (!empty($vrioInfo[1]) ? ' ' . mb_substr($vrioInfo[1], 0, 1) . '.' : '') .
                    (!empty($vrioInfo[2]) ? ' ' . mb_substr($vrioInfo[2], 0, 1) . '.' : '') . '</small>';
            }

            // ============ ПРАВИЛО 5: Определяем, активна ли очередь текущего пользователя ============
            // Вычисляем заранее, чтобы использовать при формировании <tr> и иконки
            $isMyTurn = false;
            if ($isCurrentUser && $statusInfo['status'] === 'pending' && !$hasAnyRejection) {
                if ($isSignersSection) {
                    if ($allPreviousStagesCompleted) {
                        if ($listType == 1) {
                            $isMyTurn = true;
                            for ($j = $startIndex; $j < $i; $j++) {
                                if (!isset($itemArr[$j]['id'])) continue;
                                if ($getApproverStatus($itemArr[$j])['status'] !== 'approved') {
                                    $isMyTurn = false;
                                    break;
                                }
                            }
                        } else {
                            $isMyTurn = true; // параллельное
                        }
                    }
                } else {
                    if ($listType == 2) {
                        $isMyTurn = true; // параллельное согласование
                    } else {
                        $isMyTurn = true;
                        for ($j = $startIndex; $j < $i; $j++) {
                            if (!isset($itemArr[$j]['id'])) continue;
                            if (isset($itemArr[$j]['_is_redirector_repeat'])) continue;
                            $pst = $getApproverStatus($itemArr[$j])['status'];
                            if ($pst !== 'approved' && $pst !== 'redirected') {
                                $isMyTurn = false;
                                break;
                            }
                        }
                    }
                    // Если это повторная запись после перенаправления — ждём завершения redirect-цепи
                    $isAfterRedirectCheck = false;
                    if ($level == 0 && isset($seenUsers[$userId]) && $seenUsers[$userId]['has_redirect']) {
                        $prevIdx = $seenUsers[$userId]['index'];
                        if ($prevIdx < $i && isset($itemArr[$prevIdx]['redirect'])) {
                            $isAfterRedirectCheck = true;
                            if (!$isRedirectCompleted($itemArr[$prevIdx]['redirect'])) {
                                $isMyTurn = false;
                            }
                        }
                    }
                }
            }

            // Правило 5: строка текущего пользователя:
            //   - очередь ещё не пришла: серый фон (ожидание)
            //   - очередь пришла: нормальный фон + иконка голубая
            $trClass = '';
            if ($isCurrentUser && $statusInfo['status'] === 'pending') {
                $trClass = $isMyTurn ? ' class="my-turn-active"' : ' class="my-turn-waiting"';
            }

            // Формируем строку таблицы
            $html .= '<tr' . $trClass . '>';
            $html .= '<td>' . $displayNumber . '</td>';
            $padding = $level > 0 ? ' style="padding-left: ' . (20 * $level) . 'px"' : '';

            $userTitle = '';
            if (isset($users['result'][$userId])) {
                $institution = $users['result'][$userId]->institution ?? 0;
                $ministry = $users['result'][$userId]->ministries ?? 0;
                $division = $users['result'][$userId]->division ?? 0;
                $position = $users['result'][$userId]->position ?? '';

                $userTitle = ' title="' . htmlspecialchars(
                        ($ins['result'][$institution]->short ?? '') .
                        ($ministry > 0 ? '<br>' . ($mins['array'][$ministry] ?? '') : '') .
                        ($division > 0 ? '<br>' . ($units['array'][$division] ?? '') : '') .
                        (!empty($position) ? '<br>' . htmlspecialchars($position) : '')
                    ) . '"';
            }

            // ПРАВИЛО 5: иконка — голубая когда очередь пришла, чёрная когда ещё ждёт
            $userIcon = '';
            if ($isCurrentUser && $statusInfo['status'] === 'pending') {
                if ($isMyTurn) {
                    $userIcon = "<span class='material-icons' style='color:#086a9b;vertical-align:middle;font-size:16px;margin-right:3px'>account_circle</span>";
                } else {
                    $userIcon = "<span class='material-icons' style='color:#333;vertical-align:middle;font-size:16px;margin-right:3px'>account_circle</span>";
                }
            }

            $html .= '<td' . $padding . $userTitle . ' data-user-id="' . $userId . '">' . $userIcon . $userFio . '</td>';
            $html .= '<td>' . ($urgent_types[$urgent] ?? 'Обычная') . '</td>';
            $html .= '<td>';

            // ============ ЛОГИКА СТАТУСОВ ============
            if ($hasAnyRejection && $i > $rejectionIndex) {
                $html .= "<span style='color: #9e9e9e'>Согласование прервано</span>";
            } elseif ($statusInfo['status'] === 'rejected') {
                $html .= "<span style='color: var(--red)'>Отклонено<br>" . $resultDate . '</span>';
            } elseif ($statusInfo['status'] === 'redirected') {
                $html .= "<span style='color: #ff9800'>Перенаправлено<br>" . $resultDate . '</span>';
            } elseif ($statusInfo['status'] === 'approved') {
                if ($resultId == 1) {
                    $html .= "<span style='color: #086a9b'>Подписано с ЭП<br>" .
                        date('d.m.Y H:i', strtotime($resultDate)) . '</span>';
                } elseif ($resultId == 2) {
                    $html .= "<span style='color: #086a9b'>Согласовано с ЭП<br>" .
                        date('d.m.Y H:i', strtotime($resultDate)) . '</span>';
                } elseif ($resultId == 3) {
                    $html .= "<span style='color: #086a9b'>Согласовано<br>" . $resultDate . '</span>';
                }
            } else { // pending

                // ============ ВАЖНО: ПРОВЕРЯЕМ, ПОВТОРНАЯ ЛИ ЭТО ЗАПИСЬ ============
                $isAfterRedirect = false;
                if ($level == 0 && isset($seenUsers[$userId]) && $seenUsers[$userId]['has_redirect']) {
                    $prevIndex = $seenUsers[$userId]['index'];
                    if ($prevIndex < $i && isset($itemArr[$prevIndex]['redirect'])) {
                        $isAfterRedirect = true;
                    }
                }

                $actionType = isset($item['type']) ? intval($item['type']) : 1;

                if ($isCurrentUser) {
                    $canAct = false;
                    $redirectCompleted = false;
                    if ($isAfterRedirect) {
                        // Проверяем, завершено ли перенаправление
                        if (isset($seenUsers[$userId])) {
                            $prevIndex = $seenUsers[$userId]['index'];
                            if (isset($itemArr[$prevIndex]['redirect'])) {
                                $redirectCompleted = $isRedirectCompleted($itemArr[$prevIndex]['redirect']);
                            }
                        }
                    }

                    // ============ СЕКЦИЯ ПОДПИСАНТОВ ============
                    if ($isSignersSection) {
                        // КНОПКИ ПОЯВЛЯЮТСЯ ТОЛЬКО ЕСЛИ ВСЕ ПРЕДЫДУЩИЕ ЭТАПЫ ЗАВЕРШЕНЫ
                        if ($allPreviousStagesCompleted && !$hasAnyRejection) {
                            if ($listType == 1) {
                                // ПОСЛЕДОВАТЕЛЬНОЕ - проверяем предыдущих подписантов
                                $canSign = true;
                                if ($i > $startIndex) {
                                    for ($j = $startIndex; $j < $i; $j++) {
                                        if (isset($itemArr[$j]['id'])) {
                                            $prevStatus = $getApproverStatus($itemArr[$j]);
                                            if ($prevStatus['status'] !== 'approved') {
                                                $canSign = false;
                                                break;
                                            }
                                            if ($isAfterRedirect && !$redirectCompleted) {
                                                $canSign = false;
                                                break;
                                            }
                                        }
                                    }
                                }

                                if ($canSign) {
                                    $canAct = true;
                                    $html .= "<div class='actions' data-section='" . $section . "'>";
                                    $html .= "<button class='button icon text green setSign'>" .
                                        "<span class='material-icons'>verified</span>Подписать</button>";
                                    $html .= "<button class='button icon text red setReject'>" .
                                        "<span class='material-icons'>cancel</span>Отклонить</button>";
                                    $html .= '<div class="redirect-field" style="margin-top: 10px;">';
                                    $f = [
                                        'type' => 'list_fromdb_multi',
                                        'field_name' => 'redirect',
                                        'from_db' => 40,
                                        'from_db_value' => 0,
                                        'from_db_text' => 13,
                                        'width' => 50,
                                        'subQuery' => ' AND id <> ' . $_SESSION['user_id'],
                                        'label' => 'Перенаправить на:'
                                    ];
                                    $html .= $reg->renderListFromDB($f, [], '');
                                    $html .= '</div></div><div class="action_result" id="agResult' . $section . '"></div>';
                                } else {
                                    $html .= "<span style='color: #9e9e9e'>Ожидание предыдущего подписанта</span>";
                                }
                            } else {
                                // ПАРАЛЛЕЛЬНОЕ - все видят кнопки сразу
                                $canAct = true;
                                $html .= "<div class='actions' data-section='" . $section . "'>";
                                $html .= "<button class='button icon text green setSign'>" .
                                    "<span class='material-icons'>verified</span>Подписать</button>";
                                $html .= "<button class='button icon text red setReject'>" .
                                    "<span class='material-icons'>cancel</span>Отклонить</button>";
                                $html .= '<div class="redirect-field" style="margin-top: 10px;">';
                                $f = [
                                    'type' => 'list_fromdb_multi',
                                    'field_name' => 'redirect',
                                    'from_db' => 40,
                                    'from_db_value' => 0,
                                    'from_db_text' => 13,
                                    'width' => 50,
                                    'subQuery' => ' AND id <> ' . $_SESSION['user_id'],
                                    'label' => 'Перенаправить на:'
                                ];
                                $html .= $reg->renderListFromDB($f, [], '');
                                $html .= '</div></div><div class="action_result" id="agResult' . $section . '"></div>';
                            }
                        } else {
                            if (!$allPreviousStagesCompleted) {
                                $html .= "<span style='color: #9e9e9e'>Ожидание завершения предыдущих этапов</span>";
                            } else {
                                $html .= "<span style='color: #9e9e9e'>Ожидает</span>";
                            }
                        }
                    } else {
                        // ============ СЕКЦИИ СОГЛАСОВАНИЯ ============
                        if ($listType == 1 && $level == 0) {
                            $prevCanAct = true;
                            if ($i > $startIndex) {
                                for ($j = $startIndex; $j < $i; $j++) {
                                    if (isset($itemArr[$j]['id'])) {
                                        $prevStatus = $getApproverStatus($itemArr[$j]);
                                        if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                                            $prevCanAct = false;
                                            break;
                                        }
                                    }
                                }
                            }

                            if ($prevCanAct) {
                                $canAct = true;
                            } else {
                                $html .= "<span style='color: #9e9e9e'>Ожидание предыдущего согласующего</span>";
                            }
                        } else {
                            $canAct = true;
                        }

                        if ($isAfterRedirect && !$redirectCompleted) {
                            $canAct = false;
                            $html .= "<span style='color: #9e9e9e'>Ожидание предыдущего согласующего</span>";
                            break;
                        }

                        if ($canAct && !$hasAnyRejection) {
                            $html .= "<div class='actions' data-section='" . $section . "'>";

                            if ($actionType == 1) {
                                $html .= "<button class='button icon text green setSign'>" .
                                    "<span class='material-icons'>verified</span>Подписать</button>";
                            } else {
                                $html .= "<button class='button icon text blue setAgree'>" .
                                    "<span class='material-icons'>task_alt</span>Согласовать</button>";
                                $html .= "<button class='button icon text green setAgreeSign'>" .
                                    "<span class='material-icons'>verified</span>Согласовать с ЭП</button>";
                            }

                            $html .= "<button class='button icon text red setReject'>" .
                                "<span class='material-icons'>cancel</span>Отклонить</button>";

                            $html .= '<div class="redirect-field" style="margin-top: 10px;">';
                            $f = [
                                'type' => 'list_fromdb_multi',
                                'field_name' => 'redirect',
                                'from_db' => 40,
                                'from_db_value' => 0,
                                'from_db_text' => 13,
                                'width' => 50,
                                'subQuery' => ' AND id <> ' . $_SESSION['user_id'],
                                'label' => 'Перенаправить на:'
                            ];
                            $html .= $reg->renderListFromDB($f, [], '');
                            $html .= '</div></div><div class="action_result" id="agResult' . $section . '"></div>';
                        }
                    }
                } else {
                    // ============ ДРУГИЕ СОТРУДНИКИ ============
                    if ($isSignersSection) {
                        // ДРУГИЕ ПОДПИСАНТЫ
                        if (!$allPreviousStagesCompleted) {
                            $html .= "<span style='color: #9e9e9e'>Ожидание завершения предыдущих этапов</span>";
                        } else {
                            $html .= "<span style='color: #9e9e9e'>Ожидает</span>";
                        }
                    } else {
                        // ДРУГИЕ СОГЛАСУЮЩИЕ
                        if ($statusInfo['status'] === 'approved') {
                            // уже обработано выше
                        } elseif ($statusInfo['status'] === 'redirected') {
                            // уже обработано выше
                        } else {
                            if ($listType == 1 && $level == 0 && $i > $startIndex && !$hasAnyRejection) {
                                $prevCanAct = true;
                                for ($j = $startIndex; $j < $i; $j++) {
                                    if (isset($itemArr[$j]['id'])) {
                                        $prevStatus = $getApproverStatus($itemArr[$j]);
                                        if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                                            $prevCanAct = false;
                                            break;
                                        }
                                    }
                                }
                                if (!$prevCanAct) {
                                    $html .= "<span style='color: #9e9e9e'>Ожидание предыдущего</span>";
                                } else {
                                    $html .= "<span style='color: #9e9e9e'>Ожидает</span>";
                                }
                            } else {
                                $html .= "<span style='color: #9e9e9e'>Ожидает</span>";
                            }
                        }
                    }
                }
            }

            $html .= '</td>';

            // Комментарий
            $html .= '<td>';
            $comment = isset($item['comment']) ? htmlspecialchars(trim($item['comment'])) : '';

            if ($isCurrentUser && isset($canAct) && $canAct && !$hasAnyRejection/* && !$isSignersSection*/) {
                $html .= $item['comment'];
                $html .= '<div class="item w_100">
                <div class="el_data">
                    <textarea class="el_textarea" name="comment" rows="3" placeholder="Комментарий"></textarea>
                </div>
            </div>';
            } else {
                $html .= (!empty($comment) ? nl2br($item['comment']) : '-');
            }
            $html .= '</td></tr>';

            // Обработка перенаправлений
            if (isset($item['redirect']) && is_array($item['redirect'])) {
                $html .= $this->buildAgreementList(
                    $item['redirect'],
                    $section,
                    $users,
                    $urgent_types,
                    $user_signs,
                    $reg,
                    $level + 1,
                    $allSections
                );
            }

            // Увеличиваем номер строки
            if ($level == 0 && isset($processedUsers[$userId]) && !$processedUsers[$userId]['incremented']) {
                $processedUsers[$userId]['incremented'] = true;
                $rowNumber++;
            }
        }

        return $html;
    }

    //Создает документ в cam_agreement
    public function createDocument(array $data, int $docId = 0): array
    {
        $err = 0;
        $errStr = array();
        $result = false;
        $errorFields = array();
        $regId = 66; //'agreement'
        $documentId = 0;

        $regProps = $this->rb::getAll('SELECT
            ' . TBL_PREFIX . 'regfields.prop_id AS fId,  
            ' . TBL_PREFIX . 'regprops.*
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
        );

        $exist = $docId > 0
            ? $this->db->selectOne('agreement', ' WHERE id = ?', [$docId])
            : $this->db->selectOne('agreement', ' WHERE source_table = ? 
        AND source_id = ? ORDER BY id DESC LIMIT 1', [$data['source_table'], $data['source_id']]
            );
        $documentId = $exist->id;

        //Проверяем обязательные поля
        foreach ($regProps as $f) {
            $check = $this->checkRequiredField($regId, $f, $data);
            if (!$check['result']) {
                $err++;
                $errStr[] = $check['message'];
                $errorFields[] = $check['errField'];
            }
        }

        if ($err == 0) {
            reset($regProps);
            $registry = [
                'created_at' => date('Y-m-d H:i:s'),
                'author' => $_SESSION['user_id'],
                'source_id' => intval($_POST['source_id']),
                'source_table' => $_POST['source_table'],
                'file_ids' => $_POST['file_ids'],
                'prev_ins_id' => $_POST['ins'],
                'executors_list' => json_encode($_POST['executors_list']),
                'executors_head' => intval($_POST['executors_head']),
                'plan_id' => $_POST['plan'],
                'ins_id' => $_POST['ins'],
                'unit_id' => $_POST['unit_id'],
                'check_period' => $_POST['check_period'],
                'action_period' => $_POST['action_period'],//$_POST['action_period_hidden'][0],
                'action_period_text' => $_POST['action_period']
            ];


            foreach ($regProps as $f) {
                $value = $this->prepareValues($f, $_POST);
                $registry[$f['field_name']] = $value;
            }

            try {
                if (intval($exist->id) == 0) {
                    $this->db->insert('agreement', $registry);
                    $documentId = $this->db->last_insert_id;
                } else {
                    $this->db->update('agreement', $exist->id, $registry);
                }
                $result = true;
                $message = 'Документ успешно создан.';
            } catch (\RedBeanPHP\RedException $e) {
                $result = false;
                $message = $e->getMessage();
            }

        } else {
            $message = '<strong>Ошибка:</strong><br> ' . implode('<br>', $errStr);
        }
        return [
            'result' => $result,
            'resultText' => $message,
            'errorFields' => $errorFields,
            'documentId' => $documentId
        ];
    }

    public function renderFileInput(?array $f, ?array $editData, string $mode): string
    {
        $html = '';
        $gui = new \Core\Gui();
        $files = new Files();
        $editData['file_ids'] = (is_string($editData['file_ids']))
            ? json_decode($editData['file_ids']) : $editData['file_ids'];
        $fileArr = $files->getAttachedFiles($editData['file_ids']);
        $auth = new \Core\Auth();
        if (is_array($fileArr) && count($fileArr) > 0) {
            $html .= '<ul class="attached_files"><strong>Приложенные файлы:</strong>';
            foreach ($fileArr as $f) {
                $html .= '<li data-id="' . $f['id'] . '"><a href="/files/download.php?id=' . $f['id'] . '">' . $f['file'] . '</a>' .
                    ($_SESSION['user_id'] == $f['author'] || $auth->isAdmin() ?
                        '<span class="material-icons file_delete" title="Удалить">delete</span>' : '') .
                    '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '<h1 class="upload_link"><span class="material-icons clip">attach_file</span> Приложить файлы ';

        $html .= '<span class="material-icons arrow">expand_more</span></h1>
            <div class="upload-container" style="display: none">
            <input type="hidden" name="document_id" id="document_id" value="' . $editData['document_id'] . '">
            <input type="hidden" name="max_file_uploads" value="' . ini_get('max_file_uploads') . '">
            <input type="hidden" name="upload_max_filesize" value="' . $gui->parse_size(ini_get('upload_max_filesize')) . '">
            <div class="file-drop-zone" id="fileDropZone">
                <div class="drop-zone-content">
                    <span class="drop-zone-icon material-icons">cloud_upload</span>
                    <span class="drop-zone-text">Перетащите файлы сюда или</span>
                    <label for="fileInput" class="file-label">Выберите файлы</label>
                    <input type="file" name="files[]" id="fileInput" multiple class="file-input">
                </div>
            </div>
            
            <div id="fileList" class="file-list"></div>
            
            <!--<button type="submit" id="uploadButton" class="upload-button" disabled>Загрузить файлы</button>📁-->
            </div>' .
            '<script>el_tools.initUpload();</script>';
        return $html;
    }

    /**
     * Метод html-рендеринга формы добавления/редактирования записи справочника
     *
     * @param int $regId - id справочника
     * @param array $props - массив полей, если не указан $regId
     * @param array $editData - массив данных для предзаполнения формы, например при редактировании
     * @param string $mode - режим отображения: view - просмотр, edit - редактирование
     * @return string - html-код формы
     */
    public function buildForm(int $regId, array $props = [], array $editData = [], string $mode = 'view'): string
    {
        $propsIds = [];
        $regProps = [];

        if ($regId > 0) {
            //Построение формы существующего справочника
            $regProps = $this->rb::getAll('SELECT
            ' . TBL_PREFIX . 'regprops.*,
            ' . TBL_PREFIX . 'regfields.prop_id AS fId,
            ' . TBL_PREFIX . 'regfields.required AS required,
            ' . TBL_PREFIX . 'regfields.unique AS unique, 
            ' . TBL_PREFIX . 'regfields.label 
            FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
            WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
            ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [$regId]
            );
        } else {
            //Построение формы нового справочника
            foreach ($props as $p) {
                $propsIds[] = $p['value'];
            }

            $regPropsAll = $this->db->select('regprops', 'WHERE id IN (' . implode(', ', $propsIds) . ')');

            //Сортируем по порядку в $props
            foreach ($props as $p) {
                $regPropsAll[$p['value']] = (array)$regPropsAll[$p['value']];
                $regPropsAll[$p['value']]['required'] = $p['required'] == 'true' ? 1 : 0;
                $regPropsAll[$p['value']]['unique'] = $p['unique'] == 'true' ? 1 : 0;
                $regPropsAll[$p['value']]['label'] = $p['label'];
                $regProps[] = $regPropsAll[$p['value']];
            }
        }

        $html = '<div class="group">';

        foreach ($regProps as $f) {
            switch ($f['type']) {
                case 'textarea':
                case 'html':
                    $html .= $this->renderTextarea($f, $editData, $mode);
                    break;
                case 'select':
                case 'multiselect':
                case 'years':
                    $html .= $this->renderSelect($f, $editData, $mode);
                    break;
                case 'list_fromdb':
                case 'list_fromdb_multi':
                    if ($f['label'] == 'Шаблон плана') {
                        $f['width'] = '33';
                    }

                    $html .= $this->renderListFromDB($f, $editData, $mode);
                    break;
                case 'checklists':
                    $ch = $this->db->getRegistry('checklists');
                    $options_list = [];
                    foreach ($ch['array'] as $key => $item) {
                        $options_list[] = ['label' => $item, 'value' => $key];
                    }
                    $f['multiselect'] = '1';
                    $f['options_list'] = json_encode($options_list);
                    $html .= $this->renderSelect($f, $editData, $mode);
                    break;
                case 'quarterSelect':
                    $html .= $this->renderQuarter($f, $editData, $mode);
                    break;
                case 'addInstitution':
                    $html .= $this->renderAddInstitution($f, $editData, $mode);
                    break;
                case 'addObject':
                    $html .= $this->renderAddObject($f, $editData, $mode);
                    break;
                case 'addSignatory':
                    $html .= $this->renderAddSignatory($f, $editData, $mode);
                    break;
                case 'addAligner':
                    $html .= $this->renderAddAligner($f, $editData, $mode);
                    break;
                case 'addAgreement':
                    $html .= '<div class="group agreement_list_group">';
                    $html .= $this->renderAddAgreement($f, $editData, $mode);
                    $html .= '</div>';
                    break;
                case 'calendar':
                    $f['type'] = 'single_date';
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'time':
                case 'datetime':
                    $f['type'] = 'date_time';
                    if (intval($f['default_currdatetime']) == 1) {
                        $f['default_value'] = date('Y-m-d H:i');
                    }
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'multi_date':
                case 'range_date':
                    $f['type'] = 'date';
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'phone':
                    $f['type'] = 'tel';
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'checkbox':
                    $html .= $this->renderCheckbox($f, $editData, $mode);
                    break;
                case 'radio':
                    $html .= $this->renderRadio($f, $editData, $mode);
                    break;
                case 'password':
                    $f['default_value'] = '';
                    $f['type'] = 'password';
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'file':
                    $html .= $this->renderFileInput($f, $editData, $mode);
                    break;
                case 'import_button':
                    $html .= '<div class="w_16" style="margin-top: 15px;"><button class="button icon text" ' .
                        'title="Импорт из файла Excel" id="import_plan_btn">' .
                        '<span class="material-icons">file_download</span>Импорт</button></div>';
                    break;
                case 'violations':
                    $f['from_db'] = 75;
                    $html .= $this->renderListFromDB($f, $editData, $mode);
                    $f['type'] = 'html';
                    $html .= $this->renderTextarea($f, $editData, $mode);
                    break;
                default:
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
            }
        }

        $html .= '</div>';
        return $html;
    }

    public function buildPlanDocument(array $data, ?int $doc_id = 0): string
    {
        $insArr = [];
        $clear_agreement = [];
        $signers = [];
        $signers_position = [];
        $sign = [];
        $signs = [];
        $html = '';
        $agreement_date = $data['agreement_date'] ?? '_________';
        //print_r($data); echo $doc_id;
        if (is_array($data) && count($data) > 0) {
            $tmpl = $this->db->selectOne('documents', ' where id = ?', [intval($data['document'])]);
            $inst = $this->db->getRegistry('institutions');
            $mins = $this->db->getRegistry('ministries');
            $insp = $this->db->getRegistry('inspections');
            $units = $this->db->getRegistry('units');
            $users = $this->db->getRegistry('users', '', [], ['surname', 'name', 'middle_name',
                    'institution', 'ministries', 'division', 'position']
            );
            $temp = new Templates();
            $date = new \Core\Date();
            if (is_array($data['institutions']) && count($data['institutions']) > 0) {
                for ($i = 0; $i < count($data['institutions']); $i++) {
                    $mothsArr = json_decode($data['periods_hidden'][$i]);
                    $action_start = $date->getMonthNameByNumber(intval($mothsArr[0]));
                    $insArr[$i] = [
                        'check_types' => $data['checks'],
                        'institutions' => $data['institutions'][$i],
                        'units' => $data['units'][$i],
                        'periods' => $data['periods'][$i],
                        'periods_hidden' => $data['periods_hidden'][$i],
                        'start_month' => $action_start,
                        'inspections' => $data['inspections'],
                        'check_periods' => $data['check_periods'][$i],
                    ];
                }
            }

            $data['agreementlist'] = $this->fixJsonArray($data['agreementlist']);
            //print_r($data['agreementlist']);
            if (is_array($data['agreementlist']) && count($data['agreementlist']) > 0
                && !$this->allValuesEmpty($data['agreementlist'])) {
                for ($s = 0; $s < count($data['agreementlist']); $s++) {
                    //print_r($data['agreementlist'][$s]);
                    if (is_array($data['agreementlist'][$s])) {
                        $clear_agreement[] = $data['agreementlist'][$s];

                        //Выявляем подписантов
                        $sections = $data['agreementlist'][$s];//json_decode($data['agreementlist'][$s]);

                        if (isset($sections[0]['stage']) && $sections[0]['stage'] == '') { //Секция подписантов
                            $count = 0;
                            foreach ($sections as $sec) {
                                //print_r($sec);
                                //if (intval($sec['type']) == 2) { //подписание
                                if ($doc_id > 0) {
                                    $signs = $this->db->selectOne('signs',
                                        ' where user_id = ? AND doc_id = ? AND type = 1
                                             ORDER BY section DESC LIMIT 1', [$sec['id'], $doc_id]
                                    ); //print_r($signs);
                                } //echo ' where user_id = '.$sec['id'].' AND doc_id = '.$doc_id;
                                $role = intval($sec['role']);

                                $userItem = $users['result'][$sec['id']];

                                $signers[$role] = $userItem->surname . ' ' .
                                    mb_substr($userItem->name, 0, 1) . '. ' .
                                    (strlen($userItem->middle_name) > 0 ? mb_substr($userItem->middle_name, 0, 1) . '.' : '');

                                $position = $userItem->position;
                                if (intval($sec['vrio']) > 0) {
                                    $position = 'И.О. ' . $users['result'][$sec['vrio']]->position;
                                }

                                $signers_position[$role] = $position;/*$inst['array'][$userItem->institution].' '.
                                    (intval($userItem->ministries) > 0 ? '<br>'.$mins['array'][$userItem->ministries] : '').
                                    (intval($userItem->division) > 0 ? '<br>'.$units['array'][$userItem->division] : '').
                                    (strlen($userItem->position) > 0 ? '<br>'.$userItem->position : '');*/

                                $sign[$role] = is_object($signs) ?
                                    $temp->getSign(json_decode($signs->sign, true)['certificate_info']) : '';

                                $count++;
                                //}
                            }
                        }
                    }
                }

                $data['agreementlist'] = $clear_agreement;
                $data['agreement_date'] = $agreement_date;
                $data['sign_1'] = $sign[0];
                $data['signer_1'] = $signers[0];
                $data['signer_1_position'] = $signers_position[0];
                $data['sign_2'] = $sign[1];
                $data['signer_2'] = $signers[1];
                $data['signer_2_position'] = $signers_position[1];
            }
//print_r($sign);
            //Создаём документ плана
            $header_vars = [
                'agreement_date' => $agreement_date,
                'sign_1' => $sign[0],
                'signer_1' => $signers[0],
                'signer_1_position' => $signers_position[0],
                'year' => $data['year']
            ]; //print_r($sign);

            $header_vars = array_merge($header_vars, $data);
            $html .= $temp->twig_parse($tmpl->header, $header_vars);

            //$html .= $temp->twig_parse($data['longname'], ['curr_year' => date('Y')]);

            $body_vars = [];
            $check_number = 1;
            if (is_array($insArr) && count($insArr) > 0) {
                foreach ($insArr as $ch) {
                    $ch_period = explode(' - ', $ch['check_periods']);
                    $ch_period_start = $this->date->correctDateFormatFromMysql($ch_period[0]);
                    $ch_period_end = $this->date->correctDateFormatFromMysql($ch_period[1]);
                    $mothsArr = json_decode($ch['periods_hidden']);
                    $years = $date->getYearsFromPeriod($ch['check_periods']);
                    $action_start = $date->getMonthNameByNumber(intval($mothsArr[0]));
                    $body_vars[] = [
                        'check_number' => $check_number,
                        'institution' => stripslashes($inst['result'][$ch['institutions']]->name),
                        'unit' => stripslashes($units['array'][$ch['units']]),
                        'inspections' => stripslashes($insp['result'][$ch['inspections'][0]]->name),
                        'period' => $ch['periods'],
                        'start_month' => $action_start,
                        'check_periods' => $ch_period_start . ' - ' . $ch_period_end,
                        'check_periods_years' => $years
                    ];
                    $check_number++;
                }

            }
            $body_vars = array_merge($data, ['checks' => $body_vars]); //print_r($body_vars);
            $html .= $temp->twig_parse($tmpl->body, $body_vars);

            $bottom_vars = [
                'agreement_date' => $agreement_date,
                'sign_2' => $sign[1],
                'signer_2' => $signers[1],
                'signer_2_position' => $signers_position[1]
            ];
            $bottom_vars = array_merge($bottom_vars, $data);
            $html .= $temp->twig_parse($tmpl->bottom, $bottom_vars);
        }
        //echo $html;
        return $html;
    }

    public function getNewDocNumber(int $documentacial): string
    {
        $prefix = '';
        /*
         * 6 Лист согласования
            5 Устранение нарушений
            4 Доклад
            3 План проверок
            2 Акт
            1 Приказ
         * */
        switch ($documentacial) {
            case 1:
                $prefix = 'ПРП';
                break;
            case 2:
                $prefix = 'АКП';
                break;
            case 3:
                $prefix = 'ПЛП';
                break;
            case 4:
                $prefix = 'ДКП';
                break;
            case 5:
                $prefix = 'УСП';
                break;
        }
        $docs = $this->db->selectOne('agreement', ' WHERE documentacial = ?
         ORDER BY doc_number DESC LIMIT 1', [$documentacial]
        );

        $new_plan_num = 1000;
        if (strlen($docs->doc_number) > 0) {
            $plan_number = $docs->doc_number;
            $plan_numberArr = explode('-', $plan_number);
            if ($plan_numberArr[1] == date('Y')) {
                $new_plan_num = intval(str_replace($prefix, '', $plan_numberArr[0])) + 1;
            }
        }
        return $prefix . $new_plan_num . '-' . date('Y');
    }


    /**
     * Метод проверки заполнения обязательных полей
     *
     * @param int $regId - id справочника
     * @param array $field - массив имён проверяемых полей
     * @param array $var - массив значений проверяемых полей
     * @return array - массив результата проверки
     */
    public function checkRequiredField(int $regId, array $field, array $var): array
    {
        $result = ['result' => true, 'message' => '', 'errField' => ''];
        $err = 0;
        $errStr = [];
        $errorFields = [];
        if (intval($field['required']) == 1) {
            if (!isset($var[$field['field_name']]) ||
                (!is_array($var[$field['field_name']]) &&
                    (strlen(trim($var[$field['field_name']])) == 0) || $var[$field['field_name']] == '0') ||
                (is_array($var[$field['field_name']]) &&
                    (count($var[$field['field_name']]) == 0 || $var[$field['field_name']][0] == '0'))) {
                $err++;
                $errStr[] = 'Заполните поле &laquo;' . $field['label'] . '&raquo;';
                $errorFields[] = $field['field_name'];
            }
        }

        if ($field['unique'] == 1) {
            $table = $this->db->selectOne('registry', ' where id = ?', [$regId]);
            $exist = $this->db->selectOne($table->table_name, ' where ' . $field['field_name'] . ' = ?', [$var[$field['field_name']]]);
            if (intval($exist->id) > 0) {
                $err++;
                $errStr[] = 'Поле &laquo;' . $field['label'] . '&raquo; 
                со значением &laquo;' . $var[$field['field_name']] . '&raquo; уже есть в базе данных.<br>
                Значение должно быть уникальным.';
                $errorFields[] = $field['field_name'];
            }
        }
        return [
            'result' => $err == 0,
            'message' => implode('<br>', $errStr),
            'errField' => implode('<br>', $errorFields)
        ];
    }

    /**
     * Метод определения типа поля таблицы по типу поля справочника
     *
     * @param string $fieldType - тип поля справочника
     * @return string
     */
    public function getTypeColumn(string $fieldType): string
    {
        switch ($fieldType) {
            case 'integer':
            case 'list_fromdb':
            case 'depend_list':
            case 'inn':
            case 'checkbox':
            case 'radio':
                $type = 'INTEGER';
                break;
            case 'calendar':
            case 'curr_date':
            case 'range_date':
                $type = 'DATE';
                break;
            case 'datetime':
                $type = 'TIMESTAMP';
                break;
            case 'time':
                $type = 'TIME';
                break;
            case 'float':
            case 'price':
                $type = 'REAL';
                break;
            case 'bank':
            case 'multiselect':
            case 'list_fromdb_multi':
            case 'addSignatory':
            case 'addAligner':
            case 'addInstitution':
            case 'addAgreement':
            case 'addObject':
            case 'checklists':
                $type = 'JSONB';
                break;
            default:
                $type = 'TEXT';
                break;
        }
        return $type;
    }

    /**
     * Метод подготовки данных для записи в базу данных
     * @param array $f - характеристики поля в базе данных
     * @param array $var - имя переменной ($_POST, $_GET)
     * @return false|float|int|string - подготовленные данные поля
     */
    public function prepareValues(array $f, array $var)
    {
        $value = null;
        if ($f['type'] == 'checkbox') {
            $value = isset($var[$f['field_name']]) ? 1 : 0;
        } else {
            $value = $var[$f['field_name']];
        }
        if ($f['type'] == 'password' && strlen(trim($var[$f['field_name']])) > 0) {
            $value = str_replace('$1$', '', crypt(md5($var[$f['field_name']]), '$1$'));
        }
        switch ($this->getTypeColumn($f['type'])) {
            case 'INTEGER':
                $value = intval($value);
                break;
            case 'REAL':
                $value = floatval($value);
                break;
            case 'JSONB':
                $value = $this->fixJsonArray($value);
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            default:
                $value = trim(str_replace('  ', ' ', $value));
        }
        return strlen(trim($value)) > 0 ? $value : '';
    }

    /**
     * Метод создает новый справочник и его таблицу
     * @param int $reg_id - id вновь созданного справочника
     * @param string $tableName - название таблицы справочника
     * @param array $fields - массив полей в формате [label, value, sort]
     * @param string $comment - комментарий к таблицы
     * @throws RedException
     */
    public function createRegistry(int $reg_id, string $tableName, array $fields, string $comment = '')
    {
        $field_Ids = [];
        $field_props = [];
        $reg_props = [];
        $field_comments = [];

        //Составляем массив id полей из regprops
        foreach ($fields as $f) {
            $field_Ids[] = $f->value;
            $reg_props[] = '(' . $reg_id . ', ' . intval($f->value) . ', ' .
                intval($f->sort) . ", '" . addslashes($f->label) . "', " . (int)$f->required . ', ' . (int)$f->unique . ')';
        }

        if (count($reg_props) > 0) {
            $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "regfields WHERE reg_id = $reg_id");
            $this->rb::exec('INSERT INTO ' . TBL_PREFIX . 'regfields (reg_id, prop_id, sort, label, required, "unique") 
            VALUES ' . implode(', ', $reg_props)
            );
        }

        //Получаем параметры полей по их id
        if (count($field_Ids) > 0) {
            $prop_fields = $this->db->select('regprops', 'where id in (' . implode(', ', $field_Ids) . ')');

            foreach ($prop_fields as $field) {
                $type = $this->getTypeColumn($field->type);
                $field_props[] = $field->field_name . ' ' . $type;
                if (strlen(trim($field->cmment)) > 0) {
                    $field_comments[] = 'COMMENT ON COLUMN ' . TBL_PREFIX . $tableName . '.' . $field->field_name . " IS '" . $field->comment . "'";
                }
            }
        }
        //составляем запрос для создания таблицы
        $sql = 'CREATE TABLE ' . TBL_PREFIX . $tableName . " (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMP DEFAULT CURRENT_DATE,
                author INTEGER NOT NULL,
                active INTEGER DEFAULT '1'";
        if (count($field_props) > 0) {
            $sql .= ', ' . implode(', ', $field_props);
        }
        $sql .= ')';

        try {
            $this->rb::exec($sql);
            if (strlen(trim($comment)) > 0) {
                $this->rb::exec('COMMENT ON TABLE ' . TBL_PREFIX . $tableName . " IS '" . addslashes($comment) . "'");
            }
            //Добавляем комментарии к каждому полю
            if (count($field_comments) > 0) {
                foreach ($field_comments as $comm) {
                    $this->rb::exec($comm);
                }
            }
        } catch (RedException $e) {
            R::rollback();
            throw $e;
        }

    }

    /**
     * Метод редактирования струтктуры справочника
     *
     * @param int $reg_id - id справочника
     * @param string $tableName - имя таблицы справочника
     * @param array $fields - массив полей справочника в формате [label, value, sort]
     * @param string $comment - комментарий к таблице справочника
     * @throws RedException
     */
    public function updateRegistry(int $reg_id, string $tableName, array $fields, string $comment = '')
    {
        $field_Ids = [];
        $field_props = [];
        $reg_props = [];
        $field_comments = [];
        $delete_fields = [];
        $new_fields = [];
        $old_fields_ids = [];

        $regName = $this->db->selectOne('registry', ' WHERE id = ?', [$reg_id]);
        $oldTableName = $regName->table_name;

        //Получаем поля существующего справочника
        $reg_fields = $this->rb::getAll('SELECT
        ' . TBL_PREFIX . 'regfields.prop_id AS fId,  
        ' . TBL_PREFIX . 'regprops.field_name AS fName,
        ' . TBL_PREFIX . 'regprops.type AS fType
        FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
        WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND ' . TBL_PREFIX . "regfields.reg_id = $reg_id"
        );

        //И создаем массив существующих id полей
        foreach ($reg_fields as $f) {
            $old_fields_ids[] = $f['fid'];
        }

        //Составляем массив id полей из входящего массива $fields
        foreach ($fields as $f) {
            $field_Ids[] = intval($f->value);
            $reg_props[] = '(' . $reg_id . ', ' . intval($f->value) . ', ' .
                intval($f->sort) . ", '" . addslashes($f->label) . "', " . (int)$f->required . ', ' . (int)$f->unique . ')';

            //Определяем массив полей, которые надо добавить
            if (!in_array($f->value, $old_fields_ids)) {
                $new_fields[] = intval($f->value);
            }
        }

        //Определяем массив полей, которые надо удалить
        $delete_fields = array_diff($old_fields_ids, $field_Ids);

        if (count($new_fields) > 0) {
            $this->addRegistryFields($reg_id, $new_fields);
        }
        if (count($delete_fields) > 0) {
            $this->removeRegistryFields($reg_id, $delete_fields);
        }

        //Удаляем и снова записываем набор полей в regfields
        $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "regfields WHERE reg_id = $reg_id");
        $this->rb::exec('INSERT INTO ' . TBL_PREFIX . 'regfields (reg_id, prop_id, sort, label, required, "unique") 
        VALUES ' . implode(', ', $reg_props)
        );

        //Получаем параметры полей по их id
        $prop_fields = $this->db->select('regprops', 'where id in (' . implode(', ', $field_Ids) . ')');

        foreach ($prop_fields as $field) {
            if (strlen(trim($field->cmment)) > 0) {
                $field_comments[] = 'COMMENT ON COLUMN ' . TBL_PREFIX . $tableName . '.' . $field->field_name . " IS '" . $field->comment . "'";
            }
        }

        try {
            //Если пришло новое имя таблицы
            if ($tableName != $oldTableName) {
                $this->rb::exec('ALTER TABLE ' . TBL_PREFIX . $regName->table_name . ' RENAME TO ' . TBL_PREFIX . $tableName);
            }
            //Если пришел новый комментарий к таблице
            if (strlen(trim($comment)) > 0) {
                $this->rb::exec('COMMENT ON TABLE ' . TBL_PREFIX . $tableName . " IS '" . addslashes($comment) . "'");
            }
            //Добавляем комментарии к каждому полю
            if (count($field_comments) > 0) {
                foreach ($field_comments as $comm) {
                    $this->rb::exec($comm);
                }
            }
        } catch (RedException $e) {
            R::rollback();
            throw $e;
        }

    }

    /**
     * Метод добавления полей в таблицу справочника
     *
     * @param int $reg_id - id справочника
     * @param array $newFields - массив id новых полей
     */
    public function addRegistryFields(int $reg_id, array $newFields)
    {
        $field_Ids = [];

        //Получаем данные существующего справочника
        $regName = $this->db->selectOne('registry', ' WHERE id = ?', [$reg_id]);

        //Получаем параметры полей по их id
        $prop_fields = $this->db->select('regprops', 'where id in (' . implode(', ', $newFields) . ')');

        foreach ($prop_fields as $f) {
            $field_type = $this->getTypeColumn($f->type);
            $this->rb::exec('ALTER TABLE ' . TBL_PREFIX . $regName->table_name . ' ADD COLUMN ' . $f->field_name . ' ' . $field_type);
        }
    }

    /**
     * Метод удаления справочника
     *
     * @param int $reg_id - id справочника
     * @return bool
     */
    public function deleteRegistry(int $reg_id): bool
    {
        $reg_id = intval($reg_id);
        $reg = $this->db->selectOne('registry', "WHERE id = $reg_id");
        $this->db->delete('registry', [$reg_id]);
        try {

            $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "regfields WHERE reg_id = $reg_id");
            $this->rb::exec('DROP table ' . TBL_PREFIX . $reg->table_name);
            return true;
        } catch (RedBeanPHP\RedException\SQL $e) {
            // Сюда попадём только при SQL-ошибках
            R::rollback();
            error_log('SQL failed: ' . $e->getSQL(), 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/pg-log.txt');
            return false;

        } catch (RedBeanPHP\RedException $e) {
            // Сюда попадём для других ошибок RedBean
            R::rollback();
            error_log('RedBean error: ' . $e->getMessage(), 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/pg-log.txt');
            return false;

        } catch (Throwable $e) {
            // Все прочие исключения (например, PHP ошибки)
            R::rollback();
            error_log('System error: ' . $e->getMessage(), 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/pg-log.txt');
            return false;
        }
    }

    /**
     * Метод удаления полей из таблицы справочника
     *
     * @param int $reg_id - id справочника
     * @param array $deleteFields - массив id удаляемых полей
     */
    public function removeRegistryFields(int $reg_id, array $deleteFields)
    {
        $field_Ids = [];

        //Получаем данные существующего справочника
        $regName = $this->db->selectOne('registry', ' WHERE id = ?', [$reg_id]);

        //Получаем параметры полей по их id
        $prop_fields = $this->db->select('regprops', 'where id in (' . implode(', ', $deleteFields) . ')');

        foreach ($prop_fields as $f) {
            $this->rb::exec('ALTER TABLE ' . TBL_PREFIX . $regName->table_name . ' DROP COLUMN ' . $f->field_name);
        }
    }

    /*******************Чек-листы Начало**************************/

    /**
     * Метод html-рендеринга формы добавления/редактирования записи чек-листа
     *
     * @param int $regId - id справочника
     * @param string $mode - режим отображения: view - просмотр, edit - редактирование
     * @return string - html-код формы
     */
    public function buildChecklist(int $regId, array $props = [], array $editData = null, string $mode = 'view', $blockNumber = 1): string
    {
        $propsIds = [];
        $regProps = [];
        $gui = new Gui();
//print_r($editData);
        if ($regId > 0) {
            //Построение формы существующего справочника
            $regProps = $this->rb::getAll('SELECT
            ' . TBL_PREFIX . 'checkitems.*,
            ' . TBL_PREFIX . 'checkfields.prop_id AS fId,
            ' . TBL_PREFIX . 'checkfields.label,
            ' . TBL_PREFIX . 'checkfields.row_behaviour 
            FROM ' . TBL_PREFIX . 'checkfields, ' . TBL_PREFIX . 'checkitems
            WHERE ' . TBL_PREFIX . 'checkfields.prop_id = ' . TBL_PREFIX . 'checkitems.id AND 
            ' . TBL_PREFIX . 'checkfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'checkfields.sort', [$regId]
            );
        } else {
            //Построение формы нового справочника
            foreach ($props as $p) {
                $propsIds[] = $p['id'];
            }

            $regPropsAll = $this->db->select('checkitems', 'WHERE id IN (' . implode(', ', $propsIds) . ')');

            //Сортируем по порядку в $props
            foreach ($props as $p) {
                $regPropsAll[$p['id']] = (array)$regPropsAll[$p['id']];
                $regProps[] = $regPropsAll[$p['id']];
            }
        }

        $html = '<input type="hidden" name="checklist_id" value="' . $regId . '">' .
            '<ol class="group checklist' . ($mode == 'edit' ? ' edited' : '') . '">';
        //$blockNumber = 1;
        $itemNumber = 1;
        foreach ($regProps as $f) {
            $behaviour = json_decode($f['row_behaviour'], true);
            if ($f['type'] == 'block') {
                $html .= '<li class="item w_100 blockName"><h1 class="w_100">' . $blockNumber . '. ' . $f['name'] . '</h1>';
            } else {
                $html .= '<li class="item w_100' . ($f['item_type'] != 0 ? ' arbitrary' : '') . '" data-id="' . $f['id'] . '"' .
                    ($behaviour != [] ? ' data-row-behaviour="' . htmlspecialchars($f['row_behaviour']) . '"' : '') . '>';
                if ($f['item_type'] == 0) {
                    $html .= '<div class="w_75 label">' . ($blockNumber - 1) . '.' . $itemNumber . ' ' . $f['name'] . '</div>';
                } else {
                    $html .= '<label class="checklist_caption">' . $f['name'] . '</label>';
                    $itemNumber--;
                }
            }

            switch ($f['type']) {
                case 'block':
                    $html .= '';
                    break;
                case 'textarea':
                case 'html':
                    $html .= $this->renderTextarea($f, $editData, $mode);
                    break;
                case 'select':
                case 'multiselect':
                case 'years':
                    $html .= $this->renderSelect($f, $editData, $mode);
                    break;
                case 'list_fromdb':
                    $html .= $this->renderListFromDB($f, $editData, $mode);
                    break;
                case 'quarterSelect':
                    $html .= $this->renderQuarter($f, $editData, $mode);
                    break;
                case 'addInstitution':
                    $html .= $this->renderAddInstitution($f, $editData, $mode);
                    break;
                case 'addObject':
                    $html .= $this->renderAddObject($f, $editData, $mode);
                    break;
                case 'addSignatory':
                    $html .= $this->renderAddSignatory($f, $editData, $mode);
                    break;
                case 'addAligner':
                    $html .= $this->renderAddAligner($f, $editData, $mode);
                    break;
                case 'calendar':
                    $f['type'] = 'single_date';
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'time':
                case 'datetime':
                case 'multi_date':
                case 'range_date':
                    $f['type'] = 'date';
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
                case 'checkbox':
                    $html .= $this->renderCheckbox($f, $editData, $mode);
                    break;
                case 'radio':
                    $html .= $this->renderRadio($f, $editData, $mode);
                    break;
                case 'violations':
                    if (is_array($editData['violations_text']) && count($editData['violations_text']) > 0) {
                        $html .= '<div style="display:block">';
                        for ($v = 0; $v < count($editData['violations_text']); $v++) {
                            $html .= ' <div class="group w_100 violation question">' .
                                '<input type="hidden" name="violation_id[]" value="' . $editData['violations_id'][$v] . '"> ' .
                                '<input type="hidden" name="violation_checklist_id[]" value="' . $regId . '">' .
                                '<h5 class="item w_100 violation_caption violation_number section_number">Нарушение №' . ($v + 1) . '</h5>';
                            if (intval($editData['otherAuthor'][$v]) > 0) {
                                $html .= '<span class="otherAuthor">Проверяющий: ' . $gui->getUserFio($editData['otherAuthor'][$v]) . '</span>' .
                                    '<input type="hidden" name="otherAuthor[]" value="' . $editData['otherAuthor'][$v] . '">';
                            }
                            if ($v > 0) {
                                $html .= '<div class="button icon clear"><span class="material-icons">close</span></div>';
                            }
                            $vt['from_db'] = 75;
                            $vt['from_db_text'] = 13;
                            $vt['width'] = 100;
                            $vt['field_name'] = 'violation_type[]';
                            $vt['default_value'] = $editData['violations_type'][$v];
                            $vt['label'] = 'Тип нарушения';
                            $html .= $this->renderListFromDB($vt, $editData, $mode);

                            $f['type'] = 'html';
                            $f['field_name'] = 'violation_text[]';
                            $f['default_value'] = $editData['violations_text'][$v];
                            $html .= $this->renderTextarea($f, $editData, $mode);
                            if ($v == count($editData['violations_text']) - 1) {
                                $html .= '</div><button class="button icon text new_violation">' .
                                    '<span class="material-icons">add</span>Еще нарушение</button>';
                            }
                        }
                        $html .= '</div>';
                    } else {
                        $html .= '<div style="display:block"> <div class="group w_100 violation question">' .
                            '<input type="hidden" name="violation_checklist_id[]" value="' . $regId . '">' .
                            '<h5 class="item w_100 violation_caption violation_number section_number">Нарушение №1</h5>';
                        $vt['from_db'] = 75;
                        $vt['from_db_text'] = 13;
                        $vt['width'] = 100;
                        $vt['field_name'] = 'violation_type[]';
                        $vt['label'] = 'Тип нарушения';
                        $html .= $this->renderListFromDB($vt, $editData, $mode);
                        $f['type'] = 'html';
                        $f['field_name'] = 'violation_text[]';
                        $html .= $this->renderTextarea($f, $editData, $mode);
                        $html .= '</div><button class="button icon text new_violation">
                            <span class="material-icons">add</span>Еще нарушение
                        </button></div>';
                    }
                    break;
                default:
                    $html .= $this->renderTextInput($f, $editData, $mode);
                    break;
            }
            if ($mode == 'edit' && $f['type'] != 'block') {
                $html .= '<span class="material-icons behaviour' . ($behaviour != [] ? ' assigned' : '') . '" title="' . ($behaviour != [] ?
                        'Поведение настроено' : 'Настроить поведение') . '" data-id="' . $f['id'] . '">construction</span>';
            }
            $html .= '</li>';
            if ($f['is_block'] == '1') {
                $blockNumber++;
            } else {
                $itemNumber++;
            }
        }

        $html .= '</ol>';
        if ($mode == 'edit') {
            $html .= '<div id="behaviour">
                <div class="button icon close"><span class="material-icons">close</span></div>
                    <h4>Поведение элемента</h4>
                    <div id="inspector">
                        
                    </div>
                </div>';
        }

        $html .= '<script>el_app.checklistInit()</script>';
        return $html;
    }

    /**
     * Метод создает новый чек-лист и его таблицу
     * @param int $reg_id - id вновь созданного справочника
     * @param string $tableName - название таблицы справочника
     * @param array $fields - массив полей в формате [label, value, sort]
     * @param string $comment - комментарий к таблицы
     * @throws RedException
     */
    public function createChecklist(int $reg_id, string $tableName, array $fields, string $comment = '')
    {
        $field_Ids = [];
        $field_props = [];
        $reg_props = [];
        $field_comments = [];

        //Составляем массив id полей из regprops
        foreach ($fields as $f) {
            $field_Ids[] = $f->id;
            $reg_props[] = '(' . $reg_id . ', ' . intval($f->id) . ', ' .
                intval($f->sort) . ", '" . addslashes($f->label) . "', " . (int)$f->required . ', 
                ' . (int)$f->unique . ", '" . (strlen($f->rowBehaviour) == 0 ? 'null' : $f->rowBehaviour) .
                "', " . intval($f->is_block) . ', ' . intval($f->parent_id) . ')';
        }

        if (count($reg_props) > 0) {
            $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "checkfields WHERE reg_id = $reg_id");
            $this->rb::exec('INSERT INTO ' . TBL_PREFIX . 'checkfields (reg_id, prop_id, sort, label, required, "unique", row_behaviour, is_block, block_id) 
            VALUES ' . implode(', ', $reg_props)
            );
        }

        //Получаем параметры полей по их id
        if (count($field_Ids) > 0) {
            $prop_fields = $this->db->select('checkitems', 'where id in (' . implode(', ', $field_Ids) . ')');

            foreach ($prop_fields as $field) {
                $type = $this->getTypeColumn($field->type);
                $field_props[] = $field->field_name . ' ' . $type;
                if (strlen(trim($field->cmment)) > 0) {
                    $field_comments[] = 'COMMENT ON COLUMN ' . TBL_PREFIX . $tableName . '.' . $field->field_name . " IS '" . $field->comment . "'";
                }
            }
        }
        //составляем запрос для создания таблицы
        $sql = 'CREATE TABLE ' . TBL_PREFIX . $tableName . " (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMP DEFAULT CURRENT_DATE,
                author INTEGER NOT NULL,
                active INTEGER DEFAULT '1'";
        if (count($field_props) > 0) {
            $sql .= ', ' . implode(', ', $field_props);
        }
        $sql .= ')';

        try {
            $this->rb::exec($sql);
            if (strlen(trim($comment)) > 0) {
                $this->rb::exec('COMMENT ON TABLE ' . TBL_PREFIX . $tableName . " IS '" . addslashes($comment) . "'");
            }
            //Добавляем комментарии к каждому полю
            if (count($field_comments) > 0) {
                foreach ($field_comments as $comm) {
                    $this->rb::exec($comm);
                }
            }
        } catch (RedException $e) {
            R::rollback();
            throw $e;
        }

    }

    /**
     * Метод редактирования струтктуры чек-листа
     *
     * @param int $reg_id - id справочника
     * @param string $tableName - имя таблицы справочника
     * @param array $fields - массив полей справочника в формате [label, value, sort]
     * @param string $comment - комментарий к таблице справочника
     * @throws RedException
     */
    public function updateChecklist(int $reg_id, string $tableName, array $fields, string $comment = '')
    {
        $field_Ids = [];
        $field_props = [];
        $reg_props = [];
        $field_comments = [];
        $field_labels = [];
        $delete_fields = [];
        $new_fields = [];
        $old_fields_ids = [];

        $regName = $this->db->selectOne('checklists', ' WHERE id = ?', [$reg_id]);

        $exist = $this->db->db::getAll("SELECT EXISTS (
            SELECT 1 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = '" . TBL_PREFIX . $tableName . "'
        )"
        );
        if ($exist[0]['exists'] == false) {
            $this->createChecklist($reg_id, $tableName, $fields, $comment = '');
        }

        //Получаем поля существующего справочника
        $reg_fields = $this->rb::getAll('SELECT
        ' . TBL_PREFIX . 'checkfields.prop_id AS fId, 
        ' . TBL_PREFIX . 'checkitems.name AS fLabel, 
        ' . TBL_PREFIX . 'checkitems.field_name AS fName,
        ' . TBL_PREFIX . 'checkitems.type AS fType
        FROM ' . TBL_PREFIX . 'checkfields, ' . TBL_PREFIX . 'checkitems
        WHERE ' . TBL_PREFIX . 'checkfields.prop_id = ' . TBL_PREFIX . 'checkitems.id AND ' . TBL_PREFIX . 'checkfields.reg_id = ?', [$reg_id]
        );

        //И создаем массив существующих id полей
        foreach ($reg_fields as $f) {
            $old_fields_ids[] = $f['fid'];
        }

        foreach ($fields as $f) {
            $field_Ids[] = intval($f->id);
        }
        reset($fields);
        $regLabels = $this->db->getRegistry('checkitems', 'WHERE id in (' . implode(', ', $field_Ids) . ')',
            [], ['label']
        );


        //Составляем массив id полей из входящего массива $fields
        foreach ($fields as $f) {
            $field_Ids[] = intval($f->id);
            $rowBehaviour = strlen($f->rowBehaviour) > 0 ? $f->rowBehaviour : 'null';
            $reg_props[] = '(\'' . date('Y-m-d H:i:s') . '\', ' . $_SESSION['user_id'] . ', ' . $reg_id . ', ' . intval($f->id) . ', ' .
                intval($f->sort) . ", '" . addslashes($regLabels['array'][$f->id][0]) . "', " .
                (int)$f->required . ', ' . (int)$f->unique . ", '" . $rowBehaviour . "', " . intval($f->is_block) . ', ' . intval($f->parent_id) . ')';

            //Определяем массив полей, которые надо добавить
            if (!in_array($f->id, $old_fields_ids)) {
                $new_fields[] = intval($f->id);
            }
        }

        //Определяем массив полей, которые надо удалить
        $delete_fields = array_diff($old_fields_ids, $field_Ids);

        if (count($new_fields) > 0) {
            $this->addCheckFields($reg_id, $new_fields);
        }
        if (count($delete_fields) > 0) {
            $this->removeCheckFields($reg_id, $delete_fields);
        }

        //Удаляем и снова записываем набор полей в regfields
        $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "checkfields WHERE reg_id = $reg_id");
        $this->rb::exec('INSERT INTO ' . TBL_PREFIX . 'checkfields (created_at, author, reg_id, prop_id, sort, label, required, "unique", row_behaviour, is_block, block_id) 
        VALUES ' . implode(', ', $reg_props)
        );

        //Получаем параметры полей по их id
        $prop_fields = $this->db->select('checkitems', 'where id in (' . implode(', ', $field_Ids) . ')');

        foreach ($prop_fields as $field) {
            if (strlen(trim($field->cmment)) > 0) {
                $field_comments[] = 'COMMENT ON COLUMN ' . TBL_PREFIX . $tableName . '.' . $field->field_name . " IS '" . $field->comment . "'";
            }
        }

        try {
            //Если пришло новое имя таблицы
            if ($tableName != $regName->table_name) {
                $this->rb::exec('ALTER TABLE ' . TBL_PREFIX . $regName->table_name . ' RENAME TO ' . TBL_PREFIX . $tableName);
            }
            //Если пришел новый комментарий к таблице
            if (strlen(trim($comment)) > 0) {
                $this->rb::exec('COMMENT ON TABLE ' . TBL_PREFIX . $tableName . " IS '" . addslashes($comment) . "'");
            }
            //Добавляем комментарии к каждому полю
            if (count($field_comments) > 0) {
                foreach ($field_comments as $comm) {
                    $this->rb::exec($comm);
                }
            }
        } catch (RedException $e) {
            R::rollback();
            throw $e;
        }

    }

    /**
     * Метод добавления полей в таблицу чек-листа
     *
     * @param int $reg_id - id справочника
     * @param array $newFields - массив id новых полей
     */
    public function addCheckFields(int $reg_id, array $newFields)
    {
        $field_Ids = [];

        //Получаем данные существующего справочника
        $regName = $this->db->selectOne('checklists', ' WHERE id = ?', [$reg_id]);

        //Получаем параметры полей по их id
        $prop_fields = $this->db->select('checkitems', 'where id in (' . implode(', ', $newFields) . ')');

        foreach ($prop_fields as $f) {
            $field_type = $this->getTypeColumn($f->type);
            $this->rb::exec('ALTER TABLE ' . TBL_PREFIX . $regName->table_name . ' ADD COLUMN ' . $f->field_name . ' ' . $field_type);
        }
    }

    /**
     * Метод удаления полей из таблицы чек-листа
     *
     * @param int $reg_id - id справочника
     * @param array $deleteFields - массив id удаляемых полей
     */
    public function removeCheckFields(int $reg_id, array $deleteFields)
    {
        $field_Ids = [];

        //Получаем данные существующего справочника
        $regName = $this->db->selectOne('checklists', ' WHERE id = ?', [$reg_id]);

        //Получаем параметры полей по их id
        $prop_fields = $this->db->select('checkitems', 'where id in (' . implode(', ', $deleteFields) . ')');

        foreach ($prop_fields as $f) {
            $this->rb::exec('ALTER TABLE ' . TBL_PREFIX . $regName->table_name . ' DROP COLUMN ' . $f->field_name);
        }
    }

    /**
     * Метод удаления чек-листа
     *
     * @param int $reg_id - id чек-листа
     * @return bool
     */
    public function deleteChecklist(int $reg_id): bool
    {
        $reg_id = intval($reg_id);
        $reg = $this->db->selectOne('checklists', "WHERE id = $reg_id");
        $this->db->delete('checklists', [$reg_id]);
        try {

            $this->rb::exec('DELETE FROM ' . TBL_PREFIX . "checkfields WHERE reg_id = $reg_id");
            $this->rb::exec('DROP table ' . TBL_PREFIX . $reg->table_name);
            return true;
        } catch (RedBeanPHP\RedException\SQL $e) {
            // Сюда попадём только при SQL-ошибках
            R::rollback();
            error_log('SQL failed: ' . $e->getSQL(), 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/pg-log.txt');
            return false;

        } catch (RedBeanPHP\RedException $e) {
            // Сюда попадём для других ошибок RedBean
            R::rollback();
            error_log('RedBean error: ' . $e->getMessage(), 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/pg-log.txt');
            return false;

        } catch (Throwable $e) {
            // Все прочие исключения (например, PHP ошибки)
            R::rollback();
            error_log('System error: ' . $e->getMessage(), 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/pg-log.txt');
            return false;
        }
    }

    public function renderCheckResult(int $regId, array $editData = null, $blockNumber = 1): string
    {
        $propsIds = [];
        $regProps = [];
        $html = [];
        $first_block = '';
        $second_block = '';
        $out = '';

        if ($regId > 0) {
            //Построение формы существующего справочника
            $regProps = $this->rb::getAll('SELECT
            ' . TBL_PREFIX . 'checkitems.*,
            ' . TBL_PREFIX . 'checkfields.prop_id AS fId,
            ' . TBL_PREFIX . 'checkfields.label,
            ' . TBL_PREFIX . 'checkfields.row_behaviour 
            FROM ' . TBL_PREFIX . 'checkfields, ' . TBL_PREFIX . 'checkitems
            WHERE ' . TBL_PREFIX . 'checkfields.prop_id = ' . TBL_PREFIX . 'checkitems.id AND 
            ' . TBL_PREFIX . 'checkfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'checkfields.sort', [$regId]
            );
        }

        $table_html = '<table class="group checklist"><th>№</th><th>Вопросы проверки</th><th>Результат</th>' . "\n";
        $itemNumber = 1;
        $row_number = 0;
        foreach ($regProps as $f) {
            if ($f['type'] == 'block') {
                //Если это название блока
                $html[] = '<p>&nbsp;</p><h3>' . $blockNumber . '. ' . $f['name'] . '</h3>' . "\n";
            } else {
                //Если это строка таблицы (пункт чек-листа)
                if ($f['item_type'] == 0) {
                    $table_html .= '<tr><td>' . ($blockNumber - 1) . '.' . $itemNumber . '</td><td>' . $f['name'] . '</td>';
                    switch ($f['type']) {
                        case 'block':
                            //$html[] = '';
                            break;
                        /*case 'textarea':
                        case 'html':
                            $html .= $this->renderTextarea($f, $editData, $mode);
                            break;
                        case 'select':
                        case 'multiselect':
                        case 'years':
                            $html .= $this->renderSelect($f, $editData, $mode);
                            break;
                        case 'list_fromdb':
                            $html .= $this->renderListFromDB($f, $editData, $mode);
                            break;
                        case 'quarterSelect':
                            $html .= $this->renderQuarter($f, $editData, $mode);
                            break;
                        case 'addInstitution':
                            $html .= $this->renderAddInstitution($f, $editData, $mode);
                            break;
                        case 'addObject':
                            $html .= $this->renderAddObject($f, $editData, $mode);
                            break;
                        case 'addSignatory':
                            $html .= $this->renderAddSignatory($f, $editData, $mode);
                            break;
                        case 'addAligner':
                            $html .= $this->renderAddAligner($f, $editData, $mode);
                            break;
                        case 'calendar':
                        case 'time':
                        case 'datetime':
                        case 'multi_date':
                        case 'range_date':
                            ###################################################
                            break;
                        case 'checkbox':
                            $html .= $this->renderCheckbox($f, $editData, $mode);
                            break;*/
                        case 'radio':
                            $items = json_decode($f['radio_values'], true);
                            for ($r = 0; $r < count($items); $r++) {
                                if ($items[$r]['value'] == $editData[$f['field_name']]) {
                                    $table_html .= '<td>' . $items[$r]['label'] . '</td></tr>' . "\n";
                                }
                            }
                            break;
                        case 'text':
                            $table_html .= '<td>' . $editData[$f['field_name']] . '</td></tr>' . "\n";
                            break;
                        default:
                            if (strlen(trim($editData[$f['field_name']])) > 0) {
                                $html[] = $editData[$f['field_name']];
                            }
                            break;
                    }
                } else {
                    //Если это произвольное поле
                    //if(strlen(trim($editData[$f['field_name']])) > 0) {
                    $html[] = $editData[$f['field_name']];
                    // }
                    $itemNumber--;
                }

            }

            if ($f['is_block'] == '1') {
                $blockNumber++;
            } else {
                $itemNumber++;
            }
            $row_number++;
        }
        /*print_r($html);
        echo $table_html;*/

        $table_html .= '</table>' . "\n";

        $first_block = array_shift($html);
        $second_block = array_shift($html);

        return $first_block . $second_block . $table_html . implode('<br>', $html);
    }

    /*******************Чек-листы Конец**************************/

    /*******************Задачи Начало****************************/

    public function renderTextItem(string $label, string $data = null): string
    {
        $html = '
                <div class="item w_100">
                    <label>' . $label . ':&nbsp;</label>
                    ' . $data . '
                </div>';

        return $html;
    }

    public function buildTask(int $taskId, bool $view_result): array
    {
        $propsIds = [];
        $regProps = [];
        $excludeFields = ['name', 'sheet', 'comment']; //Исключаем из показа служебные поля
        $out = [];

        //Построение представления задачи
        $regProps = $this->rb::getAll('SELECT
        ' . TBL_PREFIX . 'regprops.*,
        ' . TBL_PREFIX . 'regfields.prop_id AS fId,
        ' . TBL_PREFIX . 'regfields.required AS required,
        ' . TBL_PREFIX . 'regfields.unique AS unique, 
        ' . TBL_PREFIX . 'regfields.label 
        FROM ' . TBL_PREFIX . 'regfields, ' . TBL_PREFIX . 'regprops
        WHERE ' . TBL_PREFIX . 'regfields.prop_id = ' . TBL_PREFIX . 'regprops.id AND 
        ' . TBL_PREFIX . 'regfields.reg_id = ? ORDER BY ' . TBL_PREFIX . 'regfields.sort', [48]
        );

        $task = $this->db->selectOne('tasks', ' WHERE id = ?', [$taskId]);

        $html = '<div class="item w_100"><strong>Задача:</strong></div>';
        $itemsHtml = '';

        foreach ($regProps as $f) {
            $field_name = $f['field_name'];
            if (!in_array($field_name, $excludeFields)) {//echo '<hr>'; print_r($f);
                switch ($f['type']) {
                    /*case 'textarea':
                    case 'html':
                        $html .= $this->renderTextarea($f, $editData, $mode);
                        break;
                    case 'select':
                    case 'multiselect':
                    case 'years':
                        $html .= $this->renderSelect($f, $editData, $mode);
                        break;
                    case 'list_fromdb':
                    case 'list_fromdb_multi':
                        $html .= $this->renderListFromDB($f, $editData, $mode);
                        break;
                    case 'checklists':
                        $ch = $this->db->getRegistry('checklists');
                        $options_list = [];
                        foreach($ch['array'] as $key => $item){
                            $options_list[] = ['label' => $item, 'value' => $key];
                        }
                        $f['options_list'] = json_encode($options_list);
                        $html .= $this->renderSelect($f, $editData, $mode);
                        break;
                    case 'quarterSelect':
                        $html .= $this->renderQuarter($f, $editData, $mode);
                        break;
                    case 'addInstitution':
                        $html .= $this->renderAddInstitution($f, $editData, $mode);
                        break;
                    case 'addObject':
                        $html .= $this->renderAddObject($f, $editData, $mode);
                        break;
                    case 'addSignatory':
                        $html .= $this->renderAddSignatory($f, $editData, $mode);
                        break;
                    case 'addAligner':
                        $html .= $this->renderAddAligner($f, $editData, $mode);
                        break;
                    case 'calendar':
                    case 'time':
                    case 'datetime':
                    case 'multi_date':
                    case 'range_date':
                        ###################################################
                        break;
                    case 'checkbox':
                        $html .= $this->renderCheckbox($f, $editData, $mode);
                        break;
                    case 'radio':
                        $html .= $this->renderRadio($f, $editData, $mode);
                        break;*/
                    case 'list_fromdb':
                    case 'list_fromdb_multi':
                        $r = $this->db->selectOne('registry', ' WHERE id = ?', [$f['from_db']]);//Имя таблицы
                        $n = $this->db->selectOne('regprops', ' WHERE id = ?', [$f['from_db_text']]);//Имя поля для option
                        $option_name = $n->field_name;

                        $optionsArr = [];
                        $optionText = '';

                        if (strlen($r->table_name) > 0 && strlen(trim($task->$field_name)) > 0) {
                            $field_value = json_decode($task->$field_name);
                            if (is_array($field_value) && count($field_value) > 0) {
                                $items = $this->db->select($r->table_name, ' WHERE active = 1 
                        AND id IN (' . implode(', ', $field_value) . ') order by id'
                                );

                                $itemNumber = 1;
                                foreach ($items as $item) {
                                    $optionsArr[] = $itemNumber . '. ' . $item->$option_name;
                                    $itemNumber++;
                                }
                                $optionText = implode(';<br>', $optionsArr);


                                $itemsHtml .= $this->renderTextItem($f['label'], $optionText);
                                $out[$f['label']] = $optionsArr;
                            }
                        }
                        break;
                    case 'checkbox':
                        $items = [];
                        if (strlen(trim($f['checkbox_values'])) > 0) {
                            $items = json_decode($f['checkbox_values'], true);
                        }
                        if ($task->$field_name == $items[0]['value']) {
                            $itemsHtml .= $this->renderTextItem($f['label'], $items[0]['label']);
                            $out[$f['label']] = $task->$f['field_name'];
                        }
                        break;
                    default:
                        $itemsHtml .= $this->renderTextItem($f['label'], $task->$field_name);
                        $out[$f['label']] = $task->$f['field_name'];
                        break;
                }
            }
        }

        if ($itemsHtml == '') {
            $html = '';
        }

        return ['html' => $html . $itemsHtml, 'array' => $out];
    }

    public function buildAssignment(int $taskId, bool $view_result, $editData = []): array
    {
        $out = [];
        $chStaff = $this->db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);

        if ($view_result) {

        }

        $dates = $chStaff->dates;
        $dateArr = explode(' - ', $dates);
        $dateStart = $this->date->dateToString($dateArr[0]);
        $dateEnd = $this->date->dateToString($dateArr[1]);

        if ($chStaff->object_type == 1) {
            $ins = $this->db->selectOne('institutions', ' WHERE id = ?', [$chStaff->institution]);
        } else {
            $ins = $this->db->selectOne('persons', ' WHERE id = ?', [$chStaff->institution]);
            $ins->name = $ins->surname . ' ' . $ins->first_name . ' ' . $ins->middle_name . ' ' .
                (strlen($ins->birth) > 0 ? $this->date->correctDateFormatFromMysql($ins->birth) : '');
        }
        $tasks = $this->db->selectOne('tasks', ' WHERE id = ?', [$chStaff->task_id]);
        $html = '<div class="group" id="taskContainer">';
        //Если есть координаты и нужен чекин по адресу
        if (strlen($ins->geo_lat) > 0 && $tasks->finding == 1) {
            $html .= '
            <script>
            var geo_lat = "' . $ins->geo_lat . '";
            var geo_lon = "' . $ins->geo_lon . '";
            var orgName = "' . htmlspecialchars($ins->name) . '";
            var orgNameShort = "' . htmlspecialchars($ins->short) . '";
            </script>
            <div id="openlayers-container">
                <div id="status">Определение местоположения...</div>
                <div id="map"></div>
            </div>
            <div class="item w_100">
                <div class="el_data">
                    <label>Комментарии о местоположении:</label>
                    <textarea name="geo_comment" class="el_textarea">' . htmlspecialchars($chStaff->geo_comment) . '</textarea>
                </div>
            </div>';
        }
        //$ins = $this->db->selectOne('institutions', ' WHERE id = ?', [$chStaff->institution]);

        $ousr = $this->db->selectOne('ousr', ' WHERE id =?', [$chStaff->unit]);
        $user = $this->db->selectOne('users', ' WHERE id = ?', [$chStaff->user]);
        $userFio = $user->surname . ' ' . $user->name . ' ' . $user->middle_name;

        $task = $this->db->selectOne('tasks', ' WHERE id = ?', [$chStaff->task_id]);
        $executor = $this->db->selectOne('institutions', ' WHERE id = ?', [$user->institution]);
        $ministries = $this->db->selectOne('ministries', ' WHERE id = ?', [$user->ministries]);
        $division = $this->db->selectOne('units', ' WHERE id = ?', [$user->division]);

        $html .= '
                <div class="item w_100">
                    <label>Объект проверки:&nbsp;
                    <strong>' . stripslashes($ins->name) . '</strong>' .
            (strlen($ins->location) > 0 ? '<br>' . $ins->location : '') .
            (strlen($ins->phones) > 0 ? '<br>' . $ins->phones . ' ' . $ins->phone : '') . '
                    </label>
                </div>';
        $out['institution'] = $ins->name;

        $html .= '
                <div class="item w_100">
                    <label>Период проверки:&nbsp;</label>
                    <strong>' . $dateStart . ' - ' . $dateEnd . '</strong>
                </div>';
        $out['dates'] = $dates;

        $html .= '<div class="item w_100">
                        <label>Проверяет:&nbsp;</label>';

        if (intval($chStaff->ousr) > 0) {
            $html .= '<label>ОУСР:</label>
                      <strong>' . $ousr->name . '</strong>';
            $out['ousr'] = $ousr->name;
        } else {
            $html .= '<label>' .
                $executor->short . '<br>' .
                (strlen($ministries->name) > 0 ? $ministries->name . '<br>' : '') .
                (strlen($division->name) > 0 ? $division->name . '<br>' : '') .
                (strlen($user->position) > 0 ? $user->position . '<br>' : '') .
                (strlen($userFio) > 0 ? '<strong> ' . $userFio . '</strong>' : '') . '</label>';
            $out['user']['institution'] = $executor->short;
            $out['user']['division'] = $division->name;
            $out['user']['position'] = $user->position;
            $out['user']['fio'] = $userFio;
        }
        $html .= '</div>';

        //Ищем руководителя проверки
        $head = $this->db->selectOne('checkstaff', ' WHERE 
                        check_uid = ? AND institution = ? AND is_head  = 1', [$chStaff->check_uid, $chStaff->institution]
        );
        if ($head) {
            $userHead = $this->db->selectOne('users', ' WHERE id = ?', [$head->user]);
            $html .= '<div class="item w_100">
                        <label>Руководитель проверки:</lavel>&nbsp;<strong>' .
                $userHead->surname . ' ' .
                $userHead->name . ' ' .
                $userHead->middle_name .
                '</strong></div>';
        }

        $taskContent = $this->buildTask($chStaff->task_id, $view_result);
        $html .= $taskContent['html'];
        $out['task'] = $taskContent['array'];

        if (substr_count($task->sheet, '[') > 0) {
            //Если чек-листов в задании несколько
            $sheets = json_decode($task->sheet);
            $checklistsArr = [];
            $blockNumber = 1;
            foreach ($sheets as $sh) {
                $eData = is_object($editData[$sh]) ? (array)$editData[$sh] : [];
                $checklistsArr[] = $this->buildChecklist($sh, [], $eData,
                    $view_result == 1 ? 'result' : 'edit', $blockNumber
                );
                $blockNumber++;
            }
            $checklist = implode('<p>&nbsp;</p>', $checklistsArr);
        } else {
            $checklist = $this->buildChecklist($task->sheet, [], $editData, $view_result == 1 ? 'result' : 'edit');
        }

        $html .= '
                <div>
                        <div class="item w_100"><strong>Чек-лист:</strong></div>' .
            $checklist .
            '</div>';
        $out['checklist'] = $checklist;
        $html .= $this->showTaskLog($taskId);
        $html .= $this->renderFileInput([], $editData, $view_result == 1 ? 'result' : 'edit');
        $html .= '</div>';


        return ['html' => $html, 'array' => $out];
    }

    public function showTaskLog(?int $taskId, $module = 'assigned', $form_id = 'view_task'): string
    {
        $html = '';
        $gui = new \Core\Gui();
        $logArr = $this->db->select('tasklog', ' WHERE task_id = ? AND module = ? AND form_id = ? 
        ORDER BY id DESC', [$taskId, $module, $form_id]
        );
        if (count($logArr) > 0) {
            $html .= '<h1 class="expand_link"><span class="material-icons logIcon">web_stories</span> Журнал действий' .
                '<span class="material-icons arrow">expand_more</span></h1>' .
                '<div class="item w_100 expandArea" style="max-height: 250px;overflow-y: auto; display: none"><ul>';
            foreach ($logArr as $log) {
                $html .= '<li><span class="log_time">' . $this->date->formatPostgresDate($log->created_at) . '</span> ' .
                    '<span class="log_fio">' . $gui->getUserFio($log->author, 'short') . '</span>' .
                    ' <span class="log_action">' . $log->action . '</span></li>';
            }
            $html .= '</ul></div>';
        }
        return $html;
    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    function insertTaskLog(?int $taskId, string $action, string $module = 'assigned', string $form_id = 'view_task')
    {
        $this->db->insert('tasklog', [
                'author' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'task_id' => $taskId,
                'action' => $action,
                'module' => $module,
                'form_id' => $form_id]
        );
    }

    /**
     * Добавляет учреждения для проверки в плане
     * @param string $plan_uid - UID плана
     * @param int $plan_version - Версия плана
     * @param int $ins_id - ID учреждения
     * @param int $check_type - Тип проверки
     * @param string $periods - Период проверки в названиях кварталов
     * @param string $periods_hidden - Период проверки в номерах месяцев
     * @param string $inspections - Предметы проверок
     * @param string $check_periods_start - Начало проверки
     * @param string $check_periods_end - Окончание проверки
     * @param int|null $unit_id - ID подразделения проверяемого учреждения
     * @return array - Выходной массив с результатом добавления и id новой записи или ошибками
     * @throws \RedBeanPHP\RedException
     */
    public function addInstitutionToPlan(
        string $plan_uid,
        int $plan_version,
        int $ins_id,
        int $check_type,
        string $periods,
        string $periods_hidden,
        string $inspections,
        string $check_periods_start,
        string $check_periods_end,
        int $unit_id = null
    ): array
    {
        $err = 0;
        $errStr = [];

        if ($ins_id == null) {
            $err++;
            $errStr[] = 'Укажите проверяемое учреждение';
        }
        if ($check_type == null) {
            $err++;
            $errStr[] = 'Укажите тип проверки';
        }
        if (strlen($periods_hidden) == 0) {
            $err++;
            $errStr[] = 'Укажите проверяемый период';
        }
        if (strlen($inspections) == 0) {
            $err++;
            $errStr[] = 'Укажите предметы проверки';
        }
        if (strlen($check_periods_start) == 0 || strlen($check_periods_end) == 0) {
            $err++;
            $errStr[] = 'Укажите проверяемый период';
        }
        if (strlen($plan_uid) == 0) {
            $err++;
            $errStr[] = 'Укажите UID плана';
        }
        if ($plan_version == null) {
            $err++;
            $errStr[] = 'Укажите версию плана';
        }
        if ($err == 0) {
            $ins = [
                'created_at' => date('Y-m-d H:i:s'),
                'author' => $_SESSION['user_id'],
                'institution' => $ins_id,
                'units' => $unit_id,
                'check_types' => $check_type,
                'periods' => $periods,
                'periods_hidden' => $periods_hidden,
                'inspections' => $inspections,
                'check_periods_start' => $check_periods_start,
                'check_periods_end' => $check_periods_end,
                'plan_uid' => $plan_uid,
                'plan_version' => $plan_version
            ];

            $this->db->insert('checkinstitutions', $ins);
            return ['result' => true, 'id' => $this->db->last_insert_id];
        } else {
            return ['result' => false, 'errors' => $errStr];
        }
    }

    public function getInstitutionsFromPlan(string $plan_uid = '', string $plan_version = '', int $id = 0)
    {
        $subQuery = '';
        $slot = [];
        if ($plan_uid != '' || $plan_version != '' || $id != 0) {
            if ($plan_uid != '') {
                $subQuery = ' WHERE plan_uid = ? AND plan_version = ?';
                $slot = [$plan_uid, $plan_version];
            }
            if ($id != 0) {
                $subQuery = ' WHERE id = ?';
                $slot = [$id];
            }
            return $this->db->selectOne('checkinstitutions', $subQuery, $slot);
        } else {
            return ['result' => false, 'errors' => 'Не указан ни UID плана, ни ID записи'];
        }
    }


    /**
     * Метод генерирует задания на каждый день в кэше для заданного пользователя
     * @param int $userId - ID пользователя
     * @throws \Exception
     */
    public function buildTasksListsToCache(int $userId)
    {
        $dataArr = [];
        $dateList = [];
        $intervalArr = [];
        $cache = new Cache();
        $tasks = $this->rb::getAll('SELECT cc.id AS id, cc.dates AS dates, ins.name AS name, ins.location AS location FROM ' .
            TBL_PREFIX . 'checkstaff cc, ' . TBL_PREFIX . 'institutions ins 
         WHERE cc.done = 0 AND cc.user = ' . $userId . ' AND cc.institution = ins.id'
        );
        if (count($tasks) > 0) {
            foreach ($tasks as $task) {
                $datesArr = explode(' - ', $task['dates']);
                $intervalArr = $this->date->getDatesInRange($datesArr[0], $datesArr[1]);
                foreach ($intervalArr as $day) {
                    $dateList[] = '"' . $day . '"';
                    $dataArr[$day][] = '<a class="list-group-item list-group-item-action" href="#" data-id="' . $task['id'] . '">
                            <h6>' . $task['name'] . '</h6>
                            <span>' . $task['location'] . '</span>
                        </a>';

                }
            }

            foreach ($dataArr as $day => $item) {
                $cache->saveToCache(implode("\n", $item), 'tasks', $userId, $day);
            }
            $cache->saveToCache('[' . implode(', ', $dateList) . ']', 'tasks', $userId, 'dates', 'json');
        }
    }

    /*******************Задачи Конец*****************************/


    /*******************Импорт в справочники Начало*********************/

    /**
     * Метод для получения массива сопоставления полей файла и полей целефой таблицы
     * @param string $filePath - путь к csv файлу
     * @param int $regId - id справочника
     * @return array - многомерный массив с распознанными и нераспознанными полями
     * @throws \Exception
     */

    public function comparisonImportCsv(string $filePath, int $regId, string $separator): array
    {
        $errStr = [];
        // Проверяем существование справочника
        if (!R::findOne('cam_regfields', 'reg_id = ?', [$regId])) {
            $errStr[] = "Справочник с ID $regId не найден";
        }

        // Открываем CSV файл
        if (($handle = fopen($filePath, 'r')) === false) {
            $errStr[] = 'Не удалось открыть файл CSV';
        }
        // Читаем заголовки (первую строку)
        $csvHeaders = fgetcsv($handle, 0, $separator);
        if ($csvHeaders === false) {
            throw new Exception('Не удалось прочитать заголовки CSV');
        }

        // Получаем все свойства для данного справочника
        $fields = R::find('cam_regfields', 'reg_id = ?', [$regId]);
        $matchedFields = [];
        $allMatchedHeaders = [];

        // Сопоставляем заголовки CSV с полями в базе
        foreach ($fields as $field) {
            $prop = R::load('cam_regprops', $field->prop_id);

            // Ищем совпадение по label или field_name
            foreach ($csvHeaders as $index => $csvHeader) {
                $csvHeader = trim($csvHeader);

                if (mb_strtolower($csvHeader) === mb_strtolower($field->label)) {
                    $matchedFields[$index] = [
                        'header' => $csvHeader,
                        'field_id' => $field->id,
                        'prop_id' => $field->prop_id,
                        'required' => $field->required,
                        'unique' => $field->unique,
                        'prop_type' => $prop->type,
                        'label' => $field->label
                    ];
                    $allMatchedHeaders[] = $csvHeader;
                }
            }
        }

        $newFileName = uniqid() . '.csv';
        if (!move_uploaded_file($filePath, $_SERVER['DOCUMENT_ROOT'] . '/files/' . $newFileName)) {
            $errStr[] = 'Не удалось переместить файл из временного хранилища.';
            unlink($filePath);
        }

        fclose($handle);

        $unmatchedFields = array_diff($csvHeaders, $allMatchedHeaders);

        if (empty($matchedFields)) {
            $errStr[] = 'Не найдено совпадений между полями CSV и справочника';
        }

        return [
            'result' => count($errStr) == 0,
            'fileName' => $newFileName,
            'matchedFields' => $matchedFields,
            'unmatchedFields' => $unmatchedFields,
            'errors' => $errStr
        ];
    }

    public function checkEncoding($filePath): array
    {
        $result = false;
        $message = [];
        $content = file_get_contents($filePath);

        // Определяем кодировку
        $encoding = mb_detect_encoding($content, [
            'UTF-8',
            'Windows-1251',
            'ISO-8859-1',
            'KOI8-R',
            'CP1251'
        ], true
        );

        $message[] = 'Определенная кодировка: ' . ($encoding ?: 'не определена') . "\n";

        // Проверяем валидность UTF-8
        if (mb_check_encoding($content, 'UTF-8')) {
            $message[] = "Файл уже в корректном UTF-8\n";
            $result = true;
        } else {
            $message[] = "Файл НЕ в UTF-8\n";
        }

        return [
            'result' => $result,
            'encoding' => ($encoding ?: 'не определена'),
            'message' => implode('<br>', $message)
        ];
    }

    public function cleanString($str)
    {
        if (!is_string($str)) {
            return $str;
        }

        // Пытаемся определить и исправить кодировку
        $encoding = mb_detect_encoding($str, ['UTF-8', 'Windows-1251', 'CP1251'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            //$str = mb_convert_encoding($str, 'UTF-8', $encoding);
        }

        // Удаляем невалидные UTF-8 символы
        //$str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');

        // 3. Удаляем одиночные 0xD0
        /*$cleanContent = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($str[$i]);

            if ($byte === 0xD0 || $byte === 0xD1) {
                // Всегда пропускаем следующий байт, если он есть
                if ($i < $len - 1) {
                    $i++; // Пропускаем пару к 0xD0
                    // Вместо пары байтов вставляем '?'
                    $cleanContent .= '?';
                    //echo "\n".$byte.' в строке '.$str."\n";
                } else {
                    // 0xD0 в конце - просто удаляем
                    $cleanContent .= '';
                }
            } else {
                $cleanContent .= $str[$i];
            }
        }
        $str = $cleanContent;*/

        //$str = iconv('UTF-8', 'UTF-8//IGNORE', $str);

        // Удаляем BOM
        $str = preg_replace('/^\xEF\xBB\xBF/', '', $str);

        // Заменяем "умные" кавычки и апострофы
        $replacements = [
            '«' => '"', '»' => '"',
            '„' => '"', '“' => '"', '”' => '"',
            '‘' => "'", '’' => "'", '´' => "'",
            '–' => '-', '—' => '-',
            '…' => '...',
            "\xE2\x80\x93" => '-', // EN DASH в UTF-8
            "\xE2\x80\x94" => '-', // EM DASH в UTF-8
            "\xE2\x80\x98" => "'", // LEFT SINGLE QUOTATION MARK
            "\xE2\x80\x99" => "'", // RIGHT SINGLE QUOTATION MARK
            "\xD0" => '',
            "\x27" => ''
        ];

        $str = str_replace(array_keys($replacements), array_values($replacements), $str);

        // Удаляем другие проблемные символы
        $str = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $str);

        // Удаляем NULL байты
        $str = str_replace("\0", '', $str);

        // Удаляем лишние пробелы
        $str = trim($str);

        return $str;
    }


    /**
     * /**
     * Метод для импорта CSV файла.
     * Предварительно требуется получить массив сопоставления полей с помощью метода comparisonImportCsv
     * и передать результат в параметр $comparisonData
     * @param string $filePath - путь к csv файлу
     * @param int $regId - id справочника
     * @param array $comparisonData - массив сопоставленных полей
     * @return array
     * @throws \Exception
     */
    public function importCsv(string $filePath, int $regId, array $comparisonData, bool $rewriteData = false, string $separator = ','): array
    {
        $errStr = [];
        $importData = [];
        $rowNumber = 1;
        $existRows = [];
        $updatedCount = 0;
        $skippedCount = 0;
        $result = false;
        $gui = new \Core\Gui();
        //Определяем целевую таблицу
        $table = $this->db->selectOne('registry', ' WHERE id = ? ', [$regId]);
        $target_table = $table->table_name;

        //Получаем список полей таблицы со свойствами
        $table_fields = $this->db->select('regprops',
            ' WHERE id IN (' . implode(', ', array_values($comparisonData)) . ')'
        );

        // Открываем CSV файл
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/files/' . $filePath, 'r')) === false) {
            $errStr[] = 'Не удалось открыть файл CSV';
        } else {

            while (($row = fgetcsv($handle, 0, $separator)) !== false) {

                $rowData = [];
                $rowExist = 0;
                $existDataINN = '';
                $existDataAddress = '';

                if ($rowNumber > 1) {
                    foreach ($comparisonData as $csvIndex => $fieldInfo) {
                        if (isset($row[$csvIndex])) {
                            $value = trim($row[$csvIndex]);//$this->cleanString($row[$csvIndex]);//
                            if (strlen($value) > 0 && $value != 'NULL') {
                                // Преобразование типов данных согласно prop_type
                                switch ($table_fields[$fieldInfo]->type) {
                                    case 'integer':
                                        $value = (int)$value;
                                        break;
                                    case 'jsonb':
                                        $value = json_decode($value, true) ?? $value;
                                        break;
                                    case 'phone':
                                        $value = $gui->formatPhone($value);
                                        break;
                                    case 'calendar':
                                        $value = $this->date->correctDateFormatToMysql($value);
                                        break;
                                    case 'timestamp':
                                        $value = date('Y-m-d H:i:s', strtotime($value));
                                        break;
                                    // Другие типы по необходимости
                                }

                                //Ищем, есть ли такая запись уже в справочнике по названию.
                                if ($this->db->tableHasNameField($target_table, 'name') &&
                                    $table_fields[$fieldInfo]->field_name == 'name') {
                                    $existName = $this->db->selectOne($target_table,
                                        " WHERE name = '" . $value . "'"
                                    );
                                    if ($existName != null) {
                                        $rowExist++;
                                        $existRows[$rowNumber] = $existName->id;
                                    }

                                }
                                if ($regId == 34) {
                                    //Если это импорт в Учреждения, то добавляем проверку по ИНН
                                    if ($this->db->tableHasNameField($target_table, 'inn') &&
                                        $table_fields[$fieldInfo]->field_name == 'inn') {
                                        $existINN = $this->db->selectOne($target_table,
                                            " WHERE inn = '" . $value . "'"
                                        );
                                        if ($existINN != null) {
                                            $rowExist++;
                                            $existRows[$rowNumber] = $existINN->id;
                                        }
                                    }
                                }
                                if ($regId == 70) {
                                    //Если это импорт в Адреса учреждений, то добавляем проверку по ИНН и адресу
                                    if ($this->db->tableHasNameField($target_table, 'inn') &&
                                        $table_fields[$fieldInfo]->field_name == 'inn') {
                                        $existDataINN[$rowNumber] = $value;
                                    }
                                    if ($this->db->tableHasNameField($target_table, 'target_address') &&
                                        $table_fields[$fieldInfo]->field_name == 'target_address') {
                                        $existDataAddress[$rowNumber] = $value;
                                    }
                                    if ($existDataINN[$rowNumber] != '' && $existDataAddress[$rowNumber] != '') {
                                        $existINN = $this->db->selectOne($target_table,
                                            ' WHERE inn = ? AND target_address = ?',
                                            [$existDataINN[$rowNumber], $existDataAddress[$rowNumber]]
                                        );
                                        if ($existINN != null) {
                                            $rowExist++;
                                            $existRows[$rowNumber] = $existINN->id;
                                        }
                                    }
                                }

                                $rowData[$table_fields[$fieldInfo]->field_name] = trim($value);

                            }
                        }
                    }
                    $existRows = array_unique($existRows);

                    if (!empty($rowData)) {

                        //Если элемент новый - вставляем
                        if ($rowExist == 0) {
                            $rowData['author'] = $_SESSION['user_id'];
                            $rowData['created_at'] = date('Y-m-d- H:i:s');
                            $rowData['active'] = 1;
                            $importData[] = $rowData;
                            //$this->db->insert($target_table, $rowData);
                        } else {
                            //Если не новый и разрешена перезапись - обновляем
                            if ($rewriteData) {
                                $rowData['author'] = $_SESSION['user_id'];
                                $rowData['created_at'] = date('Y-m-d- H:i:s');
                                $rowData['active'] = 1;
                                $importData[] = $rowData;
                                //$this->db->update($target_table, $existRows[$rowNumber], $rowData);
                                $updatedCount++;
                            } else {
                                $skippedCount++;
                            }
                        }

                    }
                }
                $rowNumber++;
            }

            fclose($handle);
        }
        echo $updatedCount . ' ' . $skippedCount . "\n";
        print_r($importData);
        $totalNumberRows = $rowNumber - 1;
        if (count($errStr) == 0) {
            $result = true;
            $errStr[] = 'Импортирован' . $gui->postfix($totalNumberRows, 'а', 'ы', 'ы') . ' ' . $totalNumberRows .
                ' строк' . $gui->postfix($totalNumberRows, 'а', 'и', '') .
                (count($existRows) > 0 ?
                    '<br>Из ' . count($existRows) . ' уже существующих в базе данных строк было ' .
                    (($updatedCount > 0) ? $updatedCount . ' перезаписано.' : '') .
                    (($skippedCount > 0) ? $skippedCount . ' пропущено.' : '') : '');
        }

        return ['result' => $result, 'messages' => $errStr];
    }

    /*******************Импорт в справочники Конец*********************/


    /*******************Импорт планов проверок Начало******************/
    public function isXLSX(string $filePath)
    {
        require_once ROOT . '/core/vendor/phpexcel/Classes/PHPExcel.php';
        // 2. Проверка MIME-типа
        $mime = mime_content_type($filePath);
        $validMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream'
        ];
        if (!in_array($mime, $validMimes)) return false;

        // 3. Проверка сигнатуры
        $header = file_get_contents($filePath, false, null, 0, 4);
        if ($header !== 'PK' . "\x03" . "\x04") return false;

        // 4. Попытка открыть файл
        try {
            $reader = PHPExcel_IOFactory::createReaderForFile($filePath);
            return $reader instanceof PHPExcel_Reader_Excel2007;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * /**
     * Метод для импорта CSV файла.
     * Предварительно требуется получить массив сопоставления полей с помощью метода comparisonImportCsv
     * и передать результат в параметр $comparisonData
     * @param string $filePath - путь к csv файлу
     * @param int $regId - id справочника
     * @param array $comparisonData - массив сопоставленных полей
     * @return array
     * @throws \Exception
     */
    public function importPlan(string $filePath, int $table_begin, int $plan_name): array
    {
        $errStr = [];
        $importData = [];
        $rowNumber = 1;
        $result = [];
        //$gui = new \Core\Gui();
        require_once ROOT . '/core/vendor/phpexcel/Classes/PHPExcel.php';
        try {
            // Загружаем файл
            $inputFileName = $filePath;
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);

            $worksheet = $objPHPExcel->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            $result = [];
            $unmatchedInstitutions = [];

            //Получаем полное название плана
            $planName = str_replace("\n", '', nl2br($worksheet->getCell('A' . $plan_name)->getValue()));
            preg_match('/на (\d{4}) год/', $planName, $year);

            // Начинаем с $table_begin строки
            for ($row = $table_begin; $row <= $highestRow; $row++) {
                // Получаем данные из строки
                $check_period = '';
                $quarter_months = [];
                $quarter_desc = '';
                $rowNumber = $worksheet->getCell('A' . $row)->getValue();
                $institutionName = $worksheet->getCell('B' . $row)->getValue();

                // Пропускаем пустые строки
                if (empty($institutionName) || intval($rowNumber) == 0) continue;

                $quarter = $worksheet->getCell('C' . $row)->getValue();
                $checkPeriod = $worksheet->getCell('D' . $row)->getValue();
                if ($checkPeriod != null) {
                    $check_period = $this->date->convertYearToDateRange($checkPeriod);
                    $check_period_arr = explode(' - ', $check_period);
                    $check_period_start = $this->date->correctDateFormatToMysql($check_period_arr[0]);
                    $check_period_end = $this->date->correctDateFormatToMysql($check_period_arr[1]);
                    $check_period = $check_period_start . ' - ' . $check_period_end;
                }
                if ($quarter != null) {
                    $quarters = $this->date->convertQuartersToMonths($quarter);
                    $quarter_months = $quarters['months'];
                    $quarter_desc = $quarters['description'];
                }

                $institutionName = str_replace('казенное', 'казённое', trim($institutionName));
                // Ищем учреждение в базе данных
                $insName = preg_replace('/["\'«»]/u', '', $institutionName);
                $institution = $this->rb::getRow("SELECT * FROM cam_institutions WHERE 
                                    regexp_replace(name, '[\"''«»]', '', 'g') ILIKE ?", ['%' . $insName . '%']
                );
                //'institutions', 'name = ?', [$institutionName]
//print_r($institution);
                if (!$institution) {
                    // Если учреждение не найдено, добавляем в список для сопоставления
                    if (!isset($unmatchedInstitutions[$rowNumber])) {
                        $unmatchedInstitutions[$rowNumber] = [
                            'name' => $institutionName,
                            'number' => $rowNumber,
                            'periods' => $quarter_desc,
                            'periods_hidden' => $quarter_months,
                            'check_period' => $check_period
                        ];

                    }/* else {
                        $unmatchedInstitutions[$institutionName]['rows'][] = $row;
                    }*/
                    continue;
                } else {

                    // Добавляем в результат
                    $result[$rowNumber] = [
                        'periods' => $quarter_desc,
                        'periods_hidden' => $quarter_months,
                        'number' => $rowNumber,
                        'name' => $institutionName,
                        'id' => $institution['id'],
                        'address' => $institution['legal'],
                        'check_periods' => $check_period
                    ];
                }
            }


        } catch (Exception $e) {
            die('Ошибка при обработке файла: ' . $e->getMessage());
        }

        return ['matched' => $result, 'plan_name' => $planName,
            'year' => $year[1], 'messages' => $errStr, 'unmatched' => $unmatchedInstitutions];
    }
    /*******************Импорт планов проверок Конец*******************/
}