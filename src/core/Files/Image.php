<?php

/**
 * @author Le Ngoc Doan
 * @copyright 2019
 */

namespace Saola\Core\Files;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use GdImage;


/**
 * image
 * @method Image addImage(string|GdImage $item, string|integer $x = "center", integer|string $y = "center", integer $margin = 0, integer $item_width = null, integer $item_height = null) Chèn ảnh vảo ảnh gốc
 * @method Image insertImage(string|GdImage $item, string|integer $x = "center", integer|string $y = "center", integer $margin = 0, integer $item_width = null, integer $item_height = null) Chèn ảnh vảo ảnh gốc
 * @method static GdImage addImage(string|GdImage $image, string|GdImage $item, string|integer $x = "center", integer|string $y = "center", integer $margin = 0, integer $item_width = null, integer $item_height = null) Chèn ảnh vảo ảnh gốc
 * @method static GdImage insertImage(string|GdImage $image, string|GdImage $item, string|integer $x = "center", integer|string $y = "center", integer $margin = 0, integer $item_width = null, integer $item_height = null) Chèn ảnh vảo ảnh gốc
 * @method Image addText(string $text,int $size=10,string|int $x='center',string|int $y='center', int $angle=0,string $font='arial.ttf',int $max_width=null,int $margin=0, string $text_color='#000', string $stroke_color='#FFF', int $stroke_width=0) Chèn text vào ảnh
 * @method Image insertText(string $text,int $size=10,string|int $x='center',string|int $y='center', int $angle=0,string $font='arial.ttf',int $max_width=null,int $margin=0, string $text_color='#000', string $stroke_color='#FFF', int $stroke_width=0) Chèn text vào ảnh
 * @method static GdImage addText(GdImage|string $image, string $text,int $size=10,string|int $x='center',string|int $y='center', int $angle=0,string $font='arial.ttf',int $max_width=null,int $margin=0, string $text_color='#000', string $stroke_color='#FFF', int $stroke_width=0) Chèn text vào ảnh
 * @method Image insertText(GdImage|string $image, string $text,int $size=10,string|int $x='center',string|int $y='center', int $angle=0,string $font='arial.ttf',int $max_width=null,int $margin=0, string $text_color='#000', string $stroke_color='#FFF', int $stroke_width=0) Chèn text vào ảnh
 * 
 * @method Image addQR(string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) thêm mã qr vào ảnh
 * @method Image addQRCode(string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method Image addQrCode(string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method Image insertQR(string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method Image insertQr(string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method Image insertQrCode(string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * 
 * @method static GdImage insertQrCode(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method static GdImage insertQr(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method static GdImage insertQRCode(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method static GdImage insertQR(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method static GdImage addQR(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method static GdImage addQRCode(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * @method static GdImage addQR(GdImage|string $image, string $text, int $size=400, int|string $x = "center", int|string $y = "center", int $margin = 0) Thêm mã Qr vào ảnh
 * 
 * @method static GdImage createQrImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage makeQrImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage createQRImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage makeQRImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage createQrCodeImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage makeQrCodeImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage createQRCodeImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage makeQRCodeImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage qrCodeImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage qrImage(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * @method static GdImage qrCode(string $text, int $size = 400, int $margin = 0) Tạo ảnh mã QR
 * 
 */
class Image
{
    use FileType;
    protected $source;
    /**
     * Undocumented variable
     *
     * @var GdImage
     */
    protected $data;
    protected $original;
    protected $type = null;
    protected $mime = null;
    protected $width = 0;
    protected $height = 0;
    protected static $font_path;
    protected static $font_folder;
    protected static $font = 'arial.ttf';
    protected $isImage = false;
    protected $name = null;
    protected static $checkedData = [];
    public function __construct($image = null)
    {
        $this->newImage($image);
    }

