<?php

namespace Saola\Core\Repositories;

/**
 * các phương thúc với owner
 */
trait FileAction
{
    /**
     * ham xóa file cũ
     * @param int $id
     * 
     * @return boolean
     */
    public function deleteAttachFile($id)
    {
        if($result = $this->find($id)){
            $this->fire('beforedeleteAttachFile', $this,$id, $result);
            $rs = $result->deleteAttachFile();
            $this->fire('afterdeleteAttachFile', $this,$id, $result);
            return $rs;
        }
        return false;
    }

    /**
     * lấy tên file đính kèm cũ
     */
    public function getAttachFilename($id)
    {
        if($result = $this->find($id)){
            return $result->getAttachFilename();
        }
        return null;
    }

}
