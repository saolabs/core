<?php

namespace Saola\Core\Validators;

use Saola\Core\Magic\Arr;
use Saola\Core\Files\Filemanager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait DefaultMethods
{

    protected $defaultMessages = [
        'files.array'                          => 'Tài liệu không hợp lệ',
        'files.*.filename.max'                 => 'Tên file không hợp lệ',
        'files.*.filedata.base64_file'         => 'Tập tin không hợp lệ',
        'files.*.description.max'              => 'Mô tả hơi... dài!',
        'attachments.array'                    => 'Tập tin đính kèm không hợp lệ',
        'attachments.*.filename.max'           => 'Tên file không hợp lệ',
        'attachments.*.filedata.base64_file'   => 'Tập tin không hợp lệ',
        'attachments.*.description.max'        => 'Mô tả hơi... dài!',
        '*.email_or_null'                      => 'Email không hợp lệ',
        '*.email'                              => 'Email không hợp lệ',
        '*.phone_number'                       => 'Số điện thoại không hơp lệ',
        'region_id.required'           => 'Vui lòng chọn tỉnh / thành phố',
        'region_id.check_region'       => 'Tỉnh / thành phố không hợp lệ',
        'district_id.required'         => 'Vui lòng chọn quận / huyện',
        'district_id.check_district'   => 'Quận / huyện không hợp lệ',
        'ward_id.required'             => 'Vui lòng chọn phường / xã',
        'ward_id.check_ward'           => 'phường / xã phố không hợp lệ',
    ];

    /**
     * @var \illuminate\Http\Request
     */
    protected $request = null;

    protected $__status = false;

    protected $__errors = [];

    protected $validateErrors = null;

    protected static $isSetDefault = false;

    protected static $registerRules = [];

    /**
     * repository
     *
     * @var \Saola\Core\Repositories\Base\BaseRepository
     */
    protected $repository = null;

    protected $datetimes = [];

    protected $arrdate = [];

    public function init($request = null, $repository = null)
    {
        $this->request = $request;
        if ($repository)
            $this->repository = $repository;
    }

    protected function addDefaultRules()
    {
        if (self::$isSetDefault) return;

        // kiểm tra file upload bang base64
        static::extend('base64_file', function ($attr, $value, $parameter) {
            if (!$value) return true;
            return $this->checkBase64File($value, $parameter);
        });

        static::extend('base64_list', function ($attr, $value, $parameter) {
            if (!$value) return true;
            if (!is_array($value)) return false;
            foreach ($value as $data) {
                if (!$this->checkBase64File($data, $parameter)) return false;
            }
            return true;
        });

        // kiem tra tinh duy nhat cua thuoc tinh
        static::extend('unique_prop', function ($name, $value, $parameters) {
            return $this->checkUniqueProp($name, $value, $parameters);
        });
        // kiem tra tinh duy nhat cua thuoc tinh
        static::extend('unique_value', function ($name, $value, $parameters) {
            if (is_null($value) || !strlen($value)) return true;
            return $this->checkUniqueProp($name, $value, $parameters);
        });
        // kiem tra tinh duy nhat cua thuoc tinh
        static::extend('is_slug', function ($name, $value) {
            return preg_match('/^[A-z_]+[A-z0-9_\-]*$/i', $value);
        });
        static::extend('check_boolean', function () {
            return true;
        });
        static::extend('binary', function () {
            return true;
        });


        static::extend('mixed', function () {
            return true;
        });

        static::extend('optional', function () {
            return true;
        });

        static::extend('fail', function () {
            return false;
        });
        static::extend('has_value', function () {
            return true;
        });


        static::extend('any_number', function ($name, $value) {
            if (is_null($value)) return true;
            return is_numeric($value);
        });


        static::extend('check_number', function ($name, $value, $parameters) {
            if (is_null($value)) return true;
            if (!is_numeric($value)) return false;
            if ($parameters && $params = $this->parseParameters($parameters)) {
                return in_array($value, $params);
            }
            return true;
        });

        static::extend('check_number', function ($name, $value, $parameters) {
            if (is_null($value)) return true;
            if (!is_numeric($value)) return false;
            if ($parameters && $params = $this->parseParameters($parameters)) {
                return in_array($value, $params);
            }
            return true;
        });


        static::extend('in_list', function ($name, $value, $parameters) {
            if (is_null($value)) return true;
            if ($parameters && $params = $this->parseParameters($parameters)) {
                return in_array($value, $params);
            }
            return true;
        });

        static::extend('value_by', function ($name, $value) {
            return true;
        });

        static::extend('strdate', function ($name, $value) {
            if (is_null($value)) return true;
            return strtodate($value) ? true : false;
        });

        static::extend('arrdate', function ($name, $value, $params) {
            if (!$value) return true;
            if (is_array($value)) {
                $d = new Arr($value);
                if ((!$d->day || $d->day == 'day' || $d->day == '0') && (!$d->month || $d->month == 'month' || $d->month == '0') && (!$d->year || $d->year == 'year' || $d->year == '0')) {

                    return true;
                }
            }
            $status = $this->checkArrDate($name, $value);
            if ($status && $value && $params && $p = $this->parseParameters($params)) {
                if (in_array(strtolower($p[0]), ['string', 'str'])) {
                    $this->arrdate[$name] = $value['year'] . '-' . $value['month'] . '-' .  $value['day'];
                } else {
                    $this->arrdate[$name] = $value;
                }
            } else {
                $this->arrdate[$name] = $value;
            }
            return $status;
        });

        static::extend('arrdate_after', function ($name, $value, $params) {
            if (!$value) return false;
            $status = $this->checkArrDate($name, $value);
            if ($status && $value && $params && $p = $this->parseParameters($params)) {
                if ($this->{$p[0]}) {
                    if ($this->checkArrDate($p[0], $this->{$p[0]})) {
                        //
                        $t1 = $this->{$p[0]};
                        $t2 = $value;
                        $timeBefore = strtotime("$t1[year]-$t1[month]-$t1[day] 00:00:00");
                        $timeAfter = strtotime("$t2[year]-$t2[month]-$t2[day] 00:00:00");
                        if ($timeAfter >= $timeBefore) {
                            return true;
                        }
                    }
                }
            }
            return false;
        });

        static::extend('strtime', function ($name, $value) {
            if (is_null($value)) return true;
            return check_time_format($value);
        });
        static::extend('refid', function ($name, $value, $parameters) {
            $p = $this->parseParameters($parameters);
            $key = isset($p[0]) ? $p[0] : 'ref';
            return $this->checkRef($this->{$key}, $value);
        });

        static::extend('strdatetime', function ($name, $value, $params) {
            if (is_null($value)) return true;
            if ($datetime = parse_date_time($value)) {
                if (!is_date($datetime['day'], $datetime['month'], $datetime['year'])) return false;
                if ($params && $p = $this->parseParameters($params)) {
                    if (in_array(strtolower($p[0]), ['string', 'str'])) {
                        $this->datetimes[$name] = "$datetime[year]-$datetime[month]-$datetime[day] $datetime[hour]:$datetime[minute]:$datetime[second]";
                    } else {
                        $this->datetimes[$name] = $datetime;
                    }
                } else {
                    $this->datetimes[$name] = $datetime;
                }
                return true;
            }
            return false;
        });

        static::extend('datetimerange', function ($name, $value) {
            if (is_null($value)) return true;

            if (count($date = array_map('trim', explode(' - ', $value))) == 2) {
                foreach ($date as $datetime) {
                    if ($datet = parse_date_time($datetime)) {
                        if (!is_date($datet['day'], $datet['month'], $datet['year'])) return false;
                    }
                }
                $this->datetimes[$name] = [
                    'from' => $date[0],
                    'to' => $date[1]
                ];
                return true;
            }


            return false;
        });

        static::extend('datetime', function ($name, $value, $parameters) {
            $p = $this->parseParameters($parameters);
            $format = isset($p[0]) ? $p[0] : 'datetime';
            if (!$value) {
                if (isset($p[1]) && strtolower($p[1]) == 'required') {
                    return false;
                }
                return true;
            }
            return carbon_datetime($value, $format)?true:false;
        });

        static::extend('email_or_null', function ($name, $value) {
            if (is_null($value)) return true;
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        });

        static::extend('phone_number', function ($attr, $value) {
            if (!$value) return true;
            return preg_match('/^[0-9\+\-\.]{7,14}$/si', $value);
        });


        // required nếu tạo mới
        static::extend('create_required', function ($name, $value) {
            if (!$this->id && (is_null($value) || strlen($value) == 0)) return false;
            return true;
        });

        // required nếu tạo mới
        static::extend('prop_input', function ($name, $value) {
            if (is_null($value)) return true;
            if (!is_array($value)) return false;
            $selectData = [
                "text", "number", "email", "textarea", "select", "radio", "checkbox", "checklist", "switch", "crazyselect", "crazytag", "options", "touchspin", "file", "media", 'coloris', 'ckeditor'
            ];


            if (count($value)) {
                if (
                    !isset($value['name'])
                    || !strlen($value['name'])
                    || !preg_match('/^[@A-z0-9_\.\-]+[A-z0-9_\.\-]*$/', $value['name'])
                    || !isset($value['type'])
                    || !$value['type']
                ) return false;
            }
            return true;
        });

        static::extend('privacy', function ($prop, $value) {
            if (is_null($value)) return true;
            return in_array(strtolower($value), ['private', 'public', 'publish', 'published']);
        });

        static::extend('name_slug', function ($attr, $value) {
            return preg_match('/^[A-z]+[A-z0-9_\.]*$/si', $value);
        });


        $this->addRule('base64_size', function ($prop, $value, $parameters) {
            $p = $this->parseParameters($parameters);
            if (!$p || !is_numeric($p[0]) || count($it = explode(',', $value)) < 2) return true;
            $size = (int) (strlen(rtrim($it[1], '=')) * 3 / 4);
            $size = $size / 1024;
            return $size <= to_number($p[0]);
        });

        $this->addRule('filetype', function ($attr, $value, $parameters) {
            if (!$value) return true;
            if ($parameters && $params = $this->parseParameters($parameters)) {
                $type = $value->getClientOriginalExtension();
                $filemanager = new Filemanager();
                if ($fileType = $filemanager->getMimeType($type)) {
                    return in_array($fileType->extension, $params);
                }
            }
            return false;
        });
        self::$isSetDefault = true;
    }

    /**
     * thêm ràng buộc dữ liệu
     * @param string $rule tên rule
     * @param callable $callable
     * @return bool
     */
    public function addRule($rule, $callable): bool
    {

        if (!is_string($rule) || in_array($rule, self::$registerRules) || !is_callable($callable)) return false;
        static::extend($rule, $callable);
        self::$registerRules[] = $rule;
        return true;
    }

    /**
     * kiểm tra tồn tại duy nhất
     * @param string $name
     * @param string $value
     * @param array $parameter
     * @return boolean
     */
    public function checkUniqueProp($name, $value, $parameters = [])
    {
        $data = [$name => $value];
        if (is_array($parameters) && count($parameters)) {
            foreach ($parameters as $attr) {
                if ($a = trim($attr)) {
                    $data[$attr] = $this->{$attr};
                }
            }
        }

        if ($result = $this->repository->first($data)) {
            if ($this->id && $this->id == $result->{$this->repository->getKeyName()}) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * kiểm tra tham chiếu
     * @param string $ref
     * @param int $ref_id
     * 
     * @return boolean
     */
    public function checkRef($ref, $ref_id = 0)
    {
        if (in_array($ref, ['data'])) {
            return true;
        }
        if ($tb = $this->getRefSupport($ref)) {
            if (!DB::table($tb)->where(MODEL_PRIMARY_KEY, $ref_id)->first()) return false;
            return true;
        }
        return false;
    }


    /**
     * lấy các bảng được hỗ trợ
     * @param string
     */
    public function getRefSupport($ref)
    {
        $tb = strtolower($ref);
        if (in_array($tb, ['page', 'project'])) $tb = 'post';
        $tbl = null;
        if (in_array($tb, ['data'])) {
            return $tb;
        } elseif (Schema::hasTable($tb)) {
            $tbl = $tb;
        } elseif (Schema::hasTable($tb = str_plural($tb))) {
            $tbl = $tb;
        } else {
            return false;
        }
        return $tbl;
    }


    /**
     * kiểm tra base64
     */
    public function checkBase64File($value, $parameter = [])
    {
        if (!$value) return true;
        $exts = [];
        if (is_array($parameter)) {
            foreach ($parameter as $ext) {
                $exts[] = trim(strtolower($ext));
            }
        }
        if ($file = get_base64_data($value)) {
            if ($exts) {
                if (in_array($file->ext, $exts) || in_array($file->filetype, $exts) || in_array($file->mime, $exts) || in_array($file->type, $exts)) {
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    public function checkArrDate($name, $value)
    {
        if (!$value) return true;
        if (is_array($value) && array_check_keys($value, ['day', 'month', 'year'])) {
            if (is_date($value['day'], $value['month'], $value['year'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * chuẩn hóa tham số
     *
     * @param array $parameters
     * @return array
     */
    public function parseParameters(array $parameters): array
    {
        return array_map('trim', $parameters);
    }


    /**
     * parse rule
     * chuẩn hóa các rule theo id
     */
    protected final function parseRules(array $rules, array $required = [])
    {
        $id = $this->id;
        $data = [];
        foreach ($rules as $attr => $rule) {
            if (in_array($attr, $required) || !$id || !is_null($this->{$attr})) {
                $data[$attr] = $rule;
            }
        }
        return $data;
    }

    /**
     * them rule s4 tự động được gọi khi khởi tạo
     * @return void
     * 
     */
    protected function extends()
    {
    }
}