    public function newImage($image = null)
    {
        if (self::isImageFile($image) || (is_resource($image) && \get_resource_type($image) == 'gd') || (is_object($image) && class_exists('GdImage') && is_a($image, 'GdImage'))) {
            try {
                if (self::isImageFile($image)) {
                    $i = self::getsity($image);
                    $im  = self::create($image);
                    $this->data = $im;
                    $this->original = $im;
                    $this->height = $i['h'];
                    $this->width = $i['w'];
                    $this->type = $i['type'];
                    $this->mime = $i['mime'];
                    $this->isImage = true;
                } else {
                    $this->data = $image;
                    $this->original = $image;
                    $this->height = imagesy($this->data);
                    $this->width = imagesx($this->data);
                    $this->isImage = true;
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        } else {
            $this->height = 768;
            $this->width = 1366;
            $im = self::create(null, $this->height, $this->width, array(255, 255, 255));
            $this->data = $im;
            $this->original = $im;
            $this->type = 'png';
            $this->mime = 'image/png';
        }
    }
    /**
     * lấy dữ liệu ảnh
     *
     * @return GdImage
     */
    public function get()
    {
        return $this->data;
    }
    /**
     * lấy dữ liệu nguyên dạng
     *
     * @return GD
     */
    public function getoriginal()
    {
        return $this->original;
    }
    /**
     * lấy loại tập tin ảnh
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * lấy loại file
     *
     * @return string
     */
    public function getMime()
    {
        return $this->mime;
    }
    /**
     * lấy chiều cao ảnh
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }
    /**
     * lấy chiều rộng ảnh
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }
    /**
     * lầy tên file đã được lưu
     *
     * @return string
     */
    public function getStoredFilename()
    {
        return $this->name;
    }
    /**
     * kiểm tra xem phải ảnh hay không
     *
     * @return bool
     */
    public function check()
    {
        return $this->isImage;
    }

    /**
     * lấy duôi file
     *
     * @param string $mime
     * @return string
     */
    public function getExt($mime = null)
    {
        $type = $mime ? $mime : ($this->mime ? $this->mime : 'image/png');
        $mity = $this->getMimeType($type);

        $stt = 'png';
        if (($type == "png" || $type == "image/png")) {
            $stt = 'png';
        } elseif (($type == "jpg" || $type == "jpeg" || $type == "image/jpeg")) {
            $stt = 'jpg';
        } elseif (($type == "gif" || $type == "image/gif")) {
            $stt = 'gif';
        } elseif ($mity) {
            $stt = $mity->extension;
        }
        return $stt;
    }

    /**
     * lấy một bản sao
     *
     * @return Image
     */
    public function copy()
    {
        $b = clone $this;
        return $b;
    }
    /**
     * tao hinh anh tren trinh duyet
     * @param string $mime kieu file anh
     */
    public function output($mime = null)
    {
        $m = $mime ? $mime : ($this->mime ? $this->mime : 'image/png');
        $d = $this->data;
        header('Content-Type: ' . $m);
        self::display($d, null, $m);
    }
    /**
     * tao hinh anh tren trinh duyet
     * @param string $mime kieu file anh
     */
    public function show($mime = null)
    {
        $this->output($mime);
    }
    /**
     * tao file tu du lieu co san
     * @param string $filename ten file hoac duong dan noi bo
     * @param string $mime kieu file anh
     * @return string|false
     */
    public function save($filename, $mime = null)
    {
        if (!is_string($filename)) throw new \Exception("filename you gived is not a string", 1);
        elseif ((is_resource($this->data) && get_resource_type($this->data) == 'gd') || (is_object($this->data) && class_exists('GdImage') && is_a($this->data, 'GdImage'))) {
            $m = $mime ? $mime : ($this->mime ? $this->mime : 'image/png');
            $ext = $this->getExt($m);
            $am = $ext == 'jpg' || $ext == 'jpeg' ? '(jpg|jpeg)' : $ext;
            if (!preg_match('/\.' . $am . '$/si', $filename)) {
                $filename .= '.' . $ext;
            }

            $p = explode('/', $filename);
            $fn = array_pop($p);
            $file = new Filemanager();
            $file->makeDir(implode('/', $p), 755, true);
            $this->name = $fn;
            //$quality = ($imi['quality'])?$imi['quality']:100;
            if (($ext == "png") && imagepng($this->data, $filename)) {
                $stt = $filename;
            } elseif (($ext == "jpg" || $ext == "jpeg") && imagejpeg($this->data, $filename)) {
                $stt = $filename;
            } elseif (($ext == "gif") && imagegif($this->data, $filename)) {
                $stt = $filename;
            } elseif (($ext == "webp") && imagewebp($this->data, $filename)) {
                $stt = $filename;
            } elseif (file_put_contents($filename, $this->data)) {
                $stt = $filename;
            } else {
                $stt = false;
            }
            return $stt;
        }
        return false;
    }

    /**
     * lam mo anh
     * @param int $int Độ mờ
     */
    public function blur($int = 10)
    {
        $image = $this->data;


        //$image = image::images($image);
        $image_width = imagesx($image);
        $image_height = imagesy($image);

        for ($i = 0; $i < $int; $i++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $this->data = $image;
        return $this;
    }

    /**
     * chinh kich thuoc
     * @param int $width
     * @param int $height
     * @return Image
     */
    public function resizeAndCrop($width = null, $height = null)
    {
        $image = $this->data;
        $w = imagesx($image);
        $h = imagesy($image);
        $g = $w / $h;

        if (is_string($width) && strtolower($width) == 'auto' && is_string($height) && strtolower($height) == 'auto') {
            return $this;
        }

        if (is_string($width) && strtolower($width) == 'auto') {
            $width = $height * $g;
        } elseif (!$width || !is_numeric($width)) {
            $width = $this->width;
        }

        if (is_string($height) && strtolower($height) == 'auto') {
            $height = $width / $g;
        } elseif (!$height || !is_numeric($height)) {
            $height = $this->height;
        }

        $k = $width / $height;
        if ($g < $k) {
            $s = $width;
            $z = "width";
        } else {
            $s = $height;
            $z = "height";
        }

        $this->zoom($z, $s);
        $this->crop($width, $height);
        $this->refresh();
        return $this;
    }




    /**
     * cat hinh anh
     * @param int $width Độ rộng
     * @param int $height Chiều cao
     * @param int $x Tọa độ x
     * @param int $y Tọa đô y
     * @return Image
     */

    public function crop($width = null, $height = null, $x = null, $y = null, $transparent_bg = true)
    {
        $w = $width;
        $h = $height;
        if (is_array($width)) {
            $i = $width;
            if (isset($i['width'])) $w = $i['width'];
            else $w = null;
            if (isset($i['height'])) $h = $i['height'];
            if (isset($i['x'])) $x = $i['x'];
            if (isset($i['y'])) $y = $i['y'];
        }
        $img = $this->data;
        $x = self::cropX($img, $x, $w);
        $y = self::cropY($img, $y, $h);

        $nWidth = $w;
        $nHeight = $h;
        $newImg = imagecreatetruecolor($nWidth, $nHeight);
        if ($transparent_bg) {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $nWidth, $nHeight, $transparent);
        }
        $targ_w = $w;
        $targ_h = $h;
        $jpeg_quality = 90;


        $ix = imagesx($img);
        $iy = imagesy($img);


        imagecopyresampled($newImg, $img, 0, 0, $x, $y, $nWidth, $nHeight, $w, $h);
        //imagecopyresampled($dst_r,$img_r,0,0,$x,$y,$targ_w,$targ_h,$w,$h);
        $this->data = $newImg;
        $this->refresh();
        return $this;
    }

    /**
     * cat hinh anh
     * @param int $width Độ rộng
     * @param int $height Chiều cao
     * @param int $x Tọa độ x
     * @param int $y Tọa đô y
     * @return Image
     */

    public function insertBackground()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $x = 0;
        $y = 0;
        $transparent_bg = true;
        $w = $width;
        $h = $height;
        if (is_array($width)) {
            $i = $width;
            if (isset($i['width'])) $w = $i['width'];
            else $w = null;
            if (isset($i['height'])) $h = $i['height'];
            if (isset($i['x'])) $x = $i['x'];
            if (isset($i['y'])) $y = $i['y'];
        }
        $img = $this->data;
        // $x = self::cropX($img, $x, $w);
        // $y = self::cropY($img, $y, $h);

        $nWidth = $w;
        $nHeight = $h;
        $newImg = imagecreatetruecolor($nWidth, $nHeight);
        $transparent = imagecolorallocate($newImg, 255, 255, 255);
        imagefilledrectangle($newImg, 0, 0, $nWidth, $nHeight, $transparent);
        $targ_w = $w;
        $targ_h = $h;
        $jpeg_quality = 90;


        $ix = imagesx($img);
        $iy = imagesy($img);


        imagecopyresampled($newImg, $img, 0, 0, $x, $y, $nWidth, $nHeight, $w, $h);
        //imagecopyresampled($dst_r,$img_r,0,0,$x,$y,$targ_w,$targ_h,$w,$h);
        $this->data = $newImg;
        $this->refresh();
        return $this;
    }


    /**
     * chỉnh kích thước
     *
     * @param int $width
     * @param int $height
     * @return Image
     */
    public function resize($width = null, $height = null)
    {
        $image = $this->data;
        if ($image) {
            $bg = imagecreatetruecolor($width, $height);;
            if (self::isImageFile($image)) {
                $img = self::create($image);
            } else {
                $img = $image;
            }
            // imagecopy($bg, $img, 0, 0, $width, $height, imagesx($image), imagesy($image));
            imagecopyresized($bg, $img, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
            $afterresize = $bg;
        } else {
            $afterresize = $image;
        }
        $this->data = $afterresize;
        $this->refresh();
        return $this;
    }

    /**
     * thu phong hinh anh
     * @param string $type chieu thu phong height | width | d
     * @param int $size do lon hinh anh
     * @param string $p don vi thu phong % | px
     * @return Image
     */
    public function zoom($type = 'width', $size = 100, $p = 'px')
    {
        $img = $this->data;
        $width = imagesx($img);
        $height = imagesy($img);
        $tt = strtolower($type);
        $n = $size;
        if ($p == "%") {
            $zk = $n / 100;
            $new_height = $height * $zk;
            $new_width = $width * $zk;
        } else {
            $k = $width / $height;
            if ($tt == "h" || $tt == 'height') {
                $new_height = $n;
                $new_width = $new_height * $k;
            } elseif ($tt == 'd' || $tt == 'diagonal') {
                $d1 = sqrt(($height * $height) + ($width * $width));
                $d2 = $n;
                $k2 = $d2 / $d1;
                $new_height = $k2 * $height;
                $new_width = $k2 * $width;
            } else {
                $new_width = $n;
                $new_height = $new_width / $k;
            }
        }
        $newImg = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        imagefilledrectangle($newImg, 0, 0, $new_width, $new_height, $transparent);

        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        $this->data = $newImg;
        $this->refresh();
        return $this;
    }

    /**
     * xoay ảnh
     *
     * @param integer $angle
     * @return Image
     */
    public function rotate($angle = 0)
    {
        if (is_numeric($angle)) {
            $img = $this->data;

            $img = imagerotate($img, $angle, -1);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            $this->data = $img;
            $this->refresh();
        }
        return $this;
    }


    /**
     * đưa về trạng thái ban đầu
     *
     * @return void
     */
    public function restore()
    {
        $this->data = $this->original;
        $this->refresh();
        return $this;
    }

    /**
     * làm mới
     *
     * @return void
     */
    public function refresh()
    {
        $this->width = imagesx($this->data);
        $this->height = imagesy($this->data);
        return $this;
    }


    // các phương thức static

    /**
     * @param string
     */
    public static function isImageFile($url)
    {
        if (!is_string($url))
            return false;
        if (array_key_exists($url, static::$checkedData))
            return static::$checkedData[$url];
        $stt = (preg_match('/(^http|\.jpg|\.gif|\.png|tmp|\.jpeg|\.webp)/si', $url) || is_file($url)) ? true : false;
        if (!$stt && preg_match('/^(http|https)\:\/\/.*/si', $url)) {
            try {
                $content = file_get_contents($url);

                // Kiểm tra xem nội dung có phải là một hình ảnh hay không
                $source = getimagesizefromstring($content);

                if ($source) {
                    $mime = $source['mime'];
                    $w = $source[0];
                    $h = $source[1];
                    $typ = explode('/', $mime);
                    if ($typ[0] == 'image') {
                        $stt = true;
                        static::$checkedData[$url] = $source;
                    }
                }
            } catch (\Throwable $th) {
                static::$checkedData[$url] = $stt;
            }
        } else {
            static::$checkedData[$url] = $stt;
        }
        return $stt;
    }





    public static function getsity($image_url)
    {
        $pex = '';
        $mime = '';
        if (self::isImageFile($image_url)) {
            $source = getimagesize($image_url);
            if ($source) {
                $mime = $source['mime'];
                $w = $source[0];
                $h = $source[1];
                $typ = explode('/', $mime);
                if ($typ[0] == 'image' && isset($typ[1])) {
                    $t = $typ[1];
                    switch ($t) {
                        case 'png':
                            $pex = $t;
                            break;
                        case 'jpeg':
                            $pex = 'jpg';
                            break;
                        case 'gif':
                            $pex = $t;
                            break;
                        default:
                            $pex = $t;
                            break;
                    }
                }
            } elseif (preg_match('/^(http|https)\:\/\/.*/i', $image_url)) {
                $content = file_get_contents($image_url);

                // Kiểm tra xem nội dung có phải là một hình ảnh hay không
                $source = getimagesizefromstring($content);

                if ($source) {
                    $mime = $source['mime'];
                    $w = $source[0];
                    $h = $source[1];
                    $typ = explode('/', $mime);
                    if ($typ[0] == 'image' && isset($typ[1])) {
                        $t = $typ[1];
                        switch ($t) {
                            case 'png':
                                $pex = $t;
                                break;
                            case 'jpeg':
                                $pex = 'jpg';
                                break;
                            case 'gif':
                                $pex = $t;
                                break;
                            default:
                                $pex = $t;
                                break;
                        }
                    }
                }
            }
        } elseif ($image_url) {
            $w = imagesx($image_url);
            $h = imagesy($image_url);
        } else {
            $w = 0;
            $h = 0;
        }
        $img_inf = array(
            'type' => $pex,
            'mime' => $mime,
            'w' => $w,
            'h' => $h
        );
        return ($w) ? $img_inf : null;
    }

    /**
     * @param resource or string
     * @param string
     * @param string
     */
    public static function images($image = null)
    {
        if (is_string($image) && $baseData = static::base64Data($image)) {
            $path = storage_path('logs/base64-temp-' . uniqid() . '.' . $baseData->extension);
            file_put_contents($path, $baseData->data);
            if (file_exists($path)) {
                $source_im = static::create($image);
                unlink($path);
            } else {
                $source_im = static::create(null, 480, 360, array(255, 255, 255));
            }
        } elseif (self::isImageFile($image)) {
            $source_im = self::create($image);
        } elseif (is_gd_image($image)) {
            $source_im = $image;
        } elseif (is_resource($image) && get_resource_type($image) == 'gd') {
            $source_im = $image;
        } else {
            $source_im = static::create(null, 480, 360, array(255, 255, 255));
        }
        return $source_im;
    }
    public static function display($image_src, $img_filename = null, $img_type = null)
    {
        $imi = $image_src;
        if (is_array($imi)) {
            $type = ($imi['type']) ? $imi['type'] : $img_type;
            $image = $imi['image'];
            $filename = ($imi['filename']) ? $imi['filename'] : (($img_filename) ? $img_filename : null);
        } elseif (self::isImageFile($imi)) {
            $image = self::create($imi);
            $ii = self::getsity($imi);
            $filename = ($img_filename) ? $img_filename : null;
            $type = ($img_type) ? $img_type : $ii['type'];
        } else {
            $image = $imi;
            $filename = ($img_filename) ? $img_filename : null;
            $type = $img_type;
        }
        $p = explode('/', $filename);
        $fn = array_pop($p);
        $file = new Filemanager();
        $file->makeDir(implode('/', $p), 777, true);
        //$quality = ($imi['quality'])?$imi['quality']:100;
        if (($type == "png" || $type == "image/png") && imagepng($image, $filename)) {
            $stt = true;
        } elseif (($type == "jpg" || $type == "jpeg" || $type == "image/jpeg") && imagejpeg($image, $filename)) {
            $stt = true;
        } elseif (($type == "gif" || $type == "image/gif") && imagegif($image, $filename)) {
            $stt = true;
        } elseif (isset($ii) && copy($imi, $img_filename)) {
            $stt = true;
        } else {
            $stt = false;
        }
        return $stt;
    }

    /**
     * @param resource or string
     * @param int
     * @param int
     * @param array or string
     */

    public static function create($image_url = null, $image_w = 100, $image_h = 100, $color = null)
    {
        if (is_string($image_url) && self::isImageFile($image_url)) {
            $img = self::getsity($image_url);
            $type = $img['type'];
            if ($type == "png") {
                $image = \imagecreatefrompng($image_url);
            } elseif ($type == "jpg") {
                $image = \imagecreatefromjpeg($image_url);
            } elseif ($type == "gif") {
                $image = \imagecreatefromgif($image_url);
            } elseif ($type == "webp") {
                $image = \imagecreatefromwebp($image_url);
            } else {
                $image = file_get_contents($image_url);
                if (preg_match('/^https?\:\/\/.*/', $image_url)) {
                    try {
                        $im = imagecreatefromstring($image);
                        if ($im) $image = $im;
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
            }
        } else {
            if (is_array($color) && isset($color[0]) && isset($color[1]) && isset($color[2])) {
                $image = \imagecreatetruecolor($image_w, $image_h);
                imagecolorallocate($image, $color[0], $color[1], $color[2]);
            } elseif (is_string($color)) {
                $color = explode(',', $color);
                if (is_array($color) && isset($color[0]) && isset($color[1]) && isset($color[2])) {
                    $image = \imagecreatetruecolor($image_w, $image_h);
                    imagecolorallocate($image, $color[0], $color[1], $color[2]);
                } else {
                    $image = \imagecreatetruecolor($image_w, $image_h);
                }
            } else
                $image = \imagecreatetruecolor($image_w, $image_h);
            //$txtColor = imagecolorallocate($image, 245, 250, 254);

        }
        return $image;
    }

    protected static function cropX($img, $x = null, $width = null)
    {
        $r = 0;
        $w = imagesx($img);
        $h = imagesy($img);
        if (!is_numeric($width)) $width = $w;
        if (is_numeric($x)) {
            $r = $x;
        } elseif (is_string($x)) {
            $s = strtolower($x);
            if ($s == 'left' || $s == 'l' || $s == 'trai' || $s == 't') {
                $e = 0;
            } elseif ($s == 'right' || $s == 'r' || $s == 'phai' || $s == 'p') {
                $r = $w - $width;
            } else {
                $r = ($w - $width) / 2;
            }
        } else {
            $r = ($w - $width) / 2;
        }
        return $r;
    }

    protected static function cropY($img, $y = null, $height = null)
    {
        $r = 0;
        $w = imagesx($img);
        $h = imagesy($img);
        if (!is_numeric($height)) $height = $h;
        if (is_numeric($y)) {
            $r = $y;
        } elseif (is_string($y)) {
            $s = strtolower($y);
            if ($s == 'top' || $s == 't' || $s == 'tren') {
                $e = 0;
            } elseif ($s == 'bottom' || $s == 'b' || $s == 'duoi' || $s == 'd') {
                $r = $h - $height;
            } else {
                $r = ($h - $height) / 2;
            }
        } else {
            $r = ($h - $height) / 2;
        }
        return $r;
    }


    /**
     * thêm ảnh vào ảnh gốc
     *
     * @param string|GdImage $image
     * @param string|GdImage $item
     * @param string|int $x
     * @param string|int $y
     * @param integer $margin
     * @param int $item_width
     * @param int $item_height
     * @return GdImage
     */
    protected static function _addImage($image, $item, $x = "center", $y = "center", $margin = 0, $item_width = null, $item_height = null)
    {
        $x = strtolower($x);
        $y = strtolower($y);

        $dst_im = self::images($image);
        $bg_w = imagesx($dst_im);
        $bg_h = imagesy($dst_im);

        $source_im = self::images($item);
        $ins_w = imagesx($source_im);
        $ins_h = imagesy($source_im);


        if (is_numeric($x)) {
            $ix = $x;
        } elseif (is_string($x)) {
            if ($x == "left")
                $ix = $margin;
            elseif ($x == "right")
                $ix = ($bg_w - $ins_w) - $margin;
            else
                $ix = ($bg_w - $ins_w) / 2;
        } else {
            $ix = $margin;
        }
        if (is_numeric($y)) {
            $iy = $y;
        } elseif (is_string($y)) {
            if ($y == "top") {
                $iy = $margin;
            } elseif ($y == "bottom") {
                $iy = $bg_h - $ins_h - $margin;
            } else {
                $iy = ($bg_h - $ins_h) / 2;
            }
        } else {
            $iy = $margin;
        }
        $itw = is_numeric($item_width) ? $item_width : $ins_w;
        $ith = is_numeric($item_height) ? $item_height : $ins_h;
        imagecopy($dst_im, $source_im, $ix, $iy, 0, 0, $itw, $ith);
        return $dst_im;
    }


    /**
     * chèn text vào ảnh
     *
     * @param string|GdImage $image
     * @param string $text
     * @param integer $size
     * @param string $x
     * @param string $y
     * @param integer $angle
     * @param string $font
     * @param integer $max_width
     * @param integer $margin
     * @param string $text_color
     * @param string $stroke_color
     * @param integer $stroke_width
     * @return GdImage
     */
    protected static function _addText($image, $text, $size = 10, $x = 'center', $y = 'center', $angle = 0, $font = 'arial.ttf', $max_width = null, $margin = 0, $text_color = '#000', $stroke_color = '#FFF', $stroke_width = 0)
    {
        /* xu ly hinh anh dua vao */
        if (self::is_image_file($image)) $img = self::create($image);
        elseif (strtolower(get_resource_type($image)) == 'gd') $img = $image;
        else $img = self::create(null, 850, 400, array(25, 162, 100));

        /** xu ly thong so chuoi dua vao */

        $txt = $text;
        if (is_array($text)) {
            $i = $text;
            $txt = isset($i['text']) ? $i['text'] : '';
            $size = isset($i['size']) ? $i['size'] : $size;
            $margin = isset($i['margin']) ? $i['margin'] : $margin;
            $max_width = isset($i['max_width']) ? $i['max_width'] : $max_width;
            $font = isset($i['font']) ? $i['font'] : $font;
            $x = isset($i['x']) ? $i['x'] : $x;
            $y = isset($i['y']) ? $i['y'] : $y;
            $text_color = isset($i['color']) ? $i['color'] : $text_color;
            $stroke_color = isset($i['stroke_color']) ? $i['stroke_color'] : $stroke_color;
            $stroke_width = isset($i['stroke_width']) ? $i['stroke_width'] : $stroke_width;
            $angle = isset($i['angle']) ? $i['angle'] : $angle;
        }
        $text = $txt;
        $font = self::get_font_url($font);

        $image_width = imagesx($img);
        $image_height = imagesy($img);
        // xu ly mau sac

        if (is_array($text_color) && isset($text_color[0]) && isset($text_color[1]) && isset($text_color[2])) {
            $tc = $text_color;
            $text_color = imagecolorallocate($img, $tc[0], $tc[1], $tc[2]);
        } elseif (preg_match('/^#/', $text_color)) {
            $tc = self::hex2rgb($text_color);
            $text_color = imagecolorallocate($img, $tc[0], $tc[1], $tc[2]);
        } elseif (preg_match('/[0-9],[0-9],[0-9]/', $text_color)) {
            $tc = explode(',', $text_color);
            $text_color = imagecolorallocate($img, $tc[0], $tc[1], $tc[2]);
        } else {
            $text_color = imagecolorallocate($img, 0, 0, 0);
        }
        if (is_array($stroke_color) && isset($stroke_color[0]) && isset($stroke_color[1]) && isset($stroke_color[2])) {
            $bc = $stroke_color;
            $boder_color = imagecolorallocate($img, $bc[0], $bc[1], $bc[2]);
        } elseif (preg_match('/^#/', $stroke_color)) {
            $bc = self::hex2rgb($stroke_color);
            $boder_color = imagecolorallocate($img, $bc[0], $bc[1], $bc[2]);
        } elseif (preg_match('/[0-9],[0-9],[0-9]/', $stroke_color)) {
            $bc = explode(',', $stroke_color);
            $boder_color = imagecolorallocate($img, $bc[0], $bc[1], $bc[2]);
        } else {
            $boder_color = imagecolorallocate($img, 0, 0, 0);
        }

        # kinh thuc text
        if (is_numeric($max_width)) {
            $max = $max_width;
        } elseif (is_numeric($margin) && $margin > 0) {
            $max = $image_width - 2 * $margin;
        } else {
            $max = $image_width;
        }
        $texts_height = static::getiTextHeight($text, $size, $max, $font, $angle);
        $texts = static::textQoute($text, $max, $font, $size, 0);
        $t = count($texts);
        $th = static::getiTextMaxHeight($text, $size, $max, $font, $angle);

        $image = image::images($img);
        if (static::countTextLine($text, $size, $max, $font, $angle) > 1) {
            $tr = $th / 1.1;
        } else {
            $tr = $th;
        }
        #vi tri text (y)
        if (is_numeric($y)) $sy = $y + $tr;
        else {
            $ly = strtolower($y);
            $_gy = explode('=', $ly);
            if (count($_gy) == 2) {
                if ($_gy[0] == 'm' || $_gy[0] == 'c' || $_gy[0] == 'mid' || $_gy[0] == 'middle') {
                    $sy = static::getText2Number($_gy[1]) - $texts_height / 2 + $tr;
                } else {
                    $sy = ($image_height - $texts_height) / 2 + $tr * 0.8;
                }
            } elseif ($ly == 'top')
                $sy = $margin + $tr;
            elseif ($ly == 'bottom')
                $sy = $image_height - $margin - $texts_height + $tr;
            else
                $sy = ($image_height - $texts_height) / 2 + $tr * 0.8;
        }
        for ($i = 0; $i < $t; $i++) {
            $txt = $texts[$i];
            $text_width = static::getTextWidth($txt, $font, $size, $angle);
            # vi tri text (x)
            if (is_numeric($x)) $sx = $x;
            else {
                $lx = strtolower($x);
                $_gx = explode('=', $lx);
                if (count($_gx) == 2) {
                    if ($_gx[0] == 'c' || $_gx[0] == 'center' || $_gx[0] == 'mid' || $_gx[0] == 'middle' || $_gx[0] == 'm') {
                        $sx = static::getText2Number($_gx[1]) - $text_width / 2;
                    } else {
                        $sx = ($image_width - $text_width) / 2;
                    }
                } elseif ($lx == 'left') $sx = $margin;
                elseif ($lx == 'right') $sx = $image_width - ($text_width + $margin);
                else $sx = ($image_width - $text_width) / 2;
            }
            #insert text
            $image = self::insTaS($image, $size, $angle, $sx, $sy, $text_color, $boder_color, $font, $txt, $stroke_width);
            $sy += $th;
        }
        return $image;
    }


    /**
     * Tạo mã QR data
     *
     * @param string $string
     * @param integer $size
     * @return GdImage
     */
    protected static function _createQrCodeData($string, $size = 400)
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new ImagickImageBackEnd()
        );
        $writer = new Writer($renderer);
        $path = storage_path('logs/temp-qr-' . uniqid() . '.png');
        $writer->writeFile($string, $path);
        if (file_exists($path)) {
            $image = imagecreatefrompng($path);
            unlink($path);
            return $image;
        }
        throw new Exception('Can not create QR code');
    }

    /**
     * Tạo ảnh QR Code
     *
     * @param string $string
     * @param integer $size
     * @return Image
     */
    protected static function _createQrCodeImage($string, $size = 400): Image
    {
        return new static(static::_createQrCodeData($string, $size));
    }

    /**
     * add QR code to image
     *
     * @param string|GdImage $image
     * @param string $stringToQr
     * @param integer $size
     * @param string|int $x
     * @param string|int $y
     * @param integer $margin
     * @return GdImage
     */
    protected static function _addQrCode($image, $stringToQr, $size = 400, $x = "center", $y = "center", $margin = 0)
    {
        return static::addImage($image, static::_createQrCodeData($stringToQr, $size), $x, $y, $margin);
    }


    public static function createQrImageFile($filename, $string, $size = 400)
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new ImagickImageBackEnd()
        );
        $writer = new Writer($renderer);
        $writer->writeFile($string, $filename);
        return file_exists($filename);
    }

