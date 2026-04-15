<?php

namespace Saola\Core\atabase;

use PDO;
use PDOException;

class MyAdmin
{
    /**
     * host
     *
     * @var string
     */
    protected $host = 'localhost';
    /**
     * user
     *
     * @var string
     */
    protected $user = 'root';
    /**
     * pass
     *
     * @var string
     */
    protected $pass = '';
    /**
     * pdo object
     *
     * @var PDO
     */
    protected $db = null;

    public $errorMessage = '';

    protected $_chevked = false;

    protected $_isConnected = false;

    /**
     * tạo đối tượng
     *
     * @param string|array $configHost
     * @param string $user
     * @param string $pass
     */
    function __construct($configHost = null, $user = null, $pass = '', $doConnect = null)
    {
        if($configHost){
            $this->config($configHost, $user, $pass);
            if($doConnect !== false) $this->connect();
        }
    }

    /**
     * ket61 noi61 db
     *
     * @param string|array $host
     * @param string $user
     * @param string $pass
     * @return void
     */
    public function config($host, $user = null, $pass = null)
    {
        if(is_array($host)){
            if(isset($host['host'])) $this->host = $host['host'];
            if(isset($host['user'])) $this->user = $host['user'];
            elseif(isset($host['username'])) $this->user = $host['username'];
            if(isset($host['pass'])) $this->pass = $host['pass'];
            elseif(isset($host['password'])) $this->pass = $host['password'];
        }
        else {
            if($host) $this->host = $host;
            if($user) $this->user = $user;
            if($pass) $this->pass = $pass;
        }
    }
    
    
    public function connect()
    {
        if($this->_chevked) return $this->_isConnected;
        $this->_chevked = true;
        try {
            
            $this->db = new PDO("mysql:host=$this->host", $this->user, $this->pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $this->_isConnected = true;
        } catch (PDOException $th) {
            $this->errorMessage = $th->getMessage();
        }
        return $this->_isConnected;
    }

    /**
     * thực thi lệnh sql
     *
     * @param string $query
     * @return int|bool
     */
    public function exec($query)
    {
        $this->connect();
        if(!$this->db) return 0;
        try {
            $affected = $this->db->exec($query);
            if ($affected === false) {
                $err = $this->db->errorInfo();
                if ($err[0] === '00000' || $err[0] === '01000') {
                    return true;
                }
            }else{
                $affected = 1;
            }
        } catch (PDOException $th) {
            $this->errorMessage = $th->getMessage();
            $affected = 0;
        }
        
        
        return $affected;
    }
    
    /**
     * tao5 database
     *
     * @param string $dbName
     * @param string $collate
     * @return int
     */
    public function createDatabase($dbName, $collate = 'utf8')
    {
        $this->connect();
        if(!$dbName || !$this->db) return false;
        $query = "CREATE DATABASE $dbName CHARACTER SET $collate COLLATE {$collate}_general_ci;";
        return $this->exec($query);
    }

    /**
     * drop database
     *
     * @param string $dbName
     * @return int
     */
    public function dropDatabase($dbName)
    {
        $this->connect();
        if(!$dbName || !$this->db) return false;
        // $query = "DROP DATABASE [IF EXISTS] $dbName;";
        $query = "DROP DATABASE $dbName;";
        return $this->exec($query);
    }


    /**
     * tao5 user
     *
     * @param string $user
     * @param string $pass
     * @param array|string $dbs
     * @return int
     */
    public function createUser($user, $pass = '', $dbs = null)
    {
        $this->connect();
        if(!$this->db || !$user) return false;
        $query = "CREATE USER '$user'@'%' IDENTIFIED BY '$pass'";
        $rows = $this->exec($query);
        if($rows && $dbs){
            $this->grant($user, $dbs);
        }
        return $rows;
    }

    /**
     * drop user
     *
     * @param string $user
     * @return int
     */
    public function dropUser($user)
    {
        $this->connect();
        if(!$this->db || !$user) return false;
        $query = "DROP USER '$user'@'%';";
        $rows = $this->exec($query);
        return $rows;
    }


    
    /**
     * gán quyền
     *
     * @param string $user
     * @param string|array $dbs
     * @return int
     */
    public function grant($user = null, $dbs = null)
    {
        $this->connect();
        if(!$this->db || !$user) return 0;
        if(!$dbs) return 0;
        if(is_string($dbs)){
            $dbs = explode(',', str_replace(' ', '', $dbs));
        }
        if(!is_array($dbs)) return 0;
        $rows = 0;
        for ($i=0; $i < count($dbs); $i++) { 
            $db = $dbs[$i];
            $a = $this->exec("GRANT ALL PRIVILEGES ON `$db`.* TO '$user'@'%';");
            $rows+=$a;
        }
        return $rows;
    }

    public function dbExists($db = null)
    {
        if(!$db || !is_string($db)) return false;
        try {
            $dbc = new PDO("mysql:host=$this->host;dbname=$db", $this->user, $this->pass);
            return true;
        } catch (PDOException $th) {
            return false;
        }
    }

    public function tableExists($db, $table)
    {
        if(!$this->connect()) return false;
        $query = "SELECT * 
        FROM information_schema.tables
        WHERE table_schema = '$db' 
            AND table_name = '$table'
        LIMIT 1";
        $rows = $this->db->exec($query);
        return $rows;
    }
    /**
     * @var string
     */
    public $dugError = '';
    /**
     * Tad database va gan quyen user
     * @param string $database ten csdl
     * @param string $user ten user
     * @param string $password mat khau
     * @return bool
     */
    public function createDUG($database, $user, $password)
    {
        $this->connect();
        if (!($createUser = $this->createDatabase($database))) {
            $this->dugError = 'Lỗi hệ thống không thể tạo database';
        } elseif (!($createDatabase = $this->createUser($user, $password))) {
            $this->dugError = 'Không thể khởi tạo user (' .('SQLSTATE[HY000]: General error: 1819 Your password does not satisfy the current policy requirements' == $this->errorMessage ?'Mật khẩu không an toàn': $this->errorMessage) .')';
            if(!$this->dropDatabase($database)){
                // throw new Exception("Không thể xóa database");
            }
        } elseif (!($grant = $this->grant($user, $database))) {
            $this->dropDatabase($database);
            $this->dropUser($user);
            $this->dugError = 'Không thể gán quyền cho user';
        } else {
            return true;
        }
        return false;
    }

    public function __get($name)
    {
        $this->connect();
        $n = strtolower($name);
        if($n == 'isconnected') return $this->_isConnected;
        return null;
    }
    public function __set($name, $value)
    {
        return $value;
    }
}
