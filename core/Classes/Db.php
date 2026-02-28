<?php

namespace Core;

use R;
use RedBeanPHP\RedException;
use RedBeanPHP\RedException\SQL;
use Core\Files;

/**
 * Класс Db содержит часто употрибимые методы взаимодействия с базой данных.
 * @package Core
 * @version 0.1
 */
class Db
{
    public /*int*/ $last_insert_id;
    private /*array*/ $_get, $_post, $_session;
    public /*\R*/ $db;
    private \Core\Files $files;


    /**
	 * Конструктор Db.
	 *
	 * Инициализируется так же класс R от ORM RedBeanPHP
	 */
	public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->db = new R();
        $this->last_insert_id = 0;
        $this->files = new Files();
    }

	/**
	 * Метод утсановки значения извне для приватных параметров класса.
	 *
	 * @param mixed $key Имя параметра
	 * @param mixed $val Значение параметра
	 * @return void Ничего не возвращает
	 */
    public function set($key, $val)
    {
        $this->$key = $val;
    }


	/**
	 * Метод получения данных из справочника в виде массива
	 *
	 * @param string $regName Строковый идентификатор справочника
	 * @param string $where SQL условие для выборки. Например, ' where id = ? '.
	 * @param array $slots Массив подстановочных значений в SQL условии.
	 *                     Они после очистки будут вставлены вместо вопросительного знака.
     * @param array $fields Массив имен полей, извлекаемых из таблицы. Если не задан, то извлекается только поле name.
	 * @return array Результат в виде массив в формате [id, name]
	 */
	public function getRegistry(string $regName, string $where = '', array $slots = [], $fields = []): array
    {
        /*if(isset($_SESSION['registry'][$regName]) && $where == ''){
            return $_SESSION['registry'][$regName];
        }else {*/
            $regArr = array();
            $reg = $this->select($regName, $where, $slots);
            foreach ($reg as $r) {
                if ($fields == []) {
                    $regArr[$r->id] = $r->name;
                } else {
                    $fArr = [];
                    foreach ($fields as $field) {
                        $fArr[] = $r->$field;
                    }
                    $regArr[$r->id] = $fArr;
                }
            }
            if($where == '') {
                $_SESSION['registry'][$regName]['result'] = $reg;
                $_SESSION['registry'][$regName]['array'] = $regArr;
            }
            return ['result' => $reg, 'array' => $regArr];
        //}
    }


	/**
	 * Метод удаления записи из базы данных.
	 *
	 * @param string $tableName Имя таблицы базы данных
	 * @param array  $ids Массив ID удаляемых строк
	 */
	public function delete(string $tableName, array $ids)
    {
        $this->db::trashBatch(TBL_PREFIX . $tableName, $ids);
    }


	/**
	 * Метод записи в базу данных.
	 *
	 * @param string $tableName Имя таблицы базы данных
	 * @param array  $values Ассоциативный массив вставляемых данных. Формат массива: "имя поля" => "значение"
	 * @return bool TRUE в случае успешной записи, иначе FALSE.
	 *              В случае успешной вставки записи в поле last_insert_id класса Db вносится ID новой записи
	 * @throws \RedBeanPHP\RedException
	 */
	public function insert(string $tableName, array $values): bool
    {
        $this->db::debug(1, 3);

        $logs = $this->db::getDatabaseAdapter()
            ->getDatabase()
            ->getLogger();

        $this->db::ext('xdispense', function ($type) {
            return $this->db::getRedBean()->dispense($type);
        });
        $result = $this->db::xdispense(TBL_PREFIX . $tableName);

        foreach ($values as $field => $value) {
            if (strlen($value) > 0) {
                $result->{$field} = trim($value);
            }
        }

        try {
            //if(isset())
            //$this->db->transaction( function() use ($result) {
                $this->db->store($result);
           // } );
            if(isset($_SESSION['registry'][$tableName])){
                unset($_SESSION['registry'][$tableName]);
            }
            file_put_contents(ROOT.'/logs/pg_log.txt', $logs->grep('INSERT'));
            $this->last_insert_id = $result->id;//$this->db->getInsertID();
            return true;
        } catch (SQL $e) {
            echo 'Ошибка: '.$e->getMessage();
            return false;
        }
    }

    public function count(string $tableName, string $exp = '', array $slots = []): int
    {
        return $this->db::count( $tableName, $exp, $slots);
    }

    public function cloneRows(string $tableName, array $ids)
    {
        $rows = $this->select($tableName,
            ' WHERE id IN ('.$this->db::genSlots( $ids ).')', $ids);
        foreach($rows as $index => $key){
            $new_row = [];
            foreach ($key as $field => $value) {
                if($field != 'id')
                    $new_row[$field] = $value;
            }
            $this->insert($tableName, $new_row);
        }
    }

    public function select(string $tableName, string $exp = '', array $slots = []): ?array
    {
        $result = $this->db::find(TBL_PREFIX . $tableName, $exp, $slots);
        //echo '<pre>';print_r($result);echo '</pre>';
        $object = [];
        foreach ($result as $index => $key) {
            $row = json_decode($key, true);
            $array = [];

            foreach ($row as $field => $value) {
                $array[$field] = $value;
            }

            $object[$index] = json_decode(json_encode($array), false);
        }

        return $object;
    }

    public function selectOne(string $tableName, string $exp = '', array $slots = []): ?object
    {
        $object = $this->select($tableName, $exp, $slots);
        return (count($object) == 1) ? $object[key($object)] : null;
    }

    /**
     * @throws \RedBeanPHP\RedException
     * @throws \Exception
     */
    public function update(string $tableName, int $update_id, array $values): bool
    {
        $this->db::begin();
        $this->db::ext('xdispense', function ($type) {
            return $this->db::getRedBean()->dispense($type);
        });

        $result = $this->db::load(TBL_PREFIX . $tableName, intval($update_id));

        foreach ($values as $field => $value) {
            if (strlen($value) > 0 || is_object($value)) {
                $result->{$field} = $value;
            }
        }

        try {

            $this->db::store($result);
            $this->db::commit();
            if(isset($_SESSION['registry'][$tableName])){
                unset($_SESSION['registry'][$tableName]);
            }
            return true;
        } catch (SQL $e) {
            $this->db::rollback();
            echo $e->getMessage();
            return false;
        }
    }

    public function prepare(string $data): string
    {
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $cipher, ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        return $ciphertext;
    }

    public function get(string $data)
    {
        if (is_string($data) && strlen($data) > 0) {
            $c = base64_decode($data);
            $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
            $iv = substr($c, 0, $ivlen);
            $hmac = substr($c, $ivlen, $sha2len = 32);
            $ciphertext_raw = substr($c, $ivlen + $sha2len);
            $plaintext = openssl_decrypt($ciphertext_raw, $cipher, ENCRYPTION_KEY, $options = OPENSSL_RAW_DATA, $iv);
            $calcmac = hash_hmac('sha256', $ciphertext_raw, ENCRYPTION_KEY, $as_binary = true);
            if (hash_equals($hmac, $calcmac)) {
                return $plaintext;
            }
        } else {
            return $data;
        }
    }


	/**
	 * Создаёт транзакцию при редактировании данных в базе данных.
	 *
	 * Создает запись в таблице ohs_transactions.
	 *
	 * @param string $tableName Имя таблицы, в которой идёт редактирование
	 * @param int    $row_id ID редактируемой записи
	 * @return array Ассоциативный массив в формате ['trans_id', 'user_name']
	 */
	public function transactionOpen(string $tableName, int $row_id): array
    {
        $busy = $this->selectOne(TBL_PREFIX . 'transactions', 'table_name = ? AND row_id = ?', [$tableName, $row_id]);

        if (intval($busy->user_id) == 0) {

            $trans = array(
                'table_name' => $tableName,
                'row_id' => $row_id,
                'user_id' => intval($this->_session['user_id']),
                'user_name' => $this->_session['user_fio']
            );
	        try {
		        $this->insert('transactions', $trans);
	        } catch (RedException $e) {
	            die(implode("<br>\n", $e));
	        }
	        return ['trans_id' => $this->last_insert_id, 'user_name' => $busy->user_name];
        }

        return [];
    }


	/**
	 * Закрывает транзакцию и удаляет запись из таблицы ohs_transactions.
	 *
	 * @param int $trans_id ID транзакции
	 */
	public function transactionClose(int $trans_id)
    {
        $this->db::trash(TBL_PREFIX . 'transactions', intval($trans_id));
    }

    public function parseFilterQuery($filter): array
    {
        if(!empty($filter)) {
            $filterArr = explode(';', $filter);
            $filterQuery = ' AND ';
            $filterSection = [];
            $filterFields = [];
            $filterSlots = [];


            //Листаем фильтруемые поля
            for ($f = 0; $f < count($filterArr); $f++) {
                //Листаем фильтруемые значения
                $filterValuesArr = explode(':', $filterArr[$f]);
                $filterValues = explode('|', $filterValuesArr[1]);
                $filterNames = [];
                $filterQueryArr = [];

                for ($v = 0; $v < count($filterValues); $v++) {

                    if ($filterValuesArr[0] === "m.age") {
                        switch (intval($filterValuesArr[1])) {
                            case 7318:
                                $filterQueryArr[] = "m.age >= '18'";
                                $filterSlots[] = [18];
                                break;
                            case 7319:
                                $filterQueryArr[] = "m.age >= '18' AND m.age <= '35'";
                                $filterSlots[] = [18, 35];
                                break;
                            case 7320:
                                $filterQueryArr[] = "m.age >= '36' AND m.age <= '55'";
                                $filterSlots[] = [36, 55];
                                break;
                            case 7321:
                                $filterQueryArr[] = "m.age >= '55'";
                                $filterSlots[] = [56];
                                break;
                        }
                    } else if ($filterValuesArr[0] === "a.claim_category") {
                        $filterQueryArr[] = "a.is_claim = '1' AND a.category = '" . intval($filterValues[$v]) . "'";
                        $filterSlots[] = [$filterValues[$v]];
                    } else if ($filterValuesArr[0] === "m.phone") {
                        $phone = str_replace(' 7', '+7', $filterValues[$v]);
                        $filterQueryArr[] = "m.phone = '" . $phone . "'";
                        $filterSlots[] = $phone;
                    } else {

                        if (substr_count($filterValuesArr[0], '_from') > 0) {
                            $filterNames[$filterValuesArr[0]] = $filterValues[$v];
                            $filterQueryArr[] = str_replace('_from', '', $filterValuesArr[0]) . " >= '" .
                                addslashes(urldecode($filterValues[$v])) . "'";
                        } elseif (substr_count($filterValuesArr[0], '_to') > 0) {
                            $filterQueryArr[] = str_replace('_to', '', $filterValuesArr[0]) . " <= '" .
                                addslashes(urldecode($filterValues[$v])) . "'";
                        } else {

                            $filterQueryArr[] = $filterValuesArr[0] . " = '" . addslashes(urldecode($filterValues[$v])) . "'";
                        }


                        $filterSlots[] = $filterValues[$v];
                        $filterFields[$filterValuesArr[0]][] = $filterValues[$v];
                    }


                }
                $filterSection[] = '(' . implode(' OR ', $filterQueryArr) . ')';
            }
            $filterQuery .= implode(' AND ', $filterSection);

            return ['filterQuery' => $filterQuery, 'filterSlots' => $filterSlots, 'filterArr' => $filterArr];
        }else{
            return [];
        }
    }
    
    public function addSign($docId){

    }

    public function getSigns($docId){

    }

    /**
     * Получение инофрмации о типах столбцов в таблице
     * @param string $table_name - имя таблицы без префикса
     * @return array|int|\RedBeanPHP\Cursor|NULL
     */
    public function getColumnTypes(string $table_name){
	    $out = [];
        $result = $this->db::getAll("SELECT column_name, data_type FROM
            information_schema.columns WHERE table_name = '" . TBL_PREFIX . $table_name . "'");

	    foreach($result as $index => $pair){
	        $out[$pair['column_name']] = $pair['data_type'];
        }
	    return $out;
    }

    /**
     * Проверяет, существует ли поле name в таблице через RedBean
     *
     * @param string $tableName Имя таблицы
     * @return bool
     */
    public function tableHasNameField(string $tableName, string $fieldName): bool
    {
        $columns = $this->db::inspect(TBL_PREFIX . $tableName);

        // Проверяем наличие поля '$fieldName' (регистронезависимо)
        foreach ($columns as $columnName => $columnInfo) {
            if (strtolower($columnName) === strtolower($fieldName)) {
                return true;
            }
        }

        return false;
    }

}