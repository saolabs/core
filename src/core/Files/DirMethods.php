<?php

namespace Saola\Core\Files;

use Saola\Core\Engines\Helper;
use Saola\Core\Files\File as FilesFile;
use Saola\Core\Magic\Arr;
use Illuminate\Support\Facades\File;

trait DirMethods
{
    protected $_dir = null;
    public $_basePath = null;
    public function dirInit()
    {
        $this->_basePath = Helper::basePath();
        $this->_dir = $this->_basePath;

    }
    /**
     * thiết lập dường dẫn để quản lý file
     * @param string $dir
     * @param boolean $make_dir_if_not_exists
     * 
     * @return object instance
     */
    public function setDir($dir = null, $make_dir_if_not_exists = false)
    {
        if ($dir && is_string($dir)) {
            $dir = rtrim(rtrim($dir, "\\"), '/');
            // nếu không bắt dầu từ thư mục gốc
            if (!$this->checkDirAccepted($dir)) $dir = Helper::public_path($dir);
            $dir = rtrim(rtrim($dir, "\\"), '/');

            if (is_dir($dir)) {
                // $this->_dir = $dir;
            } elseif ($make_dir_if_not_exists) {
                // nếu thư mục không tồn tại và có yêu cầu tạo thư mục
                $this->makeDir($dir, 0755, true);
                // $this->_dir = $dir;
            }

            $this->_dir = $dir;
        }
        return $this;
    }

    /**
     * lay duong dan hien tai
     * @return string
     */
    public function getDir()
    {
        return $this->_dir;
    }


    /**
     * thiết lập dường dẫn để quản lý file
     * @param string $dir
     * @param boolean $make_dir_if_not_exists
     * 
     * @return object instance
     */
    public function dir($dir = null, $make_dir_if_not_exists = false)
    {
        $f = clone $this;
        $f->setDir($dir, $make_dir_if_not_exists);
        return $f;
    }


    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @param  bool  $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * tạo dường dẫn mới
     * @param string $dir
     * @param int $mode 
     * @param boolean $recursive
     * 
     * @return boolean
     */
    public function makeDir(string $dir, $mode = 777, $recursive = false)
    {
        if ($dir && is_string($dir)) {
            // nếu không bắt dầu từ thư mục gốc
            if (!$this->checkDirAccepted($dir)) $dir = $this->publicPath($dir);

            $dlist = explode('/', str_replace("\\", "/", str_replace(rtrim(rtrim($this->_basePath, "\\"), '/'), '', $dir)));

            $xdir = rtrim(rtrim($this->_basePath, "\\"), '/');

            if (count($dlist)) {
                foreach ($dlist as $subPath) {
                    if (strlen($subPath)) {
                        if (!is_dir($xdir .= '/' . $subPath)) {
                            $this->makeDirectory($xdir, $mode, $recursive, true);
                            // $oldumask = umask(0);
                            // // mkdir('mydir', 0777); // or even 01777 so you get the sticky bit set
                            // @mkdir($xdir, $mode, $recursive);
                            // umask($oldumask);
                        }
                        // chmod($xdir, 0766);
                    }
                }
            }
            return true;
        }
        return false;
    }


    /**
     * tạo đùng dẫn chính xác
     *
     * @param string $dir
     * @param integer $mode
     * @param boolean $recursive
     * @return bool
     */
    protected function mkdir($dir, $mode = 0755, $recursive = false)
    {
        if (is_dir($dir)) {
            // $this->chmod($dir, $mode);
            return true;
        }
        File::isDirectory($dir) or File::makeDirectory($dir, $mode, true, true);
        return is_dir($dir) ? true : mkdir($dir, $mode);
    }

    /**
     * change mode
     * @param string $dir
     * @param int $mode
     */
    public function chmod($dir, $mode = 0755)
    {
        if ($dir && is_string($dir)) {
            // nếu không bắt dầu từ thư mục gốc
            if (!$this->checkDirAccepted($dir)) $dir = Helper::publicPath($dir);

            $d = str_replace("\\", "/", $dir);
            if (is_dir($d)) {
                chmod($d, $mode);
                // exec('sudo chmod -R '.$mode.' '.$d);
                return true;
            }
        }
        return false;
    }
    /**
     * kiểm tra xem dường dẫn có dc cho phép hay ko
     * @param string $dir
     * 
     * @return boolean
     */
    public function checkDirAccepted(string $dir)
    {
        $base = rtrim(rtrim(Helper::basePath(''), "\\"), '/');
        if (count(explode($base, $dir)) == 2) return true;
        return false;
    }

