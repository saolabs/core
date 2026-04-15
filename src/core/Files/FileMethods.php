<?php

namespace Saola\Core\Files;

use Saola\Core\Magic\Arr;

use Exception;

trait FileMethods{

    protected $_filename = null;

    protected $_content = null;

    protected $_filetype = null;

    protected $_extension = null;


    /**
     * chõn file để đọc hoặc ghi
     *
     * @param string $filename
     *
     * @return object $instance
     */
    public function setFile($filename)
    {
        $this->_filename = $filename;
        $this->_filetype = null;
        $this->_filedata = null;

        if(file_exists($path = $this->getPath($filename))){
            $this->_filetype = $this->getType($filename);
            if($info = $this->getMimeType($this->_filetype)){
                $this->_extension = $info->extension;
            }
        }
        return $this;
    }

    /**
     * chõn file để đọc hoặc ghi
     *
     * @param string $filename
     *
     * @return object $instance
     */
    public function file($filename)
    {
        $f = clone $this;
        $f->setFile($filename);
        return $f;
    }


    /**
     * lấy loại file trên server
     * @param string $filename
     *
     * @return string
     */
    public function getType($filename = null)
    {
        // return $this->getPath($filename);
        return file_exists($path = $this->getPath($filename))?mime_content_type($path):null;
    }

    public function info($filename = null)
    {
        if(file_exists($path = $this->getPath($filename)) && is_file($path)){
            $mime = mime_content_type($path);
            $size = filesize($path)/1024;
            $tType = $this->getMimeType($mime);
            $a = explode('/', $mime);
            $type = $a[0];
            return new Arr([
                'isFile' => true,
                'ext' => $tType->extension,
                'size' => $size,
                'mime' => $mime,
                'type' => $type
            ]);
        }
        return null;
    }

    /**
     * lấy Thông tin size
     * @param string $filename
     *
     * @return int
     */
    public function size($filename = null)
    {
        // return $this->getPath($filename);
        return file_exists($path = $this->getPath($filename))?filesize($path)/1024:0;
    }



    /**
     * thiết lập định dạng file
     * @param string $type
     */
    public function setType($type)
    {
        if($info = $this->getMimeType($type)){
            $this->_extension = $info->extension;
            $this->_filetype = $type;
        }
        return $this;
    }

    /**
     * thiết lập hoặc lấy loại tệp tin
     * @param string $type
     * @return mixed
     */
    public function type($type = null)
    {
        return is_null($type)?$this->getType():$this->setType($type);
    }
    /**
     * lấy nội dung file
     * @param string $filename
     * @param boolean $mime_type
     *
     * @return string
     */
    public function getContent($filename = null, $mime_type = null)
    {
        $f = $this->parseFilenameByType($filename, $mime_type);

        if(file_exists($f)){
            return file_get_contents($f);
        }
        return null;
    }

    /**
     * cài đặt nội dung
     * @param mixed $content
     * @return object $instance
     */
    public function setContent($content = null)
    {
        $this->_content = $content;
        return $this;
    }



