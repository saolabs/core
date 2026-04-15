<?php

namespace Saola\Core\Files;
use Saola\Core\Magic\Arr;

/**
 * File
 * @property string $name
 * @property string $filename
 * @property string $path
 * @property string $extension
 * @property string $type
 * @property float $size
 */
class File {
    use FileType;
    protected $_filename = null;
    protected $_filetype = null;
    protected $_extension = null;
    protected $_content = null;
    protected $_old_content = null;
    protected $_size = null;
    protected $_mime = null;
    protected $_path = null;
    protected $_dir = null;
    protected $_url = null;
    protected $_created_at = null;
    protected $_updated_at = null;
    /**
     * @var Filemanager
     */
    protected $filemanager = null;
    public function __construct($path = null, $content = null){
        $this->filemanager = Filemanager::getInstance();
        if($path){
            $this->setPath($path);
        }
        if($content){
            $this->setContent($content);
        }
    }
    public function setPath($path = null)
    {
        if($path){
            $parts = explode('/', $path);
            $this->_filename = array_pop($parts);
            $fs = explode('.', $this->_filename);

            $this->_extension = array_pop($fs);
            $this->_mime = static::mimeType($this->_extension)->type;
            $mime = explode('/', $this->_mime);

            $this->_dir = implode('/', $parts);
            $this->_filetype = $mime[0];
            $this->_size = filesize($path)/1024;
        }
        return $this;
    }

    public function setContent($content = null)
    {
        if($content){
            $this->_old_content = $this->_content;
            $this->_content = $content;
        }
        return $this;
    }
    public function pull(){
        $this->_old_content = $this->_content;
        $this->_content = $this->filemanager->getContent($this->_path);
        
        return $this->_content;
    }
    public function push(){
        $result = $this->filemanager->save($this->_path, $this->_content);
        $this->_size = filesize($this->_path)/1024;
        return $result;
    }
    public function update($content = null){
        if($content){
            $this->setContent($content);
        }
        return $this->push();
    }
    public function revert(){
        $this->setContent($this->_old_content);
        return $this->push();
    }
    public function delete(){
        return $this->filemanager->delete($this->_path);
    }
    public function copy(string $dst){
        if(!$dst){
            return null;
        }
        return $this->filemanager->copy($this->_path, $dst);
    }
    public function move($dst = null){
        return $this->filemanager->move($this->_path, $dst);
    }
}