    public function __call($name, $arguments)
    {
        if (in_array($str = strtolower($name), ['addimage', 'insertimage', 'setimage', 'addimageitem', 'insertimageitem'])) {
            $this->data = static::_addImage($this->data, ...$arguments);
            $this->refresh();
            return $this;
        }
        if (in_array($str, ['addtext', 'inserttext'])) {
            $this->data = static::_addText($this->data, ...$arguments);
            $this->refresh();
            return $this;
        }
        if (in_array($str, ['insertqrcode', 'insertqr', 'addqr', 'addqrcode'])) {
            $this->data = self::_addQrCode($this->data, ...$arguments);
            return $this;
        }
    }
    public static function __callStatic($name, $arguments)
    {
        if (in_array($str = strtolower($name), ['addimage', 'insertimage', 'setimage', 'addimageitem', 'insertimageitem'])) {
            return self::_addImage(...$arguments);
        }
        if (in_array($str, ['addtext', 'inserttext'])) {
            return self::_addText(...$arguments);
        }
        if (in_array($str, ['qrcodedata', 'qrdata', 'createqrcodedata', 'createqrdata', 'getqrdata', 'getqrcodedata', 'getqr'])) {
            return self::_createQrCodeData(...$arguments);
        }
        if (in_array($str, ['createqrimage', 'makeqrimage', 'qrimage', 'createqrcodeimage', 'makeqrcodeimage', 'qrcodeimage'])) {
            return self::_createQrCodeImage(...$arguments);
        }
        if (in_array($str, ['addqr', 'addqrcode', 'insertqr', 'insertqrcode'])) {
            return self::_addQrCode(...$arguments);
        }
    }