    /**
     * kiểm tra xem dường dẫn có dc cho phép hay ko
     * @param string $dir
     * 
     * @return boolean
     */
    public function canDelete(string $dir)
    {
        $dir = rtrim(str_replace("\\", "/", $dir), '/');
        $ban_list = [
            rtrim(str_replace("\\", "/", Helper::basePath('')), '/'),
            rtrim(str_replace("\\", "/", Helper::publicPath('')), '/'),
        ];
        if (in_array($dir, $ban_list)) return false;
        return true;
    }

    /**
     * chuyển dường dẫn hiện tại
     * 
     * @param string $dir
     * @param boolean $make_dir_if_not_exists
     * 
     * @return object
     */
    public function cd($dir = null, $make_dir_if_not_exists = false)
    {
        if ($this->checkDirAccepted($dir)) return $this->setDir($dir);
        $fullDir = $this->_dir . '/' . trim($dir, '/');
        if (!is_dir($fullDir) && $make_dir_if_not_exists) {
            $this->makeDir($fullDir, 777, false);
        }
        $this->_dir = $fullDir;
        return $this;
    }
    /**
     * neu url
     */
    protected function joinPath($main, $sub)
    {
        return rtrim($main, '/') . '/' . ltrim($sub, '/');
    }

    /**
     * lấy danh sách file và thư mục
     * 
     * @param string Dường dẫn
     * @param string $ext phần mở rộng
     * @param bool $sort
     * @return FilesFile[]
     * 
     */