    /**
     * chèn nội dung hoặc lấy nội dung
     * @param mixed $content
     *
     * @return mixed ($instance | $content)
     *
     */
    public function content($content = null)
    {
        return is_null($content)?$this->getContent():$this->setContent($content);
    }
    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  bool  $lock
     * @return int|bool
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }
    /**
     * lưu file
     * @param string $filename
     * @param mixed $content
     * @param string $mime_type là định dạng hoặc loại tập tin
     * @return File
     */

    public function save($filename = null, $content = null, $mime_type = null)
    {
        $f = $this->parseFilenameByType($filename, $mime_type);
        // lấy nội dung
        $c = is_null($content)?$this->_content:$content;

        // lưu nội dung filw
        // fopen($f, 'w');
        // fwrite($f, $c);
        // fclose($f);
        $a = explode('/', $f);
        $b = array_pop($a);
        $d = implode('/', $a);
        if(!is_dir($d)){
            $this->makeDir($d, 0755,true);
        }
        $this->put($f, $c);
        $return_value = false;
        // nếu lưu thành ông
        if(file_exists($f)){
            $ext = pathinfo($f, PATHINFO_EXTENSION);

            $size = filesize($f)/1024;
            if($info = $this->getMimeType($ext)){
                $data = [
                    'filename' => $this->_filename,
                    'path' => $f,
                    'type' => $info->type,
                    'extension' => $info->extension,
                    'size' => $size
                ];
            }else{
                $data = [
                    'filename' => $this->_filename,
                    'path' => $f,
                    'type' => $this->_filetype,
                    'extension' => $this->_extension,
                    'size' => $size
                ];
            }
            // trả về đối tượng any
            $return_value = new Arr($data);
        }
        return $return_value;
    }

    public function prepend($content = null, $filename = null)
    {
        if(!is_null($content)){
            $content.= $this->getContent($filename);
            $this->save($filename, $content);
            return true;
        }
    }

    public function append($content = null, $filename = null)
    {
        if(!is_null($content)){
            $content = $this->getContent($filename).$content;
            $this->save($filename, $content);
            return true;
        }
    }


    public function copyFile($src, $dst)
    {
        if(is_string($src) && is_string($dst) && $src != $dst){
            if(!$this->checkDirAccepted($src)) $src = $this->joinPath($this->_dir,$src);
            if(!$this->checkDirAccepted($dst)) $dst = $this->joinPath($this->_dir,$dst);
            if(file_exists($src) && is_file($src) && !is_dir($dst)){
                $a = explode('/', $dst);
                $f = array_pop($a);
                $d = implode('/', $a);
                if(!is_dir($d)){
                    $this->makeDir($d, 0755);
                }
                if(copy($src, $dst) && file_exists($dst) ){
                    return true;
                }
            }

        }
        return false;
    }

    /**
     * chuẩn hóa file và đừng dẩn trước khi lưu
     * @param string $filename
     * @return string realpath of file
     */
    protected function parseFilename($filename = null)
    {
        $name = $filename?$filename:$this->_filename;
        if(!$name) return false;
        $current_dir = $this->_dir;
        $filepath = $this->getPath($name);
        $pp = explode('/', $filepath);
        $fn = array_pop($pp);
        $dir = implode('/', $pp);



        $stt = $this->setDir($dir, true);
        if($this->_filetype){
            if($info = $this->getMimeType($this->_filetype)){
                $ext = $info->extension;
                if(!preg_match('/\.'.$ext.'$/i', $fn)){
                    $fn .= '.'.$ext;
                }
            }
        }else{
            $fs = explode('.', $fn);
            if(($t = count($fs))>=2){
                if($fs[$t-1]){
                    if($info = $this->getMimeType($fs[$t-1])){
                        $this->_filetype = $info->type;

                    }
                }
                $this->_extension = $fs[$t-1];
            }
        }
        $this->_filename = $fn;
        $path = $this->getPath($fn);
        return $path;
    }

    /**
     * chuẩn hóa tên file theo định dạng
     *
     * @param string $filename
     * @param string $mime_type
     *
     * @return string path of file
     */
    public function parseFilenameByType($filename = null, $mime_type = null)
    {
        $current_dir = $this->_dir;
        $old_type = $this->_filetype;
        if($mime_type && $info = $this->getMimeType($mime_type)){
            $this->_filetype = $info->type;
        }
        // chuẩn hóa tên file và lấy dường dẫn
        $f = $this->parseFilename($filename);
        // reset lại thông tin thiết lập
        $this->_filetype = $old_type;
        $this->_dir = $current_dir;
        return $f;
    }

    /**
     * kiểm tra file có tồn tại hay ko
     * @param string $filename
     * @param string $mime_type
     *
     * @return boolean
     */

    public function exists($filename = null, $mime_type = null)
    {
        $path = $this->parseFilenameByType($filename, $mime_type);
        return file_exists($path);
    }

    /**
     * xóa file
     * @param string $filename
     *
     * @return boolean
     */
    public function deleteFile($filename = null)
    {
        if(file_exists($f = $this->getPath($filename))){
            return unlink($f);
        }
        return false;
    }

    /**
     * lấy nội dung file json và chuyển về mảng
     * @param string $filename
     * @param boolean $convert_to_array_object
     *
     * @return array|Arr
     *
     */
    public function getJson($filename = null, $convert_to_array_object = false)
    {
        $path = $this->parseFilenameByType($filename, 'json');

        $data = [];
        if(file_exists($path)){

            try {
                $content = file_get_contents($path);
                $json = json_decode($content, true);
                // $data = Arr::parse($json);
                if($json && $convert_to_array_object){
                    $data = new Arr($json);
                }
                else{
                    $data = $json;
                }
            } catch (Exception $e) {
                //throw $th;
            }
        }
        return $data;
    }

    /**
     * lưu file json
     * @param string $filename
     * @param array $data
     * @return boolean
     */
    public function saveJson($filename = null, $data = null)
    {
        if(is_array($data) || is_object($data)){
            return $this->save($filename, json_encode($data, JSON_PRETTY_PRINT), 'json');
        }
        return false;
    }

    /**
     * hàm lấy hoặc lưu dữ liệu json
     * @param string $filename
     * @param mixed $data
     */
    public function json($filename = null, $data = null)
    {
        if(is_array($filename) || is_object($filename)){
            return $this->saveJson(null, $filename);
        }elseif(is_array($data) || is_object($data)){
            return $this->saveJson($filename, $data);
        }elseif (is_bool($filename)) {
            return $this->getJson(null,$filename);
        }else {
            return $this->getJson($filename, is_bool($data)?$data:false);
        }
    }


    /**
     * lấy nội dung dc mã hóa
     * @param string $filename
     *
     * @return mixed
     */
    public function getSerialize($filename = null)
    {
        $path = $this->parseFilenameByType($filename, 'ser');
        $data = null;
        if(file_exists($path)){
            try {
                $content = file_get_contents($path);
                $data = unserialize($content);
            } catch (Exception $e) {
                //throw $th;
            }
        }
        return $data;
    }
    public function getSer($filename = null)
    {
        return $this->getSerialize($filename);
    }

    /**
     * lưu file serialize
     * @param string $filename
     * @param array $data
     * @return boolean
     */
    public function saveSerialize($filename = null, $data = null)
    {
        return $this->save($filename, serialize($data), 'ser');
    }
    public function saveSer($filename = null, $data = null)
    {
        return $this->saveSerialize($filename, $data);
    }

    /**
     * hàm lấy hoặc lưu dữ liệu json
     * @param string $filename
     * @param mixed $data
     */
    public function serialize($filename = null, $data = null)
    {
        if(is_null($data)) return $this->getSerialize($filename);
        return $this->saveSerialize($filename, $data);
    }

    public function ser($filename = null, $data = null)
    {
        return $this->serialize($filename, $data);
    }


}
