<?php

use Saola\Core\Magic\Arr as CrazyArr;
use Carbon\Carbon;
use Illuminate\Support\Str;
define('__RANDOM_VALUE__', md5(uniqid() . time() . rand(10000, 99999)));

if (!defined('MODEL_PRIMARY_KEY')) {
    define('MODEL_PRIMARY_KEY', env('MODEL_PRIMARY_KEY', 'id'));
}
if (!function_exists('carbon_datetime')) {
    /**
     * lấy thời gian theo carbon
     * @param string $datetime
     * @param string $format
     * @return string
     */
    function carbon_datetime($time = null, $format = null)
    {
        if (!$time) return false;
        try {
            $carbon = Carbon::parse($time);
            if (!$format) return $carbon;
            switch (strtolower($format)) {
                case 'date':
                    return $carbon->toDateString();
                    break;
                case 'locale':
                    return $carbon->toDateTimeLocalString();
                    break;
                case 'datetime':
                    return $carbon->toDateTimeString();
                    break;
                case 'datetime:millisecond':
                case 'datetime:ms':
                case 'datetimems':
                case 'datetime-ms':
                case 'dtms':

                    return $carbon->toDateTimeString('millisecond');
                    break;
                case 'time':
                    return $carbon->getTimestamp();
                    break;
                case 'timestamp':
                    return $carbon->getTimestamp();
                    break;
                case 'timezone':
                    return $carbon->getTimezone();
                    break;

                case 'dbdate':
                    return $carbon->format('Y-m-d');
                    break;
                case 'iso':
                case 'timetz':
                    return $carbon->format('Y-m-d\TH:i:s.uP');
                    break;


                default:
                    return $carbon->format($format);
                    break;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return null;
    }
}

if (!function_exists('entities')) {
    /**
     * htmlentities
     */
    function entities($any = null)
    {
        return CrazyArr::entities($any);
    }
}

if (!function_exists('parseFunctionCall')) {
    function parseFunctionCall($input)
    {
        // Remove extra spaces and parentheses
        $input = trim(preg_replace('/\s+|\(.*?\)/', ' ', $input));

        // Check for valid format with letters, numbers, and underscores in function name
        if (!preg_match('/^([a-zA-Z0-9_]+)(?:\((.*)\))$/', $input, $matches)) {
            return []; // Return empty array if invalid
        }

        // Extract function name and parameter string
        $functionName = $matches[1];
        $paramsStr = isset($matches[2]) ? $matches[2] : '';

        // Process parameter string
        $params = [];
        if ($paramsStr) {
            $paramPairs = explode(',', $paramsStr);
            foreach ($paramPairs as $paramPair) {
                $paramPair = trim($paramPair);

                // Check for named parameter with value
                if (preg_match('/^(\w+)\s*(=)(.*)$/', $paramPair, $matches)) {
                    $paramName = $matches[1];
                    $paramValue = isset($matches[3]) ? $matches[3] : null;

                    // Process parameter value
                    if (strtolower($paramValue) === 'null') {
                        $paramValue = null;
                    } elseif (is_numeric($paramValue)) {
                        $paramValue = (float) $paramValue;
                    } elseif (preg_match('/^[\'\"](.+?)\'\"]$/', $paramValue, $matches)) {
                        $paramValue = stripslashes($matches[1]);
                    }

                    $params[$paramName] = $paramValue;
                } else { // Assume positional parameter
                    $params[] = $paramPair;
                }
            }
        }

        // Return result
        return [
            'function' => $functionName,
            'params' => $params
        ];
    }
}

if (!function_exists('get_domain')) {
    /**
     * lấy tên miên dược cấu hình
     */
    function get_domain()
    {
        static $domain = null;
        if (!$domain) {
            $domain = strtolower(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
        }
        return $domain;
    }
}

if (!function_exists('json_path')) {
    /**
     * lấy dường dẫn từ thư mục json
     * @param string $path
     * @return string
     */
    function json_path($path = null)
    {
        return base_path('json') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('is_email')) {
    /**
     * kiểm tra giá trị nhập vào có phải email hay không
     *
     * @param string $str
     * @return boolean
     */
    function is_email($str)
    {
        if (filter_var($str, FILTER_VALIDATE_EMAIL)) return true;
        return false;
    }
}
if (!function_exists('is_phone_number')) {
    /**
     * kiểm tra giá trị nhập vào có phải sdt hay không
     *
     * @param string $str
     * @return boolean
     */
    function is_phone_number($str)
    {
        return preg_match('/^(\+84|0)+[0-9]{9,10}$/si', str_replace([' ', '-', '.'], ' ', $str));
    }
}


if (!function_exists('crazy_arr')) {
    /**
     * tạo đối tượng Arr
     * @param mixed $array
     * @return CrazyArr
     */
    function crazy_arr($array = null)
    {
        return new CrazyArr($array);
    }
}

if (!function_exists('object_to_array')) {
    /**
     * convert object thành mảng
     * @param object $d
     * 
     * @return array
     */
    function object_to_array($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map(__FUNCTION__, $d);
        } else {
            return $d;
        }
    }
}


if (!function_exists('string_to_array')) {
    function string_to_array($s)
    {
        parse_str($s, $a);
        if ($a) return $a;

        return [];
    }
}

if (!function_exists('to_array_by_nl')) {
    function to_array_by_nl($string)
    {
        $a = explode("\r\n", $string);
        $b = [];
        if ($a) {
            foreach ($a as $v) {
                if ($c = trim($v)) {
                    $b[] = $c;
                }
            }
        }
        return $b;
    }
}

if (!function_exists('nl2array')) {
    function nl2array($string, $checkEmpty = true)
    {
        $b = [];
        if ($string) {
            if ($checkEmpty) {
                if (count($a = explode("\r\n", $string)) > 1) {
                    foreach ($a as $v) {
                        if ($c = trim($v)) {
                            $b[] = $c;
                        }
                    }
                } elseif (count($d = explode("\n", $string)) > 1) {
                    foreach ($d as $v) {
                        if ($c = trim($v)) {
                            $b[] = $c;
                        }
                    }
                } elseif (count($d = explode("
", $string)) > 1) {
                    foreach ($d as $v) {
                        if ($c = trim($v)) {
                            $b[] = $c;
                        }
                    }
                } elseif (strlen($string)) {
                    $b = [$string];
                }
            } else {
                if (count($a = explode("\r\n", $string)) > 1) {
                    $b = $a;
                } elseif (count($d = explode("\n", $string)) > 1) {
                    $b = $d;
                } elseif (count($d = explode("
", $string)) > 1) {
                    $b = $d;
                } elseif (strlen($string)) {
                    $b = [$string];
                }
            }
        } elseif (strlen($string)) {
            $b = [$string];
        }
        return $b;
    }
}


if (!function_exists('get_first_value')) {
    /**
     * tạo đối tượng Arr
     * @param mixed $array
     * @return CrazyArr
     */
    function get_first_value($array, $key, $default = null)
    {
        if (!is_array($array) || !($count = count($array))) {
            if (is_array($key)) {
                if (CrazyArr::isNumericKeys($key)) {
                    $a = [];
                    for ($i = 0; $i < count($key); $i++) {
                        $a[$key[$i]] = $default;
                    }
                    return $a;
                }
                return $key;
            }
            return $default;
        }
        if (is_array($key)) {
            $a = [];
            if (CrazyArr::isNumericKeys($key)) {
                $t = count($key);

                for ($i = 0; $i < $t; $i++) {
                    $a[$key[$i]] = get_first_key_value($array, $count, $key[$i], $default);
                }
                return $a;
            }
            foreach ($key as $k => $def) {
                $a[$k] = get_first_key_value($array, $count, $k, $def);
            }

            return $a;
        }
        return get_first_key_value($array, $count, $key, $default);
    }
}
if (!function_exists('get_first_key_value')) {
    /**
     * tạo đối tượng Arr
     * @param mixed $array
     * @return CrazyArr
     */
    function get_first_key_value($array, $count = 0, $key = null, $default = null)
    {
        for ($j = 0; $j < $count; $j++) {
            $v = $array[$j]->{$key};
            if (!(!$v && !is_numeric($v))) {
                return $v;
            }
        }
        return $default;
    }
}

if (!function_exists('array_val_type')) {
    /**
     * kiểm tra danh sách giá trị có đúng kiểu hay ko
     *
     * @param array $array
     * @param string $type
     * @return bool
     */
    function array_val_type(array $array = [], string $type = 'any'): bool
    {
        $type = strtolower($type);
        if ($type == 'any' || $type == '*') return true;
        if ($type == 'string') {
            foreach ($array as $key => $value) {
                if (!is_string($value)) return false;
            }
        } elseif ($type == 'number') {
            foreach ($array as $key => $value) {
                if (!is_numeric($value)) return false;
            }
        } elseif ($type == 'bool' || $type == 'boolean') {
            foreach ($array as $key => $value) {
                if (!is_bool($value)) return false;
            }
        } else {
            foreach ($array as $key => $value) {
                if (gettype($value) != $type) return false;
            }
        }
        return true;
    }
}

if (!function_exists('isSecure')) {
    function isSecure()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }
}

if (!function_exists('get_video_from_url')) {
    /**
     * lây Thông tin video từ url.
     * hỗ trợ youtube là chính =)))
     * @param string $url
     * 
     * @return Saola\Core\Magic\Arr
     */
    function get_video_from_url($url = null)
    {
        if (!$url) return null;
        $a = [];
        $protoccol = 'https';
        if (preg_match_all('/.*youtu\.be\/(.*?)($|\?|#)/si', $url, $m)) {
            $a['id'] = $m[1][0];
            $a['server'] = 'youtube';
            $a['thumbnail'] = $protoccol . '://img.youtube.com/vi/' . $a['id'] . '/hqdefault.jpg';
            $a['embed_url'] = $protoccol . "://www.youtube.com/embed/$a[id]";
        } elseif (preg_match_all('/youtube\.com\/watch\?.*v=(.*?)($|&|#)/si', $url, $m)) {
            $a['id'] = $m[1][0];
            $a['server'] = 'youtube';
            $a['thumbnail'] = $protoccol . '://img.youtube.com/vi/' . $a['id'] . '/hqdefault.jpg';
            $a['embed_url'] = $protoccol . "://www.youtube.com/embed/$a[id]";
        } elseif (preg_match_all('/\.*vimeo.com\/(.*?)($|\?)/si', $url, $m)) {
            $v = explode('/', $m[1][0]);
            $a['id'] = $v[count($v) - 1];
            $a['server'] = 'vimeo';
            $hash = unserialize(file_get_contents($protoccol . "://vimeo.com/api/v2/video/" . $a['id'] . ".php"));
            $a['thumbnail'] = $hash[0]['thumbnail_large'];
            $a['embed_url'] = $protoccol . "://player.vimeo.com/video/$a[id]?wmode=opaque";
        } elseif (preg_match_all('/.*facebook.com\/(.*?)\/videos\/(.*?)\//si', $url, $m)) {
            $a['id'] = $m[2][0];
            $a['page_id'] = $m[1][0];
            $a['server'] = 'facebook';
            $a['thumbnail'] = null;
            $ac = urlencode(url('/'));
            $a['embed_url'] = "https://www.facebook.com/v2.0/plugins/video.php?allowfullscreen=true&container_width=620&href=$ac%2F$a[page_id]%2Fvideos%2Fvb.$a[page_id]%2F$a[id]%2F%3Ftype%3D3&locale=en_US&sdk=joey";
        }
        if (!$a) return null;
        $obj = new CrazyArr($a);
        return $obj;
    }
}

if (!function_exists('get_array_element')) {
    /**
     * lấy ra thông tin phần tử bao gồm key và value
     * @param mixed $needle
     * @param array $array
     * 
     * @return array
     */
    function get_array_element($needle = null, $array = [])
    {
        if (is_array($array)) {
            if ((is_string($needle) || is_numeric($needle)) && array_key_exists($needle, $array)) {
                return [
                    'key' => $needle,
                    'value' => $array[$needle]
                ];
            } else {
                foreach ($array as $key => $value) {
                    if ($needle == $value) return compact('key', 'value');
                }
            }
        }
        return [];
    }
}

if (!function_exists('array_contains')) {
    /**
     * kiểm tra một mảng có chứa các phần tử của mảng khác hay không
     *
     * @param array $wrapper Mảng chính
     * @param array $child mảng con
     * @return bool
     */
    function array_contains(array $wrapper = [], array $child = []): bool
    {
        return count(array_intersect($wrapper, $child)) == count($child);
    }
}

if (!function_exists('array_check_keys')) {
    /**
     * kiểm tra danh sách key có tồn tại trong mảng hay ko
     *
     * @param array $array
     * @param array $keys
     * @return bool
     */
    function array_check_keys(array $array = [], ...$keys): bool
    {
        if (isset($keys[0]) && is_array($keys[0])) {
            $check = $keys[0];
        } else {
            $check = $keys;
        }
        return array_contains(array_keys($array), $check);
    }
}


if (!function_exists('array_has_any')) {
    /**
     * kiểm tra danh sách key có tồn tại trong mảng hay ko
     *
     * @param array $array
     * @param array $keys
     * @return bool
     */
    function array_has_any(array $array = [], ...$keys): bool
    {
        if (isset($keys[0]) && is_array($keys[0])) {
            $check = $keys[0];
        } else {
            $check = $keys;
        }
        $a = count(array_intersect(array_keys($array), $check)) > 0;
        return $a;
    }
}


if (!function_exists('array_any_val')) {
    /**
     * kiểm tra danh sách giá trị có tồn tại trong mảng hay ko
     *
     * @param array $array
     * @param array $keys
     * @return bool
     */
    function array_any_val(array $array = [], ...$keys): bool
    {
        if (isset($keys[0]) && is_array($keys[0])) {
            $check = $keys[0];
        } else {
            $check = $keys;
        }
        $a = count(array_intersect(array_values($array), $check)) > 0;
        return $a;
    }
}


if (!function_exists('array_remove_key')) {
    /**
     * kiểm tra danh sách giá trị có tồn tại trong mảng hay ko
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    function array_remove_key(array $array = [], ...$keys): array
    {
        if (isset($keys[0]) && is_array($keys[0])) {
            $check = $keys[0];
        } else {
            $check = $keys;
        }
        if ($check) {
            foreach ($check as $key) {
                unset($array[$key]);
            }
        }
        return $array;
    }
}


if (!function_exists('array_copy')) {
    /**
     * lấy danh sách phần tử mảng ban đầu theo danh sách key đã cho. 
     * nếu key nào không có giá trị sẽ trả về null
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    function array_copy(array $array = [], ...$keys): array
    {
        if (isset($keys[0]) && is_array($keys[0])) {
            $list = $keys[0];
        } else {
            $list = $keys;
        }
        $newArray = [];
        foreach ($list as $key) {
            if (array_key_exists($key, $array)) {
                $newArray[$key] = $array[$key];
            } else {
                $newArray[$key] = null;
            }
        }
        return $newArray;
    }
}




if (!function_exists('str_eval')) {
    /**
     * dien chu vao doan
     * @param string $text
     * @param array $data
     * @param int $char_type
     * @param string $char_start
     */
    function str_eval($text = null, $data = null, $char_type = 0, $char_start = '$')
    {
        $type = [
            0 => ['start' => '{', 'end' => '}'],
            1 => ['start' => '[', 'end' => ']'],
            2 => ['start' => '(', 'end' => ')'],
            3 => ['start' => '/*', 'end' => '*/'],
            5 => ['start' => '<', 'end' => '>'],
        ];
        $chars = ['$', '@', '%', '', '*', 'sd:'];
        if (!is_string($text) && !is_array($data)) {
            return $text;
        }

        $start = '{';
        $end = '}';
        $char = '';
        if (isset($type[$char_type])) {
            $ty = $type[$char_type];
            $start = $ty['start'];
            $end = $ty['end'];
        } elseif (is_string($char_type)) {
            $start = $char_type;
            if (strlen($char_type) > 1) {
                $end = '';
                $n = strlen($char_type) - 1;
                for ($i = $n; $i >= 0; $i--) {
                    $end .= substr($char_type, $i, 1);
                }
            } else {
                $end = $start;
            }
        }
        if (in_array($char_start, $chars)) {
            $char = $char_start;
        } elseif ($char_start && isset($chars[$char_start])) {
            $char = $chars[$char_start];
        } elseif ($char_start) {
            $char = $char_start;
        }
        $find = [];
        $replace = [];
        $find2 = [];
        $replace2 = [];

        foreach ($data as $name => $val) {
            if (is_array($val)) {
                continue;
            }

            $find[] = $start . $char . $name . $end;
            $replace[] = $val;
        }

        $txt = str_replace($find, $replace, $text);
        $txt = str_replace($find, $replace, $txt);
        $txt = str_replace($find, $replace, $txt);
        $txt = str_replace($find, $replace, $txt);
        $sEsc = [
            '{' => '\{',
            '{{' => '\{\{',
            '[' => '\[',
            '(' => '\('
        ];
        $eEsc = [
            '}' => '\}',
            '}}' => '\}\}',
            ']' => '\]',
            ')' => '\)'
        ];

        // $a = (array_key_exists($start, $sEsc)? $sEsc[$start]: $start) . ($char=='$'?'\$':$char) . '[A-z0-9_\:\.]' . array_key_exists($end, $eEsc)? $eEsc[$end]: $end ;
        // $txt = preg_replace('/'.$a.'/i', '', $txt);

        return $txt;
    }
}



if (!function_exists('to_number')) {
    /**
     * chuan so
     * @param string $value
     */
    function to_number($value)
    {
        if (is_numeric($value)) {
            $number = (((int) $value) == $value) ? ((int) $value) : ((float) $value);
            return $number;
        }
        return 0;
    }
}







if (!function_exists('vnclean')) {
    /**
     * Chuyển tất cả các chữ cái trong chuỗi tiếng Việt về không dấu
     * @param string $string
     * @return string
     */
    function vnclean($string)
    {

        // tạm thời chưa có nên dùng tạm của php
        return \Saola\Core\Magic\Str::clearVi($string);
    }
}

if (!function_exists('vntolower')) {
    /**
     * Chuyển tất cả các chữ cái trong chuỗi tiếng Việt về chữ viết thường
     * @param string $string
     * @return string
     */
    function vntolower($string)
    {

        // tạm thời chưa có nên dùng tạm của php
        return \Saola\Core\Magic\Str::vnToLower($string);
    }
}


if (!function_exists('vntoupper')) {
    /**
     * Chuyển tất cả các chữ cái trong chuỗi tiếng Việt về chữ viết Hoa
     * @param string $string
     * @return string
     */
    function vntoupper($string)
    {

        // tạm thời chưa có nên dùng tạm của php
        return \Saola\Core\Magic\Str::vnToUpper($string);
    }
}

if (!function_exists('vnucfirst')) {
    /**
     * Viết hoa chữ đầu dòng trong chuỗi tiếng Việt
     * @param string $string
     * @return string
     */
    function vnucfirst($string)
    {

        // tạm thời chưa có nên dùng tạm của php
        return ucfirst($string);
    }
}


if (!function_exists('vnucwords')) {
    /**
     * Viết hoa chữ đầu mỗi từ trong chuỗi tiếng Việt
     * @param string $string
     * @return string
     */
    function vnucwords($string)
    {

        // tạm thời chưa có nên dùng tạm của php
        return ucwords($string);
    }
}

if (!function_exists('str_slug')) {
    /**
     * lấy slug của một chuỗi
     * @param string $string
     * 
     * @return string
     */
    function str_slug(...$params)
    {

        // tạm thời chưa có nên dùng tạm của php
        return Str::slug(...$params);
    }
}
if (!function_exists('str_plural')) {
    /**
     * lấy chuỗi dạng số nhiều
     * @param string $string
     * 
     * @return string
     */
    function str_plural(...$params)
    {

        // tạm thời chưa có nên dùng tạm của php
        return Str::plural(...$params);
    }
}
if (!function_exists('str_limit')) {
    /**
     * sub string
     * @param string $string
     * 
     * @return string
     */
    function str_limit(...$params)
    {

        // tạm thời chưa có nên dùng tạm của php
        return Str::limit(...$params);
    }
}


if (!function_exists('get_validation_params')) {

    function get_validation_params(array $parameters = [])
    {
        $pars = [];
        foreach ($parameters as $p) {
            if ($par = trim($p)) {
                $pars[] = $par;
            }
        }
        return $pars;
    }
}


if (!function_exists('strtodate')) {
    /**
     * convert to date format
     *
     * @param string $string
     * @param string $returnType
     * @return string|array
     */
    function strtodate(string $string, $returnType = 'array')
    {
        $string = trim($string);
        $y = 0;
        $m = 0;
        $d = 0;
        $k = null;
        $l = 0;
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/i', $string)) {
            $k = '/';
        } elseif (preg_match('/^\d{1,2}\-\d{1,2}\-\d{4}$/i', $string)) {
            $k = '-';
        } elseif (preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}$/i', $string)) {
            $k = '-';
            $l = 1;
        } elseif (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/i', $string)) {
            $k = '.';
            $l = 3;
        }

        if ($k) {
            $dd = explode($k, $string);
            if ($l == 1) {
                if (is_date($dd[2], $dd[1], $dd[0])) {
                    $y = $dd[0];
                    $m = $dd[1];
                    $d = $dd[2];
                }
            } elseif ($l == 3 || $k = '/') {
                if (is_date($dd[0], $dd[1], $dd[2])) {
                    $y = $dd[2];
                    $m = $dd[1];
                    $d = $dd[0];
                } elseif (is_date($dd[1], $dd[0], $dd[2])) {
                    $y = $dd[2];
                    $m = $dd[0];
                    $d = $dd[1];
                }
            } elseif (is_date($dd[0], $dd[1], $dd[2])) {
                $y = $dd[2];
                $m = $dd[1];
                $d = $dd[0];
            }
        }
        if ($y && $m && $d) return $returnType == 'string' ? "$y-$m-$d" : [
            'day' => $d,
            'month' => $m,
            'year' => $y
        ];
        return $returnType == 'string' ? '' : [];
    }
}

if (!function_exists('is_date')) {
    function is_date($day = 0, $month = 0, $year = 0)
    {
        if (!$day || !$month || !$year || !is_numeric($day) || !is_numeric($month) || !is_numeric($year) || $day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }
        $day = (int) $day;
        $month = (int) $month;
        $year = (int) $year;
        $status = false;
        switch ($month) {
            case '2':
                if ($day == 29) {
                    if ($year % 100 == 0) {
                        $year = $year / 100;
                    }
                    $status = ($year % 4 == 0);
                } elseif ($day > 29) $status =  false;
                else $status = true;

                break;
            case '4':
            case '6':
            case '9':
            case '11':
                $status = ($day < 31);
                break;
            default:
                $status = ($day < 32);
                break;
        }
        return $status;
    }
}


if (!function_exists('get_time_seconds')) {
    function get_time_seconds($time = null)
    {
        $xs = [1, 60, 3600];
        $time = $time ? $time : date('G:i:s');
        $times = explode(':', $time);
        $timeSeconds = 0;
        $c = count($times);
        if ($c <= 3) {
            $n = 0;
            for ($i = $c - 1; $i >= 0; $i--) {
                $nb = (int) $times[$i];
                $timeSeconds += $nb * $xs[$n];
                $n++;
            }
        }
        return $timeSeconds;
    }
}

if (!function_exists('check_time_format')) {
    function check_time_format($time = null)
    {
        if (!is_string($time) || !$time) return false;
        if (($t = count($arrTime = explode(':', $time))) >= 2) {
            if ($t > 3) return false;
            $arrMax = [23, 59, 59];
            $end = ($t > 3) ? 3 : $t;
            for ($i = 0; $i < $end; $i++) {
                if (!is_numeric($arrTime[$i])) return false;
                $s = (int) $arrTime[$i];
                if ($s < 0 || $s > $arrMax[$i]) return false;
            }
            return $t;
        }
        return false;
    }
}


if (!function_exists('get_working_hours')) {
    function get_working_hours($intTime = 0)
    {
        $time = to_number($intTime);
        $hours = floor($time / 3600);
        return $hours;
    }
}

if (!function_exists('get_date_time_array')) {
    function get_date_time_array($time = null)
    {
        if ($time) $dateTime = date("Y-m-d-H-i-s", $time);
        else $dateTime = date("Y-m-d-H-i-s");
        $dateKeys = ['year', 'month', 'day', 'hour', 'minute', 'second'];
        $data = [];
        $p = explode('-', $dateTime);
        foreach ($p as $key => $value) {
            $data[$dateKeys[$key]] = $value;
        }
        return $data;
    }
}

if (!function_exists('parse_date_time')) {
    function parse_date_time($dateTime = null)
    {
        if (!$dateTime) $dateTime = date("Y-m-d H:i:s");
        if (!is_string($dateTime)) return [];
        $dateKeys = ['year', 'month', 'day', 'hour', 'minute', 'second'];
        $dateKeyGroup = [['year', 'month', 'day'], ['hour', 'minute', 'second']];
        $data = ['year' => 1980, 'month' => '01', 'day' => '01', 'hour' => '00', 'minute' => '00', 'second' => '00'];

        $old = '';
        $p = explode(' ', trim($dateTime));
        $i = -1;
        $de = ['-', ':'];
        foreach ($p as $path) {
            if ($path) $i++;
            else continue;
            if ($i >= 2) break;
            $d = $de[$i];
            if ($d == $old) continue;
            if (count($time = explode($d, $path)) >= 1) {
                for ($j = 0; $j < 3; $j++) {
                    if (isset($time[$j])) {
                        $data[$dateKeyGroup[$i][$j]] = $time[$j];
                    }
                }
            }
        }
        return $i == 1 ? $data : [];
    }
}

if (!function_exists('get_date_range')) {
    /**
     * kiểm tra và lấy dữ liệu date range
     * @param string $string chuỗi chứa thời gian cần kiểm tra
     * @param string $split_char chuỗi ngăn cch1 2 vế
     * @return array Trả về mảng định dạng thời gian hoạc mảng rỗng
     */
    function get_date_range($string, $split_char = ' - ')
    {
        if (count($dates = explode($split_char, $string)) == 2) {
            if (($from = strtodate($dates[0])) && ($to = strtodate($dates[1]))) {
                return compact('from', 'to');
            }
        }
        return [];
    }
}
if (!function_exists('get_datetime_range')) {
    /**
     * kiểm tra và lấy dữ liệu date range
     * @param string $string chuỗi chứa thời gian cần kiểm tra
     * @param string $split_char chuỗi ngăn cch1 2 vế
     * @return array Trả về mảng định dạng thời gian hoạc mảng rỗng
     */

    function get_datetime_range($string, $split_char = ' - ')
    {
        $dates = [];
        if (count($date = array_map('trim', explode($split_char, $string))) == 2) {
            foreach ($date as $datetime) {
                if ($datet = parse_date_time($datetime)) {
                    if (!is_date($datet['day'], $datet['month'], $datet['year'])) return false;
                    $dates[] = "$datet[day]-$datet[month]-$datet[year] $datet[hour]:$datet[minute]:$datet[second]";
                }
            }
            return [
                'from' => $dates[0],
                'to' => $dates[1]
            ];
        }
        return false;
    }
}


if (!function_exists('get_date_str')) {
    /**
     * Ham lay chuoi date
     */
    function get_date_str($string = null)
    {
        if (is_numeric($string)) {
            return date("Y-m-d", time() + $string * 3600 * 24);
        }
        if ($d = parse_date_time($string)) {
            return "$d[year]-$d[month]-$d[day]";
        }
        return null;
    }
}




if (!function_exists('get_year_options')) {
    /**
     * lấy option các năm cho thẻ select
     * @param string $lang
     * @param int|string $start năm bắt dầu [=năm hiện tại]
     * @param int|string $end $năm kết thúc
     * @return array
     */
    function get_year_options($lang = 'vi', $start = 'current', $end = 'current')
    {
        $l = strtolower($lang);
        $langs = [
            'en' => 'Year',
            'vi' => 'Năm'
        ];
        $label = $l ? (isset($langs[$l]) ? $langs[$l] : $lang) : 'Chọn một';
        $data = [$label];

        $current = to_number(date('Y'));
        $years = ['current' => $current, 'last' => $current - 1, 'next' => $current + 1];

        if (!is_numeric($start)) {
            if (is_string($start) && array_key_exists($sk = strtolower($start), $years)) $start = $years[$sk];
            else $start = $current;
        } elseif ($start < 0) {
            $start = $current + $start;
        }
        if (!is_numeric($end)) {
            if (is_string($end) && array_key_exists($ek = strtolower($end), $years)) $end = $years[$ek];
            else $end = $current;
        } elseif ($end < 0) {
            $end = $current + $end;
        }

        if ($end > $start) {
            for ($i = $start; $i <= $end; $i++) {
                $data[$i] = $i;
            }
        } else {
            for ($i = $start; $i >= $end; $i--) {
                $data[$i] = $i;
            }
        }
        return $data;
    }
}
if (!function_exists('get_month_options')) {
    /**
     * lấy option các tháng cho thẻ select
     * @param string $lang 
     * @param bool $increment Thứ tự tăng hay giảm
     * @return array
     */
    function get_month_options($lang = 'vi', $increment = true)
    {

        $en = [
            'Month', 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        $vi = ['Tháng'];
        for ($i = 1; $i < 13; $i++) {
            $vi[$i] = 'Tháng ' . $i;
        }
        $months = strtolower($lang) == 'en' ? $en : $vi;
        $data = [$months[0]];
        if (!$increment) {
            for ($i = 12; $i > 0; $i--) {
                $data[$i < 10 ? '0' . $i : $i . ''] = $months[$i];
            }
        } else {
            for ($i = 1; $i < 13; $i++) {
                $data[$i < 10 ? '0' . $i : $i . ''] = $months[$i];
            }
        }
        return $data;
    }
}

if (!function_exists('get_day_options')) {
    /**
     * lấy ngày cho option của select
     * @param string $lang
     * @param boolean $increment
     * 
     * @return array
     */
    function get_day_options($lang = 'vi', $increment = true)
    {
        $label = strtolower($lang) == 'en' ? 'Day' : 'Ngày';
        $data = [$label];
        if ($increment) {
            for ($i = 1; $i < 32; $i++) {
                $data[$i < 10 ? '0' . $i : $i] = $i;
            }
        } else {
            for ($i = 31; $i > 0; $i--) {
                $data[$i < 10 ? '0' . $i : $i] = $i;
            }
        }
        return $data;
    }
}



if (!function_exists('get_number_options')) {
    /**
     * option cho day so
     * @param int $start so bat dau
     * @param int $end so ket thuc
     * @param string $first_text gia tri dau tien
     * @param string|int|float|double $first_value
     * @return array
     */
    function get_number_options($start = 0, $end = 10, $first_text = null, $first_value = null)
    {
        $data = [];
        if ($first_value || $first_text) {
            if ($first_value) {
                if ($first_text) {
                    $data[$first_value] = $first_text;
                } else {
                    $first_value = $first_value;
                }
            } else {
                $data[""] = $first_text;
            }
        }
        if ($start < $end) {
            $a = $start;
            $b = $end;
        } else {
            $a = $end;
            $b = $start;
        }
        for ($i = $a; $i <= $b; $i++) {
            $data[$i] = $i;
        }
        return $data;
    }
}





if (!function_exists('get_mime_type')) {
    function get_mime_type($type)
    {
        $mimes = get_mime_support();
        $s = strtolower($type);
        if (isset($mimes[$s])) {
            return new CrazyArr([
                'ext' => $s,
                'type' => $mimes[$s]
            ]);
        } else {
            foreach ($mimes as $ext => $mime) {
                if ($s == $mime) {
                    return new CrazyArr([
                        'ext' => $ext,
                        'type' => $mime
                    ]);
                }
            }
        }
        return null;
    }
}

if (!function_exists('get_editor_support_types')) {
    /**
     * kiểm tra tên file hợp lệ hay không
     * @param string $str
     * @return array
     */
    function get_editor_support_types()
    {
        return ['html', 'js', 'css', 'php', 'txt', 'json', 'jsx', 'ts', 'asp', 'aspx', 'xhtml', 'htm', 'less', 'sass', 'scss', 'py', 'java', 'xml', 'gltf', 'htaccess', 'env', 'example'];
    }
}

if (!function_exists('is_filename')) {
    /**
     * kiểm tra tên file hợp lệ hay không
     * @param string $str
     * @return bool
     */
    function is_filename($str = null)
    {
        return str_replace(['%', '?', '!', '\\', '/', '#', ':', ';', "'", '|', '+', '=', '"', "^", '<', '>', ',', '*', "\r", "\n", "\t"], '', $str) == $str;
    }
}

if (!function_exists('get_base64_data')) {
    /**
     * lấy thông tin từ request, kiểm tra xem đúng hay ko
     * dúng trả về mảng
     * sai trả về null
     * @param string $str
     * @return CrazyArr|null
     */
    function get_base64_data($str)
    {
        $filename = null;
        if (count($fileinfo = explode('@', $str)) == 2) {
            $filename = $fileinfo[0];
            $str = $fileinfo[1];
        }
        if (preg_match_all('/^data\:([^;]*);base64,(.*)$/si', $str, $m)) {
            $type = $m[1][0];
            if ($info = get_mime_type($type)) {
                $data = $m[2][0];
                $ext  = $info->ext;
                $ctype = explode('/', $type);
                $filetype = $ctype[0];
                $mime = $ctype[1];
                return new CrazyArr(compact('type', 'data', 'ext', 'filetype', 'mime', 'filename'));
            }
        }
        return null;
    }
}


if (!function_exists('get_mime_support')) {
    /**
     * lấy danh sach kiểu file được hổ trợ
     */
    function get_mime_support()
    {
        return [
            '3gp' => 'video/3gpp',
            '7z' => 'application/x-7z-compressed',
            'aac' => 'audio/x-aac',
            'ai' => 'application/postscript',
            'avi' => 'video/x-msvideo',
            'bmp' => 'image/bmp',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docm' => 'application/vnd.ms-word.document.macroenabled.12',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dot' => 'application/msword',
            'dotm' => 'application/vnd.ms-word.template.macroenabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'exe' => 'application/x-msdownload',
            'flv' => 'video/x-flv',
            'gif' => 'image/gif',
            'h261' => 'video/h261',
            'h263' => 'video/h263',
            'h264' => 'video/h264',
            'html' => 'text/html',
            'htm' => 'text/html',
            'ico' => 'image/x-icon',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpgv' => 'video/jpeg',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'jsonml' => 'application/jsonml+json',
            'm4a' => 'audio/mp4',
            'mb' => 'application/mathematica',
            'mdb' => 'application/x-msaccess',
            'mka' => 'audio/x-matroska',
            'mkv' => 'video/x-matroska',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'psd' => 'image/vnd.adobe.photoshop',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'rq' => 'application/sparql-query',
            'rs' => 'application/rls-services+xml',
            'rsd' => 'application/rsd+xml',
            'rss' => 'application/rss+xml',
            'rtf' => 'application/rtf',
            'rtx' => 'text/richtext',
            's' => 'text/x-asm',
            'ser' => 'application/java-serialized-object',
            'sid' => 'image/x-mrsid-image',
            'sql' => 'application/x-sql',
            'src' => 'application/x-wais-source',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'txt' => 'text/plain',
            'text' => 'text/plain',
            'tpl' => 'application/vnd.groove-tool-template',
            'tpt' => 'application/vnd.trid.tpt',
            'tr' => 'text/troff',
            'ttf' => 'application/x-font-ttf',
            'wav' => 'audio/x-wav',
            'wbs' => 'application/vnd.criticaltools.wbs+xml',
            'weba' => 'audio/webm',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'application/font-woff',
            'wri' => 'application/x-mswrite',
            'wrl' => 'model/vrml',
            'xhtml' => 'application/xhtml+xml',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlt' => 'application/vnd.ms-excel',
            'xltm' => 'application/vnd.ms-excel.template.macroenabled.12',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlw' => 'application/vnd.ms-excel',
            'xm' => 'audio/xm',
            'xml' => 'application/xml',
            'xsl' => 'application/xml',
            'xsm' => 'application/vnd.syncml+xml',
            'xyz' => 'chemical/x-xyz',
            'xz' => 'application/x-xz',
            'zip' => 'application/zip'
        ];
    }
}

if (!function_exists('get_directory_size')) {
    function get_directory_size($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : get_directory_size($each);
        }

        return $size;
    }
}
if (!function_exists('get_folder_size')) {
    function get_folder_size($folder, $unit = 'b')
    {
        $size = get_directory_size($folder);
        $u = strtolower($unit);
        if (in_array($u, ['t', 'tb'])) return $size / (1024 * 1024 * 1024);
        if (in_array($u, ['g', 'gb'])) return $size / (1024 * 1024 * 1024);
        if (in_array($u, ['m', 'mb'])) return $size / (1024 * 1024);
        if (in_array($u, ['k', 'kb'])) return $size / 1024;
        return $size;
    }
}


/*
  make:channel         Create a new channel class
  make:command         Create a new Artisan command
  make:controller      Create a new controller class
  make:event           Create a new event class
  make:exception       Create a new custom exception class
  make:factory         Create a new model factory
  make:job             Create a new job class
  make:listener        Create a new event listener class
  make:mail            Create a new email class
  make:middleware      Create a new middleware class
  make:migration       Create a new migration file
  make:model           Create a new Eloquent model class
  make:notification    Create a new notification class
  make:observer        Create a new observer class
  make:policy          Create a new policy class
  make:provider        Create a new service provider class
  make:request         Create a new form request class
  make:resource        Create a new resource
  make:rule            Create a new validation rule
  make:seeder          Create a new seeder class
  make:test            Create a new test class
 */
if (!function_exists('get_hosting_commands')) {
    function get_hosting_commands()
    {
        return [
            1 => ['php artisan make:model', 1],
            2 => ['php artisan make:controller', 1],
            3 => ['php artisan make:migration', 1],
            4 => ['php artisan make:command', 1],
            5 => ['php artisan make:middleware', 1],
            6 => ['php artisan make:seeder', 1],
            7 => ['php artisan make:request', 1],
            8 => ['php artisan make:resource', 1],
            9 => ['php artisan make:provider', 1],
            10 => ['php artisan make:job', 1],
            11 => ['php artisan make:event', 1],
            12 => ['php artisan make:listener', 1],
            13 => ['php artisan make:factory', 1],
            14 => ['php artisan make:notification', 1],
            15 => ['php artisan make:observer', 1],
            16 => ['php artisan make:policy', 1],
            17 => ['php artisan make:rule', 1],
            18 => ['php artisan key:generate', 0],

            19 => ['php artisan migrate', 0],
            20 => ['php artisan migrate:rollback', 0],
            21 => ['php artisan migrate:refresh', 0],
            22 => ['php artisan migrate:rollback', 1],
            23 => ['php artisan migrate:reset', 0],
            24 => ['php artisan migrate:status', 1],
            25 => ['php artisan db:seed', 1],
            26 => ['php artisan db:wipe', 1],
            27 => ['composer update', 0],
            28 => ['composer install', 0],
            29 => ['composer dump-autoload', 0],
            30 => ['composer require', 1],

        ];
    }
}


if (!function_exists('parse_query_data')) {

    /**
     * chuẩn hóa query string
     * @param string $query query or url
     * @param array $data mang cac tham so
     * @param string|array $ignore ten tham so dc bo qua
     * @return array
     */
    function parse_query_data($query = null)
    {
        $arr = [];
        if ($query && is_string($query)) {
            try {
                $a = explode('?', $query);
                if (count($a) == 2) $query = $a[1];
                parse_str($query, $d);
                if ($d) {
                    $arr = $d;
                }
            } catch (Exception $e) {
                // eo can lam gi cung dc
            }
        }
        return $arr;
    }
}
if (!function_exists('parse_query_string')) {

    /**
     * chuẩn hóa query string
     * @param string $query co san
     * @param array $data mang cac tham so
     * @param string|array $ignore ten tham so dc bo qua
     * @return string
     */
    function parse_query_string($query = null, array $data = [], $ignore = null)
    {
        $arr = [];
        if ($query && is_string($query)) {
            try {
                parse_str($query, $d);
                if ($d) {
                    $arr = $d;
                }
            } catch (Exception $e) {
                // eo can lam gi cung dc
            }
        }

        if (is_array($data)) {
            foreach ($data as $name => $value) {
                if (!is_null($value) && (is_numeric($value) || is_string($value)) && strlen($value) > 0) {
                    $arr[$name] = $value;
                }
            }
        }
        $s = '';
        if ($arr) {
            // nếu là array
            if (is_array($ignore)) {
                foreach ($arr as $n => $v) {
                    if (!in_array($n, $ignore)) {
                        $s .= "$n=" . urlencode($v) . "&";
                    }
                }
                $s = trim($s, '&');
                return $s;
            }
            // neu la string
            if ($ignore && isset($arr[$ignore])) unset($arr[$ignore]);
            foreach ($arr as $n => $v) {
                $s .= "$n=" . urlencode($v) . "&";
            }
            $s = trim($s, '&');
        }
        return $s;
    }
}

if (!function_exists('url_merge')) {
    /**
     *
     * add quey string to url
     * @param string $url dung dan lien ket
     * @param array $name mang query hoac ten bien neu la bien don
     * @param string $val gia tri bien
     * @return string $url
     */

    function url_merge($url, $name = null, $val = null, $ignore = null)
    {
        $u = $url;
        $r = [];
        $f = explode('?', $url);
        $q = '';
        $u = $f[0];
        if (count($f) > 1) {
            $q = $f[1];
        }
        if ($name) {
            if (is_string($name)) $r[$name] = $val;
            elseif (is_array($name)) {
                foreach ($name as $n => $v) {
                    if (is_string($n)) {
                        $r[$n] = $v;
                    }
                }
            }
        }
        if ($q || $r) {
            $u .= '?' . parse_query_string($q, $r, $ignore);
        }
        return $u;
    }
}


if (!function_exists('url_relative')) {
    /**
     * xóa link asset và thay bằng /
     * @param string $url đường dẫn tuyệt đối
     *
     * @return string trả về url
     */
    function url_relative(string $url): string
    {
        // tìm các chứa địa chỉ trang chủ thay bằng /
        $search = rtrim(asset('/'), '/') . '/';
        $replace = '/';
        $newUrl = str_replace($search, $replace, $url);
        return $newUrl;
    }
}


if (!function_exists('json_path')) {
    /**
     * lấy dường dẫn từ thư mục json
     * @param string $path
     * @return string
     */
    function json_path($path = null)
    {
        return base_path('json') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('is_gd_image')) {
    function is_gd_image($var): bool
    {
        return (gettype($var) == "object" && get_class($var) == "GdImage");
    }
}
