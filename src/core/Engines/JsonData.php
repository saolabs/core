<?php
namespace Saola\Core\Engines;

use Saola\Core\Files\Filemanager;

Class JsonData{

    protected $filemanager;

    public function __construct()
    {
        $this->filemanager = new Filemanager(json_path());
    }

    /**
     * lay Thông tin file cache hoac json
     * @param string $file ten file hoac sub path khong chua phan mo rong
     * @param array|null
     */
    public function getData(string $file)
    {
        return $this->getJsonData($file);
        // if($d = $this->getStorageData($file)){
        //     return $d;
        // }else{
        //     return $this->getJsonData($file);
        // }
    }
    
    /**
     * check storage data
     * @param string $file duong dan khong chua phan mo rong
     * @return bool
    */
    public function checkStorageData($file)
    {
        return file_exists(storage_path('crazy/'. ltrim($file,'/').'.php'));
    }

    /**
     * lấy data dc lưu
     * @param string $file duong dan khong chua phan mo rong
     * @return array
     */
    public function getStorageData($file)
    {
        if($cachePath = $this->checkConvertStorageDataCache($file)){
            return require $cachePath;
        }
        $file = ltrim($file,'/');
        if(file_exists($path = storage_path('crazy/data/'. $file.'.php'))){
            $data = require $path;
        }
        else{
            $data = [];
        }
        return $data;
    }

    

    /**
     * check json data
     * @param string $file duong dan khong chua phan mo rong
     * @return bool
    */
    public function checkJsonData($file)
    {
        return file_exists(json_path($file.'.json'));
    }

    /**
     * lấy data dc lưu
     * @param string $file duong dan khong chua phan mo rong
     */
    public function getJsonData($file)
    {
        // $path = json_path($file.'.json');
        
        if(file_exists($path = json_path($file.'.json'))){
            $data = json_decode(file_get_contents($path),true);
        }
        else{
            $data = [];
        }
        return $data;
    }

    public function checkConvertStorageDataCache($file)
    {
        $file = ltrim($file,'/');
        if(file_exists($json_path = json_path($file.'.json'))){
            $time = filemtime($json_path);
            $php_filename = md5($file.'-'.$time).'.php';
            $path = storage_path('crazy/cache/'.$php_filename);
            if(file_exists($path)){
                return $path;
            }elseif($this->filemanager->convertJsonToPhp($json_path, $path)){
                return $path;
            }
        }
        return null;
        
    }
}