    static function getTextWidth($text, $font = 'fonts/arial.ttf', $size = 10, $angle = 0)
    {
        $dims = imagettfbbox($size, $angle, $font, $text);
        $width = $dims[4] - $dims[6];
        return $width;
    }
    static function getTextHeight($text, $font = 'fonts/arial.ttf', $size = 10, $angle = 0)
    {
        $dims = imagettfbbox($size, $angle, $font, $text);
        $height = $dims[3] - $dims[5];
        return $height;
    }

    static function textQoute($text, $maxWidth = 100, $font = "fonts/arial.ttf", $size = 10, $angle = 0)
    {
        $maxTextWidth = $maxWidth;
        $texts = array('');
        if (is_array($text))
            $texts = $text;
        elseif (is_string($text))
            $texts = explode("\n", $text);
        elseif (is_numeric($text))
            $texts = array($text);
        $itext = array();
        $st = count($texts);
        $n = 0;
        for ($i = 0; $i < $st; $i++) {
            $txt = $texts[$i];
            $xt = explode(" ", $txt);
            $curtext = "";
            $crt = "";
            $c = count($xt);
            for ($j = 0; $j < $c; $j++) {
                $crt .= (($crt == "") ? "" : " ") . $xt[$j];
                if (static::getTextWidth($crt, $font, $size) <= $maxTextWidth) {
                    $curtext = $crt;
                } else {
                    $itext[$n] = $curtext;
                    $crt = $xt[$j];
                    $curtext = $xt[$j];
                    $n++;
                }
            }
            if ($curtext != "") {
                $itext[$n] = $curtext;
                $n++;
            }
        }
        return $itext;
    }

