<?php

namespace Saola\Core\Support;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ValidationRules
{
    /**
     * Phone number validation rule
     */
    public static function phone(): string
    {
        return 'regex:/^(\+84|84|0)[0-9]{9}$/';
    }

    /**
     * Vietnamese phone number validation rule
     */
    public static function vietnamesePhone(): string
    {
        return 'regex:/^(0|\+84)(3[2-9]|5[689]|7[06-9]|8[1-689]|9[0-46-9])[0-9]{7}$/';
    }

    /**
     * Vietnamese ID card validation rule
     */
    public static function vietnameseIdCard(): string
    {
        return 'regex:/^[0-9]{9,12}$/';
    }

    /**
     * Vietnamese tax code validation rule
     */
    public static function vietnameseTaxCode(): string
    {
        return 'regex:/^[0-9]{10,13}$/';
    }

    /**
     * Vietnamese bank account validation rule
     */
    public static function vietnameseBankAccount(): string
    {
        return 'regex:/^[0-9]{9,19}$/';
    }

    /**
     * Vietnamese postal code validation rule
     */
    public static function vietnamesePostalCode(): string
    {
        return 'regex:/^[0-9]{5,6}$/';
    }

    /**
     * Strong password validation rule
     */
    public static function strongPassword(): string
    {
        return 'min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/';
    }

    /**
     * Username validation rule
     */
    public static function username(): string
    {
        return 'regex:/^[a-zA-Z0-9_]{3,20}$/';
    }

    /**
     * Slug validation rule
     */
    public static function slug(): string
    {
        return 'regex:/^[a-z0-9-]+$/';
    }

    /**
     * Color hex validation rule
     */
    public static function colorHex(): string
    {
        return 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';
    }

    /**
     * URL validation rule with custom schemes
     */
    public static function urlWithSchemes(array $schemes = ['http', 'https']): string
    {
        return 'url|regex:/^(' . implode('|', $schemes) . '):\/\/.+/';
    }

    /**
     * File size validation rule
     */
    public static function fileSize(int $maxSizeInMB): string
    {
        return 'max:' . ($maxSizeInMB * 1024);
    }

    /**
     * Image dimensions validation rule
     */
    public static function imageDimensions(int $minWidth, int $minHeight, ?int $maxWidth = null, ?int $maxHeight = null): string
    {
        $rule = "dimensions:min_width={$minWidth},min_height={$minHeight}";
        
        if ($maxWidth) {
            $rule .= ",max_width={$maxWidth}";
        }
        
        if ($maxHeight) {
            $rule .= ",max_height={$maxHeight}";
        }
        
        return $rule;
    }

    /**
     * Date range validation rule
     */
    public static function dateRange(string $startDate, string $endDate): string
    {
        return "after_or_equal:{$startDate}|before_or_equal:{$endDate}";
    }

    /**
     * Time range validation rule
     */
    public static function timeRange(string $startTime, string $endTime): string
    {
        return "date_format:H:i|after_or_equal:{$startTime}|before_or_equal:{$endTime}";
    }

    /**
     * Unique with conditions validation rule
     */
    public static function uniqueWithConditions(string $table, string $column = 'NULL', string $ignore = 'NULL', string $ignoreColumn = 'id', array $where = []): string
    {
        $rule = Rule::unique($table, $column);
        
        if ($ignore !== 'NULL') {
            $rule->ignore($ignore, $ignoreColumn);
        }
        
        if (!empty($where)) {
            foreach ($where as $column => $value) {
                $rule->where($column, $value);
            }
        }
        
        return $rule;
    }

    /**
     * Exists with conditions validation rule
     */
    public static function existsWithConditions(string $table, string $column = 'id', array $where = []): string
    {
        $rule = Rule::exists($table, $column);
        
        if (!empty($where)) {
            foreach ($where as $col => $val) {
                $rule->where($col, $val);
            }
        }
        
        return $rule;
    }

    /**
     * Required if other field has value
     */
    public static function requiredIfField(string $field, $value): string
    {
        return "required_if:{$field},{$value}";
    }

    /**
     * Required unless other field has value
     */
    public static function requiredUnlessField(string $field, $value): string
    {
        return "required_unless:{$field},{$value}";
    }

    /**
     * Required with other fields
     */
    public static function requiredWithFields(array $fields): string
    {
        return 'required_with:' . implode(',', $fields);
    }

    /**
     * Required without other fields
     */
    public static function requiredWithoutFields(array $fields): string
    {
        return 'required_without:' . implode(',', $fields);
    }

    /**
     * Prohibited if other field has value
     */
    public static function prohibitedIfField(string $field, $value): string
    {
        return "prohibited_if:{$field},{$value}";
    }

    /**
     * Prohibited unless other field has value
     */
    public static function prohibitedUnlessField(string $field, $value): string
    {
        return "prohibited_unless:{$field},{$value}";
    }

    /**
     * Custom validation for Vietnamese name
     */
    public static function vietnameseName(): string
    {
        return 'regex:/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵýỷỹ\s]+$/';
    }

    /**
     * Custom validation for Vietnamese address
     */
    public static function vietnameseAddress(): string
    {
        return 'regex:/^[a-zA-Z0-9ÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵýỷỹ\s\.,\/\-\(\)]+$/';
    }

    /**
     * Custom validation for credit card number
     */
    public static function creditCardNumber(): string
    {
        return 'regex:/^[0-9]{13,19}$/';
    }

    /**
     * Custom validation for credit card expiry
     */
    public static function creditCardExpiry(): string
    {
        return 'regex:/^(0[1-9]|1[0-2])\/([0-9]{2})$/';
    }

    /**
     * Custom validation for credit card CVV
     */
    public static function creditCardCvv(): string
    {
        return 'regex:/^[0-9]{3,4}$/';
    }

    /**
     * Validate Vietnamese phone number
     */
    public static function validateVietnamesePhone($attribute, $value, $parameters, $validator): bool
    {
        return preg_match('/^(0|\+84)(3[2-9]|5[689]|7[06-9]|8[1-689]|9[0-46-9])[0-9]{7}$/', $value);
    }

    /**
     * Validate Vietnamese ID card
     */
    public static function validateVietnameseIdCard($attribute, $value, $parameters, $validator): bool
    {
        return preg_match('/^[0-9]{9,12}$/', $value);
    }

    /**
     * Validate strong password
     */
    public static function validateStrongPassword($attribute, $value, $parameters, $validator): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value);
    }

    /**
     * Register custom validation rules
     */
    public static function register(): void
    {
        Validator::extend('vietnamese_phone', [self::class, 'validateVietnamesePhone']);
        Validator::extend('vietnamese_id_card', [self::class, 'validateVietnameseIdCard']);
        Validator::extend('strong_password', [self::class, 'validateStrongPassword']);
    }
}
