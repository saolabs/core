<?php
namespace Saola\Core\Files;

use Saola\Core\Engines\Helper;
use Saola\Core\Magic\Any;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

trait ZipMethods{
    protected $_zip = null;
    public function zipInit()
    {
        $this->_zip = new ZipArchive();
    }

    /**
     * giải nén file 
     * @param string $filename
     * @param string $dir
     * 
     * @return boolean 
     */
    public function extract($filename = null, $dir = null, $list = [])
    {
        // lấy dừng dẫn tuyệt đối và kiểm tra xem tập tin có tồn tại hay ko?
        if(!file_exists($path = $this->parseFilename($filename))) return false;

        // kiểm tra tập tin xem có phải là file zip hay ko
        if($this->getType($filename)!='application/zip') return false;
        // kiểm tra và chỉnh sửa dường dẫn giải nén
        if(!$dir) $dir = $this->_dir.'/'.str_slug(preg_replace('/\.'.$this->_extension.'$/i', '', $this->_filename));
        elseif(!$this->checkDirAccepted($dir)) $dir = Helper::publicPath($dir);
        // tạo dường dẫn nếu ko tồn tại
        
        if(!is_dir($dir)){
            $this->makeDir($dir, 0755);
        };
        if(!is_dir($dir)) return false;
        // đọc file và giải nén
        $zip = $this->_zip;
        if ($zip->open($path) === TRUE) {
            // kiểm tra xem có danh savh1 file yêu cầu ko
            if(is_array($list) && count($list)){
                // nếu có thì chĩ giải nén các file theo yêu cầu
                $zip->extractTo($dir, $list);
            }
            else{
                // nếu không có thì giải nén tất cả
                $zip->extractTo($dir);
            }
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * giải nén đến thư mục
     * @param string $dir
     * @param array $list
     * 
     * @return boolean
     * 
     */
    public function extractTo($dir = null, $list = [])
    {
        return $this->extract(null, $dir, $list);
    }


    /**
     * nén thư mục
     * @param string $dir Dường dẫn thư mục
     * @param string $archive_file tên/dường dẫn file nén
     * @param array
     * 
     * @return boolean
     */
    public function addArchive($dir=null, $archive_file=null, $list = [])
    {
        $zip_dir = null;
        $archive_path = null;
        if($dir){
            $isdir = is_dir($dir);
            if($isdir && $this->checkDirAccepted($dir)) $zip_dir = $dir;
            else $zip_dir = $this->_dir . '/' . $dir;
        }elseif(is_dir($this->_dir)) $zip_dir = $this->_dir;
        else return false;

        if(!$archive_file){
            $a = explode('/', str_replace("\\", "/", $zip_dir));
            $archive_file = array_pop($a);
        }
        if($this->checkDirAccepted($archive_file)) $archive_path = $archive_file;
        else $archive_path = Helper::publicPath($archive_file);
        if(!preg_match('/\.zip$/i', $archive_file)) $archive_path.='.zip';
        $zip = $this->_zip;

        $zip->open($archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** 
         * @var SplFileInfo[] $files 
         * 
         */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($zip_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file)
        {
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($zip_dir) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
        return true;
    }
}