    static function getQuoteHeight($text = '', $font = null, $size = null, $max = 500)
    {
        $texts = static::textQoute($text, $max, $font, $size, 0);
        $t = count($texts);
        $th = static::getTextHeight((isset($texts[0]) ? $texts[0] : ""), $font, $size);
        for ($i = 1; $i < count($texts); $i++) {
            $tm = static::getTextHeight($texts[$i], $font, $size);
            if ($tm > $th) $th = $tm;
        }
        $tr = $th;
        $th *= 1.1;
        $texts_height = $th * $t;
        return $texts_height;
    }

    static function getiTextHeight($text = '', $size = null, $max = 500, $font = null, $angle = 0)
    {
        $texts = static::textQoute($text, $max, $font, $size, $angle);
        $t = count($texts);
        $th = 0;
        $last = 0;
        for ($i = 0; $i < $t; $i++) {
            $tm = static::getTextHeight($texts[$i], $font, $size, $angle);
            if ($tm > $th) $th = $tm;
            if ($i == $t - 1) $last = $tm;
        }
        $tr = $th;
        $th *= 1.1;
        $texts_height = $th * $t - ($last * 1.1 - $last);

        return $texts_height;
    }

    static function getiTextMaxHeight($text = '', $size = null, $max = 500, $font = null, $angle = 0)
    {
        $texts = static::textQoute($text, $max, $font, $size, $angle);
        $t = count($texts);
        $th = 0;
        $last = 0;
        for ($i = 0; $i < $t; $i++) {
            $tm = static::getTextHeight($texts[$i], $font, $size, $angle);
            if ($tm > $th) $th = $tm;
            if ($i == $t - 1) $last = $tm;
        }
        $th *= 1.1;
        return $th;
    }

