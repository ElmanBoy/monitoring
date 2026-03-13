<?php
namespace Core;

use Core\Db;
use Core\CryptoProHelper;

/**
 * Класс Auth.
 *
 * Отвечает за:
 * * авторизацию,
 * * хранение пользовательских настроек,
 * * хранение и соблюдение прав пользователей,
 * * проверку ajax запросов
 *
 * @package Core
 * @version 0.1
 */
class Auth
{

    private /*array*/ $_get, $_post, $_session, $_headers;
    private /*Db*/ $db;


    /**
     * Конструктор класса Auth.
     */
    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->_headers = getallheaders();
        $this->db = new Db();
    }

    /**
     * Метод утсановки значения извне для приватных параметров класса.
     * @param mixed $key Имя параметра
     * @param mixed $val Значение параметра
     * @return void Ничего не возвращает
     */
    public function set( $key, $val){
        $this->$key = $val;
    }

    /**
     * Метод получения из базы данных персональных настроек пользователя.
     * Например для получения настроек отображения таблиц.
     * @param integer $user_id ID пользователя
     * @return array Ассоциативный массив с настройками
     */
    public function getUserSettings(int $user_id): array
    {
        $settingsArr = [];

        $userSettings = $this->db->select('usersettings', ' where user_id = ?', [$user_id]);

        foreach ($userSettings as $setting){
            $settingsArr[] = json_decode($setting->settings, true);
        }

        return $settingsArr;
    }

    /**
     * @throws \RedBeanPHP\RedException
     */
    public function setUserSettings(array $user_settings){
        //Смотрим сохраненные настройки пользователя
        $oldSettings = $this->db->selectOne('usersettings', ' where user_id = ?',
            [intval($_SESSION['user_id'])]);
        $newSettings = [];
        //Если настройки уже есть...
        if (intval($oldSettings->id) > 0) {
            //Извлекаем в ассоциативный массив
            $newSettings = json_decode($oldSettings->settings, true);
            if(count($user_settings) > 0) {
                foreach($user_settings as $index => $set){
                    $newSettings[$set['name']] = $set['value'];
                    $_SESSION['user_settings'][$set['name']] = $set['value'];
                }
            }
        }

        $settings = array(
            'user_id' => $_SESSION['user_id'],
            'settings' => json_encode($newSettings)
        );

        //Если настройки уже есть...
        if (intval($oldSettings->id) > 0) {
            //Обновляем
            $this->db->update('usersettings', $oldSettings->id, $settings);
        } else {
            //Иначе, вставляем
            $this->db->insert('usersettings', $settings);
        }
    }


	/**
	 * Метод получения прав пользователя на основе роли.
	 * @param int $role_id ID роли пользователя
	 * @return array Ассоциативный массив с разрешениями по каждому модулю
	 */
	public function getUserPermissions(array $role_id): array
    {
        $perms = [];
        $userPerms = $this->db->select('roles', ' where id in ('.implode(', ', $role_id).')');

        //Если ролей несколько, то выбираем наилучшие права
        $rights = [];
            foreach ($userPerms as $id => $p) {
                $item = json_decode($p->permissions, true);
                foreach($item as $i) {
                    $rights[$i['module']] = [
                        'module' => $i['module'],
                        'view' => (intval($rights[$id]['view']) + intval($i['view'])) > 0 ? 1 : 0,
                        'edit' => (intval($rights[$id]['edit']) + intval($i['edit'])) > 0 ? 1 : 0,
                        'delete' => (intval($rights[$id]['delete']) + intval($i['delete'])) > 0 ? 1 : 0
                    ];
                }
                $perms = $rights;
            }

        return $perms;
    }

    /**
     * @param $input
     */
    public function loginByCertificate($input){
        $err = 0;
        // Подключение необходимых модулей
        if (!extension_loaded('php_CPCSP')) {
            die('Модуль КриптоПро CSP не установлен');
        }

        // Получение данных от фронтенда
        /*$input = json_decode($input, true);
        if (!$input) {
            http_response_code(400);
            die('Неверный формат данных');
        }*/
        /*require $_SERVER['DOCUMENT_ROOT'].'/core/Classes/CryptoHelper.php';
        $crypto = new CryptoHelper();*/

        // Проверка наличия всех необходимых полей
        /*if (!isset($input['challenge']) || !isset($input['signature']) || !isset($input['cert'])) {
            http_response_code(400);
            die('Отсутствуют обязательные поля');
        }

        $challenge = $input['challenge'];
        $signature = base64_decode($input['signature']);
        $certInfo = $input['cert'];*/
        try {
            /*// 1. Проверка ИНН
            if (!isset($certInfo['inn']) || !verifyInn($certInfo['inn'])) {
                throw new Exception('INN verification failed');
            }

            // 2. Открываем хранилище сертификатов
            $store = new CPStore();
            if (!$store->Open(CURRENT_USER_STORE, 'My', STORE_OPEN_READ_ONLY)) {
                throw new Exception('Failed to open certificate store');
            }

            // 3. Ищем сертификат по отпечатку
            $certs = $store->get_Certificates();
            $cert = null;

            foreach ($certs as $c) {
                if (strtolower($c->get_Thumbprint()) === strtolower($certInfo['thumbprint'])) {
                    $cert = $c;
                    break;
                }
            }

            if (!$cert) {
                throw new Exception('Certificate not found in store');
            }

            // 4. Проверяем срок действия сертификата
            if (time() > $cert->get_ValidToDate()) {
                throw new Exception('Certificate has expired');
            }

            // 5. Проверяем subjectName (опционально)
            if (strpos($cert->get_SubjectName(), $certInfo['subjectName']) === false) {
                throw new Exception('Certificate subject name mismatch');
            }

            // 6. Создаем объект для проверки подписи
            $signedData = new CPSignedData();
            $signedData->set_Content($challenge);

            // 7. Проверяем подпись
            $verifyResult = $signedData->VerifySignature($signature, $cert);
            if (!$verifyResult) {
                throw new Exception('Signature verification failed');
            }

            // Если все проверки пройдены
            $response = [
                'success' => true,
                'message' => 'Verification successful',
                'certInfo' => [
                    'subject' => $cert->get_SubjectName(),
                    'issuer' => $cert->get_IssuerName(),
                    'validFrom' => date('Y-m-d', $cert->get_ValidFromDate()),
                    'validTo' => date('Y-m-d', $cert->get_ValidToDate())
                ]
            ];

            echo json_encode($response);*/
            require $_SERVER['DOCUMENT_ROOT'].'/core/Classes/CryptoProHelper.php';
            $crypto = new CryptoProHelper();
            $certData = base64_decode($input['certData']);
            print_r($crypto->Verify($input));
        }catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function loginByInn($input){
        $result = $this->db->selectOne('users', ' where inn = ?', [$_POST['cert']['inn']]);
        if(strlen(trim($result->inn)) > 0){
            return $this->login($result->login, '0000', $result->password);
        }else{
            return ['result' => false, 'message' => 'Пользователь с ИНН '.$_POST['cert']['inn'].' не найден'];
        }
    }


	/**
	 * Метод для авторизации пользователя.
	 * @param string $login Логин пользователя
	 * @param string $password Пароль пользователя
	 * @return bool TRUE в случае успешной авторизации, иначе FALSE
	 */
	public function login(string $login, string $password, string $pswd = ''): array
    {
        $answer = [];

        if(strlen(trim($login)) > 0 && strlen(trim($password)) > 0){

            $pass = str_replace("$1$", "", crypt(md5($_POST['password']), '$1$'));
            $login = mb_strtolower($login);

            $result = $this->db->selectOne('users', " where LOWER(login) = ?", [$login]);

            if ($result->locked_until && strtotime($result->locked_until) > time()) {
                $remainingTime = strtotime($result->locked_until) - time();
                $lockedUntil = strtotime($result->locked_until);
                $remainingTime = $lockedUntil - time(); // Оставшееся время в секундах
                $answer = ['result' => false,
                    'message' => 'Учетная запись заблокирована. Попробуйте снова через <span id="login_countdown">' .
                        gmdate('i:s', $remainingTime).'</span>
                    <script>el_app.loginCountdown('.$remainingTime.');</script>'];
            }elseif($result != null && ($pass == $result->password || $pswd == $result->password)){
                // Сбрасываем счетчик попыток и разблокируем пользователя
                $this->db->update('users', intval($result->id),
                    ['login_attempts' => 0, 'locked_until' => NULL]);

                $roles = json_decode($result->roles);

                $this->_session['login'] = $result->login;
                $this->_session['user_id'] = $result->id;
                $this->_session['user_name'] = $result->name;
                $this->_session['user_surname'] = $result->surname;
                $this->_session['user_fio'] = $result->surname . ' ' . $result->name . ' ' .$result->middle_name;
                $this->_session['user_middle_name'] = $result->middle_name;
                $this->_session['user_roles'] = $result->roles;
                $this->_session['user_institution'] = $result->institution;
                $this->_session['user_ousr'] = $result->ousr;
                $this->_session['user_division'] = $result->division;
                $this->_session['user_position'] = $result->position;
                $this->_session['user_active'] = $result->active;
                $this->_session['user_email'] = $result->email;
                $this->_session['user_phone'] = $result->phone;
                $this->_session['user_innerPhone'] = $result->inner_phone;
                $this->_session['user_subordinates'] = @$result->subordinates;
                $this->_session['user_settings'] = $this->getUserSettings($result->id);
                $this->_session['user_permissions'] = $this->getUserPermissions($roles);
                $_SESSION = $this->_session;
                $answer = ['result' => true,
                    'message' => 'Добро пожаловать,  ' . $this->_session['user_fio']];
            }else{

                // Увеличиваем счетчик попыток
                $loginAttempts = $result->login_attempts + 1;
                $this->db->update('users', intval($result->id), ['login_attempts' => $loginAttempts]);

                // Если попыток больше 3, блокируем пользователя
                if ($loginAttempts >= 3) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $this->db->update('users', intval($result->id), ['locked_until' => $lockedUntil]);
                    $answer = ['result' => false,
                        'message' => 'Неверный логин или пароль. Учетная запись заблокирована на 5 минут.'];
                }else {
                    $answer = ['result' => false,
                        'message' => 'Неверный логин или пароль. Осталось попыток: ' . (3 - $loginAttempts)];
                }
            }
        }else{
            $answer = ['result' => false, 'message' => 'Укажите лоин и пароль'];
        }

        return $answer;
    }


	/**
	 * Завершение сессии пользователя.
	 * @return bool
	 */
	public function logout(): bool
    {
        session_abort();
    }


	/**
	 * Метод проверки наличия авторизации пользователя.
	 * @return bool TRUE если авторизован, иначе FALSE
	 */
	public function isLogin(): bool
    {
        if(!isset($this->_session['login'])){
            return false;
        }
        return true;
    }


	/**
	 * Метод генерирует токен для HTTP заголовка X-Csrf-Token.
	 * В целях защиты от CSRF-атак.
	 * @return string
	 * @throws \Exception
	 */
	public function buildToken(): string
    {
        $phpv = explode('.', phpversion());
        $token = '';
        if(intval($phpv[0]) == 5 && intval($phpv[1]) >= 3){
            if (function_exists('mcrypt_create_iv')) {
                $token = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
            } else {
                $token = bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
        if(intval($phpv[0]) >= 7){
            $token = bin2hex(random_bytes(32));
        }
        return $token;
    }


	/**
	 * Метод для проверки AJAX-запросов.
	 *
	 * @return bool TRUE если запрос сделан с помощью AJAX, иначе FALSE
	 */
	public function checkAjax(): bool
    {
        return ($this->isLogin() && intval($this->_post['ajax']) == 1
            && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ||
                strtolower($this->_headers['x-requested-with']) == 'xmlhttprequest')
            && $_SESSION['csrf-token'] == getallheaders()['x-csrf-token']);
    }


	/**
	 * Метод для быстрого получения разрешений текущего пользователя в указанном модуле.
	 * @param int $module_id ID модуля
	 * @return array Ассоциативный массив с разрешениями
	 */
	public function checkModulePermissions(int $module_id): array
	{
        return isset($this->_session['user_permissions']) ? $this->_session['user_permissions'][$module_id] : [];
    }

    /**
     * Метод для быстрого получения разрешений текущего пользователя в текущем модуле.
     * @return array Ассоциативный массив с разрешениями
     */
    public function getCurrentModulePermission(): array
    {
        $path = $_GET['url'] == '' ? $_COOKIE['last_path'] : $_POST['url'];
        if($path == ''){
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        $gui = new \Core\Gui();
        $module_props = $gui->getModuleProps(str_replace('/', '', $path));
        return $this->checkModulePermissions($module_props['id']);
    }


	/**
	 * Метод определяет дефолтную страницу, которую нужно показать пользователю, если она не указана явно.
	 * Например, если пользователь после домена в URL не указывает ничего и у него есть доступ в модуль Main, то метод
	 * вернет строку "main"
	 * @return string
	 */
	public function getDefaultPage(): string
    {
        $last_path = preg_replace('/^\/+|\/+$/', '', $_COOKIE['last_path']);//$_SESSION['login_path']
        //Если модуль Данные не разрешен на просмотр, то выбираем первый доступный модуль
        /*foreach($this->_session['user_permissions'] as $id => $p){
            if($p['view']){
                $path = $this->db->selectOne('modules', ' where id = ? order by id', [intval($p['module'])]);
                $default_page = str_replace('/', '', $path->path);
                if(is_file($_SERVER['DOCUMENT_ROOT'] . '/modules/'.$default_page.'/pages/index.php')) {
                    return $path->path;
                }
            }
        }*/

        return strlen($last_path) > 0 ? $last_path : 'dashboard';
    }

    public function isAdmin(): bool
    {
        return $this->haveUserRole(1);
    }

    public function haveUserRole( int $role_id): bool
    {
        if(substr_count($_SESSION['user_roles'], ',')) {
            return in_array($role_id, explode(',', $_SESSION['user_roles']));
        }elseif (substr_count($_SESSION['user_roles'], '[')) {
            return in_array($role_id, json_decode($_SESSION['user_roles']));
        }else{
            return $_SESSION['user_roles'] == $role_id;
        }
    }

    /**
     * Пересчитывает права пользователя из БД и обновляет сессию.
     * Вызывается при каждой загрузке страницы (GET-запрос),
     * чтобы изменения ролей вступали в силу без перелогина.
     */
    public function refreshPermissions(): void
    {
        $userId = intval($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            return;
        }

        $user = $this->db->selectOne('users', ' WHERE id = ?', [$userId]);
        if (!$user) {
            return;
        }

        // Роли могут быть в виде "1", "1,2" или "[1,2]"
        $rolesRaw = $user->roles ?? '';
        if (substr_count($rolesRaw, '[') > 0) {
            $roles = json_decode($rolesRaw, true);
        } elseif (substr_count($rolesRaw, ',') > 0) {
            $roles = array_map('intval', explode(',', $rolesRaw));
        } else {
            $roles = [intval($rolesRaw)];
        }
        $roles = array_filter($roles); // убираем нули

        if (empty($roles)) {
            return;
        }

        $newPerms = $this->getUserPermissions($roles);
        $_SESSION['user_permissions'] = $newPerms;
        $_SESSION['user_roles']       = $user->roles;
        $this->_session['user_permissions'] = $newPerms;
    }
}