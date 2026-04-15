<?php

namespace Saola\Core\Support\Methods;

use Saola\Core\Files\Filemanager;

trait FileMethods
{
    /**
     * @var \Saola\Core\Files\Filemanager $filemanager
     */
    protected $filemanager = null;
    
    public function initFile()
    {
        $this->filemanager = new Filemanager();
    }
    public function getFilemanager()
    {
        return $this->filemanager;
    }
    public function setFilemanager($filemanager)
    {
        $this->filemanager = $filemanager;
    }
    
}