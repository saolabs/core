<?php

namespace Saola\Core\Files;

use Saola\Core\Magic\Any;

class Filemanager{
    use FileType, DirMethods, FileMethods, ZipMethods, FileConverter;
    protected static $_filemanager = null;
    public static function getInstance()
    {
        if(!static::$_filemanager){
            static::$_filemanager = new Filemanager();
        }
        return static::$_filemanager;
    }


    /**
     * khoi tao doi tuong
     * @param string $dir
     */
    function __construct($dir = null, $make_dir_if_not_exists = false)
    {
        $this->dirInit();
        $this->zipInit();
        $this->setDir($dir,$make_dir_if_not_exists);
    }



    /**
     * copy file hoac folder
     * @param string $src
     * @param string $dst
     */
    public function copy($src, $dst)
    {
        if(!$this->checkDirAccepted($src) || !$this->checkDirAccepted($dst)) return false;
        if(is_dir($src)) return $this->copyFolder($src, $dst, false, false);
        elseif(is_file($src)) return $this->copyFile($src, $dst);
    }

    /**
     * copy file hoac folder
     * @param string $src
     * @param string $dst
     */
    public function move($src, $dst, $list = [])
    {
        if(!$this->checkDirAccepted($src) || !$this->checkDirAccepted($dst)) return false;
        $s = true;
        if(is_dir($src)) {
            if(!is_dir($dst)) $this->mkdir($dst);
            $d = rtrim($dst, '/') . '/';
            if(!$list){
                $listItem = $this->getList($src);
                if($t = count($listItem)){
                    for ($i=0; $i < $t; $i++) { 
                        $file = $listItem[$i];
                        $nf = $d . $file->name;
                        if($file->type == 'file'){
                            if($this->copyFile($file->path, $nf)){
                                $this->delete($file->path);
                            }else{
                                $s = false;
                            }
                        }else{
                            if($this->copyFolder($file->path, $nf)){
                                $this->delete($file->path);
                            }else{
                                $s = false;
                            }
                        }
                    }
                }
                if($s){
                    $this->delete($src);
                }
            }
            elseif(is_array($list)){
                $ds = rtrim($src, '/') . '/';
                $d = rtrim($dst, '/') . '/';
                foreach ($list as $id => $filename) {
                    if(file_exists($fs = $ds . $filename)){
                        $nf = $d . $filename;
                        if(is_dir($fs)){
                            if(!$this->move($fs, $nf)) $s = false;
                        }else{
                            if($this->copyFile($fs, $nf)){
                                $this->delete($fs);
                            }else{
                                $s = false;
                            }
                        }
                    }
                }
            }
        }
        elseif(is_file($src)) {
            $fns = explode('/', $src);
            $filename = array_pop($fns);
            if(is_dir($dst)){
                
                if($this->copyFile($src, $nf = rtrim($dst, '/') . '/' . $filename)){
                    if($nf!=$src) $this->delete($src);
                }
                else{
                    $s = false;
                }
            }
            elseif($this->copyFile($src, $dst)){
                if($dst!=$src) $this->delete($src);
            }else{
                $s = false;
            }
        }else{
            $s = false;
        }
        return $s;
    }

    
    

    /**
     * full path
     * lấy dừng dẫn tuyệt đối của file hoạc thư mục
     * @param string $filename
     * @return string
     */
    public function getPath($filename = null)
    {
        $path = null;
        if($filename){
            if(!$this->checkDirAccepted($filename)) $path = $this->_dir . '/' . $filename;
            else $path = $filename;
        }
        else $path = $this->_dir . '/' . $this->_filename;

        return $path;
    }

