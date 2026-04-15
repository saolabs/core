<?php
namespace Saola\Core\Crawlers;
// use voku\helper\HtmlDomParser;
// use Sunra\PhpSimple\HtmlDomParser;
use Saola\Core\Html\Dom\HtmlDomParser;


use Carbon\Carbon;

use Saola\Core\Files\Image;
use Saola\Core\Http\Client;
use Saola\Core\Http\Curl;
use Saola\Core\Files\Filemanager;

trait Crawl{
    
    protected $folder = 'posts';


    protected $resources = [];

    /**
     * api class
     *
     * @var Api
     */
    public $api = null;

    
    protected $crawlOwnerID = 0;

    public function setCrawlOwnerID($id = 0)
    {
        $this->crawlOwnerID = $id;
    }


    public function setup($folder = 'posts', $api = null)
    {
        $this->folder = $folder;
        if($api){
            $this->api = $api;
        }
        else{
            $this->api = app(Client::class);
        }
        
    }
     /**
     * loại bỏ các thẻ không cần thiết
     * @param string $string $nội dung đầu vào
     * @param string $except Các selector cần loại bỏ. phân cách bằng dấu ^
     * 
     * @return string dử liệu trả về là chuỗi đã qua xử lý
     */
    public function except($string, $except){
        $string = HtmlDomParser::str_get_html($string);
        if($string == '')
            return '';
        foreach($string->find('figure') as $v){
            $v->attr = [];
        }
        $string->load($string->save());
        foreach($string->find('a') as $v){
            unset($v->attr['href']);
        }
        // $string->load($string->save());
        // foreach($string->find('table') as $value){
        //     $value->outertext.='<br>';
        // }
        $string->load($string->save());
        $except = explode('^', $except);
        foreach($except as $v){
            if($v != '')
                foreach ($string->find(trim($v)) as $value){
                    $value->outertext = '';
                };
        }
        $string->load($string->save());
        return $string->innertext;
    }

    /**
     * lưu trữ tài nguyên
     * @param App\Models\CrawlerFrame $frame
     * @param string $string
     * @param string $qid uniqid
     * @return string chuổi sau khi được thay thế các link
     */
    public function saveResources($frame, $string, $qid, $folder = null) {
        if(!$folder) $folder = $this->folder;
        $string = HtmlDomParser::str_get_html($string);
        foreach($string->find('video,source,img') as $value){
            if(isset($value->attr['src']) && $value->attr['src']){
                if(isset($value->attr['src']) && $value->attr['src'] != ''){
                    $url = strpos($value->attr['src'], 'http') === 0 ? $value->attr['src'] : $frame->url.$value->attr['src'];
                    if($src = @$this->uploadFromUrl($url, $qid, $folder)){
                        $value->attr['src'] = $src;
                    }
                    
                }
            }
        }
        $string->load($string->save());
        return $string->innertext;
    }


    /**
     * lấy từ khóa từ trang crawl
     * @param nixed $html
     * @param string $tag selector
     * @param string tag attr
     * @return array
     */
    public function addTag($html, $tag, $attr_tag){
        $tag_list = [];
        
        foreach($html->find($tag) as $value){
            $tags = $this->getContent($value, $attr_tag);
            
            if($tagList = $this->tags->createTags($tags)){
                foreach ($tagList as $t) {
                    $tag_list[] = $t->{MODEL_PRIMARY_KEY};
                }
            }
        }
        return $tag_list;
    }

    /**
     * upload tài nguyên từ url
     * @param string $url
     * @param string $qid id duy nhất
     * @param string $folder thư mục lưu file
     * @return string url tài nguyên
     */
    public function uploadFromUrl($url, $qid, $folder=null){
        if(!$folder) $folder = $this->folder;

        if(preg_match('/^image\//si', $url)){
            $filemanager = new Filemanager(public_path($folder));
            $name = $qid . md5(microtime());
            if($file = $filemanager->saveBase64($url, $name)){
                $this->resources[] = $file->filename;
                $path = $folder.'/'.$file->filename;
                return url_relative(asset($path));
            }
            else{
                return null;
            }
        }

        $name = $qid.'-'.substr($url, strrpos($url, '/') + 1);
        if(count($s = explode('?', $name)) == 2){
            $name = $s[0];
        }
        $path = 'static/'.$folder.'/'.$name;
        
        $contents = @file_get_contents($url);
        $fp = public_path($path);
        @file_put_contents($fp, $contents);
        if(!file_exists($fp)) return null;
        $this->resources[] = $name;
        return url_relative(asset($path));
    }

