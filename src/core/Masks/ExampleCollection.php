<?php
namespace Saola\Core\Masks;

class ExampleCollection extends MaskCollection
{
    /**
     * lấy tên class mask tương ứng
     *
     * @return string
     */
    public function getMask()
    {
        return ExampleMask::class;
    }
    // xem Collection mẫu ExampleCollection
}