    public function getList($dir = null, $ext = null, $sort = false)
    {
        if (!$dir) $dir = $this->_dir;
        $list = [];
        $abc = [];
        $result = [];
        $e = is_string($ext) ? strtolower($ext) : null;
        if ($e) {
            $e = explode(',', $e);
            $b = [];
            for ($i = 0; $i < count($e); $i++) {
                $ei = trim($e[$i]);
                if ($ei) {
                    $b[] = $ei;
                }
            }
            $e = $b;
        }
        if (is_string($dir) && is_dir($dir)) {
            // try {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $t = 1;
                    if ($e) {
                        $fs = explode('.', $file);

                        $ex = strtolower($fs[count($fs) - 1]);
                        $path = $this->joinPath($dir, $file);
                        if (is_file($path) && in_array($ex, $e)) {
                            $t = 1;
                        } else {
                            $t = 0;
                        }
                        if ($t && $file != '..' && $file != '.') {

                            $sd = strtolower($file);
                            $abc[] = $sd;
                            $rawsize = filesize($path);
                            $size = round($rawsize / 1024, 2);
                            $size_unit = "KB";
                            if ($size >= 1024) {
                                $size = round($size / 1024, 2);
                                $size_unit = 'MB';
                                if ($size >= 1024) {
                                    $size = round($size / 1024, 2);
                                    $size_unit = 'GB';
                                    if ($size >= 1024) {
                                        $size = round($size / 1024, 2);
                                        $size_unit = 'TB';
                                    }
                                }
                            }
                            $mod =  substr(sprintf('%o', fileperms($path)), -3);


                            $list[$sd] = new Arr([
                                'type' => 'file',
                                'name' => $file,
                                'path' => $path,
                                'extension' => $ex,
                                'mode' => $mod,
                                'modified' => date("F d Y H:i:s", filemtime($path)),
                                'size' => $rawsize,
                                'size_total' => $size,
                                'size_unit' => $size_unit
                            ]);
                        }
                    } else {
                        if ($file != '..' && $file != '.') {
                            $path = $this->joinPath($dir, $file);
                            $fs = explode('.', $file);
                            $ex = strtolower(array_pop($fs));
                            $type = is_dir($path) ? 'folder' : 'file';
                            $sd = strtolower($file);
                            $abc[] = $sd;

                            $mod =  substr(sprintf('%o', fileperms($path)), -3);

                            $list[$sd] = new Arr([
                                'type' => $type,
                                'name' => $file,
                                'extension' => $ex,
                                'mode' => $mod,
                                'modified' => date("F d Y H:i:s", filemtime($path)),
                                'path' => $path
                            ]);

                            if ($type == 'file') {

                                $rawsize = filesize($path);
                                $size = round($rawsize / 1024, 2);
                                $size_unit = "KB";
                                if ($size >= 1024) {
                                    $size = round($size / 1024, 2);
                                    $size_unit = 'MB';
                                    if ($size >= 1024) {
                                        $size = round($size / 1024, 2);
                                        $size_unit = 'GB';
                                        if ($size >= 1024) {
                                            $size = round($size / 1024, 2);
                                            $size_unit = 'TB';
                                        }
                                    }
                                }
                                $list[$sd]->size_total = $size;
                                $list[$sd]->size_unit = $size_unit;
                            } else {
                                $size = get_folder_size($path, 'k');
                                $size_unit = "KB";
                                if ($size >= 1024) {
                                    $size = round($size / 1024, 2);
                                    $size_unit = 'MB';
                                    if ($size >= 1024) {
                                        $size = round($size / 1024, 2);
                                        $size_unit = 'GB';
                                        if ($size >= 1024) {
                                            $size = round($size / 1024, 2);
                                            $size_unit = 'TB';
                                        }
                                    }
                                }
                                $list[$sd]->size_total = $size;
                                $list[$sd]->size_unit = $size_unit;
                            }
                        }
                    }
                }
                closedir($dh);
            }
            // } catch (\Exception $e) {
            //     // $this->errors[__METHOD__] = $e->getMessage();
            // }
        }
        if ($list && $abc) {
            if ($sort) {
                sort($abc);
            }
            $t = count($abc);
            $type_list = [
                'folder' => [],
                'file' => []
            ];

            for ($i = 0; $i < $t; $i++) {
                $item = $list[$abc[$i]];
                $type_list[$item->type][] = $item;
            }
            foreach ($type_list as $list_type) {
                foreach ($list_type as $it) {
                    $result[] = $it;
                }
            }
        }
        return $result;
    }


    /**
     * xóa tất cả
     * @param string $dirname
     */
    public function delete($dirname = null)
    {
        if (is_string($dirname)) {
            $tt = $this->checkDirAccepted($dirname);
            if (is_file($dirname) && $tt) return unlink($dirname);
            elseif (is_dir($dirname) && $tt && $this->canDelete($dirname)) {
                return $this->removeDir($dirname);
            } else {
                $dirname = $this->joinPath($this->_dir, $dirname);
                if (is_file($dirname)) return unlink($dirname);
                elseif (is_dir($dirname) && $this->canDelete($dirname)) {
                    return $this->removeDir($dirname);
                }
            }
            return false;
        } else {
            return $this->deleteFile();
        }
    }

    /**
     * xoa
     */
    protected function removeDir($dirname)
    {
        try {
            if ($list = $this->getList($dirname)) {
                foreach ($list as $item) {
                    $d = $item->path;
                    if (is_dir($d)) $this->delete($d);
                    else unlink($d);
                }
            }
            return rmdir($dirname);
        } catch (\Exception $e) {
            // $this->errors[__METHOD__] = $e->getMessage();
            return false;
        }
    }

    /**
     * sao chep thu muc
     */
    public function copyFolder($src, $dst, $check_src = true, $check_dst = true)
    {

        if (is_string($src) && is_string($dst) && $src != $dst) {
            if (!$this->checkDirAccepted($src)) $src = $this->joinPath($this->_basePath, $src);
            if (!$this->checkDirAccepted($dst)) $dst = $this->joinPath($this->_basePath, $dst);
            if (!is_dir($src)) return false;
            if (!is_dir($dst)) $this->makeDir($dst, 0755, true);
            // $this->chmod($src, 777);
            // $this->chmod($dst, 777);
            $mng = app(static::class);
            $date = date('Y-m-d');
            if ($list = $this->getList($src)) {
                foreach ($list as $file) {
                    if ($file->type == 'folder') {
                        $this->copyFolder($src . '/' . $file->name, $dst . '/' . $file->name);
                    } else {
                        if (!is_dir($dst)) {
                            $this->makeDir($dst, 0755, true);
                        }
                        // $this->chmod($src.'/'.$file->name);
                        $msg = 'copy from ' . $src . '/' . $file->name . ' to ' . $dst . '/' . $file->name . ' ';
                        $this->copyFile($src . '/' . $file->name, $dst . '/' . $file->name);
                        // if(!file_exists($dst.'/'.$file->name)){
                        //     @exec('sudo cp '.$src.'/'.$file->name.' '.$dst.'/'.$file->name);
                        // }
                        if (!file_exists($dst . '/' . $file->name)) {
                            $msg .= 'fail';
                            $mng->append("\n" . $msg, Helper::storage_path('crazy/logs/' . $date . '.log'));
                        }
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
