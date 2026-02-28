<?php
namespace Core;

use Core\Db;
use Core\Gui;
use Core\Registry;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RedBeanPHP\RedException\SQL;
use Throwable;

class Cache
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
     * @var \Core\Registry
     */
    public $reg;
    /**
     * @var \Core\Gui
     */
    private $gui;
    /**
     * @var string
     */
    private $cacheDir;

    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_session = $_SESSION;
        $this->_server = $_SERVER;
        $this->_cookie = $_COOKIE;
        $this->rb = new R();
        $this->db = new Db();
        $this->gui = new Gui();
        $this->reg = new Registry();

        $this->cacheDir = $_SERVER['DOCUMENT_ROOT'].'/cache';
        if(!is_dir($this->cacheDir)){
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function makeSubDir(string $type, int $userId): string
    {
        $subDir = $type.'/'.$userId;
        if(!is_dir($this->cacheDir.'/'.$subDir)){
            mkdir($this->cacheDir.'/'.$subDir, 0755, true);
        }
        return $this->cacheDir.'/'.$subDir;
    }

    public function saveToCache(string $data, string $type, int $userId, string $fileId, string $extension = 'html'){
        $ext = $extension != '' ? '.'.$extension : '';
        $path = $this->makeSubDir($type, $userId).'/'.$fileId.$ext;
        file_put_contents($path, $data);
    }

    public function deleteFromCache(string $type, int $userId, int $fileId){
        $path = $this->makeSubDir($type, $userId).'/'.$fileId.'.html';
        @unlink($path);
    }

}