    /**
     * gọi hàm theo dịnh dạng file
     * @param string $method
     * @param array $params
     */
    public function __call($method, $params)
    {
        $filename = null;
        $data = null;
        $mime = null;
        $action = null;
        $n = strtolower($method);
        $has_params = (is_array($params) && $t = count($params));
        // trường hợp 1: gọi hàm bằng get + phần mở rộng của file, 
        // ví dụ getHtml thì sẽ set định dạng html để chuẩn hóa dường dẫn rồi trả về nội dung file nenu61 file tồn tại
        if((substr($n,0, 3) == 'get') && ($info = $this->getMimeType(substr($n, 3)))){
            // nếu có tham số
            if($has_params){
                // tham số đầu tiên sẽ là tên file
                $filename = $params[0];
            }
            // set loại file
            $mime = $info->type;
            // set hành dộng
            $action = 'get';
        }
        // trường hợp 2: gọi hàm bằng save + phần mở rộng của file, 
        // ví dụ saveHtml thì sẽ set định dạng html để chuẩn hóa dường dẫn rồi thêm đuôi file nếu người dủng quên rồi lưu
        elseif((substr($n,0, 4) == 'save') && ($info2 = $this->getMimeType(substr($n, 4))))
        {
            if($has_params){
                if($t == 1){
                    // nếu chỉ có 1 tham số thì data sẽ là tham số đầu tiên
                    $data = $params[0];
                }else{
                    // nếu nhiều hôn thì tên file là tham số dầu tiên
                    $filename = $params[0];
                    // dữ liệu là tham số thứ 2
                    $data = $params[1];
                }
            }
            $mime = $info2->type;
            $action = 'save';
        }
        // trường hợp 2: gọi hàm bằng  phần mở rộng của file, 
        // ví dụ html thì sẽ set định dạng html để chuẩn hóa dường dẫn rồi thêm đuôi file nếu người dủng quên rồi lưu hoặc lấy dữ liệu
        elseif($info3 = $this->getMimeType($n)){
            if($has_params){
                if($t == 1){
                    // nếu tham số đầu tiên là chuỗi thì sẽ là file name và lấy data
                    if(is_string($params[0])){
                        $filename = $params[0];
                        $action = 'get';
                    }
                    // ngược lại là lưu với tham số đầu tiên là dữ liệu
                    else{
                        $data = $params[0];
                        $action = 'save';
                    }
                }
                // nếu có hơn 1 tham số thì chắc chắn sẽ lưu chử với tham số là filename và data
                else{
                    $filename = $params[0];
                    $data = $params[1];
                    $action = 'save';
                }
            }
            // nếu ko tham số sẽ là lấy dữ liệu theo dường dẫn và tên file dc set trc đó
            else{
                $action = 'get';
            }
            $mime = $info3->type;
        }

        if($action == 'get'){
            return $this->getContent($filename, $mime);
        }elseif($action == 'save'){
            return $this->save($filename, $data, $mime);
        }
        return $this;
    }

    
    
    /**
     * lưu file từ dử liệu base 64
     * @param string $base64 dữ liệu file được mã hóa base64
     * @param string $filenameWithoutExtension tên file không bao gồm phần mờ rộng
     * @param int $upload_by người upload
     * 
     * @return Saola\Core\agic\Arr|null
     */
    public function saveBase64($base64, $filenameWithoutExtension = null, $path = null)
    {
        if($file = $this->getBase64Data($base64)){
            if($path){
                $this->setDir($path);
            }
            $original = null;
            // neu co ten file cu
            if($file->filename){
                $original = $file->filename;
            }
            if($fn = $this->getFilenameWithoutExtension($filenameWithoutExtension)){
                $attachment = $fn;
            }elseif ($original) {
                $attachment = $this->getFilenameWithoutExtension($original).'-' . uniqid();
            }
            else{
                $attachment = str_slug(microtime(),'-');
            }
            $filename = $attachment.'.'. $file->extension;
            if($saveFile = $this->save($filename, $file->data, $file->extension)){
                return $saveFile;
            }
        }

        return false;
    }

    
    /**
     * lấy tên file ko có phần mở rộng
     * @param string $filenameWithoutExtension
     * @return string|null
     */
    public function getFilenameWithoutExtension($filenameWithoutExtension = null)
    {
        if($filenameWithoutExtension){
            $of = explode('.',$filenameWithoutExtension);
            $ext = array_pop($of);
            if($mime = $this->getMimeType($ext)){
                $filename = implode('.',$of);
            }else{
                $filename = $filenameWithoutExtension;
            }
            
            
            if($filename) return $filename;
        }
        return null;
    }

}