    static function getiTextLine($text = '', $size = null, $max = 500, $font = null, $angle = 0)
    {
        $texts = static::textQoute($text, $max, $font, $size, $angle);
        $t = count($texts);
        return $t;
    }
    static function countTextLine($text = '', $size = null, $max = 500, $font = null, $angle = 0)
    {
        $texts = static::textQoute($text, $max, $font, $size, $angle);
        $t = count($texts);
        return $t;
    }

    static function getElx($array, $key = 'A', $char = '|')
    {
        if (!is_array($array)) return null;
        if (!is_string($key) && !is_numeric($key)) return $array;
        $p = explode($char, $key);
        $t = count($p);
        for ($i = 0; $i < $t; $i++) {
            $c = rtrim($p[$i]);
            if (is_array($array) && isset($array[$c])) {
                $array = $array[$c];
            } else {
                $array = null;
                $i += $t;
            }
        }

        return $array;
    }

    static function getText2Number($str)
    {
        $lbtg = array(
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9, 'J' => 10,
            'K' => 11, 'L' => 12, 'M' => 13, 'N' => 14, 'O' => 15, 'P' => 16, 'Q' => 17, 'R' => 18, 'S' => 19, 'T' => 20,
            'U' => 21, 'V' => 22, 'W' => 23, 'X' => 24, 'Y' => 25, 'Z' => 26, '.' => '.',


        );
        $txt = strtoupper($str);
        $s = '';
        $l = strlen($txt);

        for ($i = 0; $i < $l; $i++) {
            $c = substr($txt, $i, 1);
            $gt = static::getElx($lbtg, $c);

            if (is_numeric($gt) || is_string($gt)) {
                $s .= $gt;
            }
        }
        return ((int) $s);
    }
}