    /**
     * upload hình đạ diện cho bài viết
     * @param string $url
     * @param string $qid id duy nhất
     * @param string $folder thư mục lưu file
     * @return string tên file
     */
    public function saveFeatureImage($url, $qid, $folder=null, $width = null, $height = null, $sub = null){
        if(!$folder) $folder = $this->folder;

        $name = $qid.'-'.substr($url, strrpos($url, '/') + 1);
        if(count($s = explode('?', $name)) == 2){
            $name = $s[0];
        }
        $image = new Image($url);
        if($image->save($fp = public_path('static/'.$folder.'/'.$name))){
            
            $image->resizeAndCrop(90,90);
            $image->save(public_path('static/'.$folder.'/90x90/'.$name));
            if($width && $height){
                $a = $sub?$sub:$width . 'x' . $height;
                $image->restore();
                $image->resizeAndCrop($width, $height);
                $image->save(public_path('static/'.$folder.'/'.$a.'/'.$name));
            }
            return $image->getStoredFilename();
        }
        
        return null;
    }

    /**
     * lấy nội dung từ thẻ html
     * @param nixed $html thẽ
     * @param string $attr thuộc tính của thẻ
     * @return string nội dung 
     */
    public function getContent($html, $attr){
        $result = '';
        
        if($attr){
            if(isset($html->attr[$attr]))
                $result = $html->attr[$attr];
        }elseif(isset($html->innertext)){
            $result = $html->innertext;
        }
            
        $result = preg_replace('/&#39;/', '', trim($result));
        return preg_replace('/&quot;/', '"', $result);
    }


    public function sendRequest($url, $source_type = 'ssr')
    {
        if($source_type == 'csr' || preg_match('/(lazada|shopee|sendo)\.vn\//i', $url)){
            $url = env('CRAWLER_BOT_API') .'?url='.$url;
            
        }
        try{
            $rs = $this->api->get($url);
            if($rs){
                if(is_object($rs)){
                    $content = $rs->getBody()->getContents();
                }else{
                    $content = $rs;
                }
                
            }
            elseif(function_exists('curl_init')){
                $curl = new Curl($url);
                $content = $curl->get();
            }else{
                $content = file_get_contents($url);
            }
        }catch(\Exception $e){
            $content = null;
        }
        return $content;
    }

    
    public function getHtmlRaw($url)
    {
        try{
            $rs = $this->api->get($url);
            if($rs){
                $content = $rs->getBody()->getContents();
            }
            else{
                $curl = new Curl($url);
                $content = $curl->get();
            } 
        }catch(\Exception $e){
            $content = null;
        }
        return $content;
    }

    
    public function toDom($html)
    {
        return HtmlDomParser::str_get_html($html);
    }

    /**
     * lấy nội dung từ trang
     * @param string $url
     * @return htmlDom
     */
    public function getHtml($url, $source_type = 'ssr'){
        return $this->toDom($this->sendRequest($url, $source_type));
    }


    public function parseUrl($url, $frame = null)
    {
        $u = $url;
        if(strpos($url, 'http') === 0) return $u;
        if(substr($url, 0, 2) == '//'){
            if($frame){
                $prot = explode('://', $frame->url);
                if(count($prot) == 2){
                    $u = $prot[0]. ':'.$u;
                }else{
                    $u = 'http:'.$u;
                }
            }else{
                $u = 'http:'.$u;
            }
        }elseif (substr($url, 0, 1) == '/') {
            if($frame){
                $u = rtrim($frame->url, '/') . $u;
            }
        }
        return $u;
    }
}

?>