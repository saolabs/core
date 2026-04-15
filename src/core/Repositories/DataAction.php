<?php

namespace Saola\Core\Repositories;

/**
 * các phương thúc với owner
 */
trait DataAction
{
    /**
     * lấy slug
     * @param $string
     */


    

    /**
     * thay the noi dung mot
     * @param string $columns
     * @param string $find
     * @param string $replace
     * @return void
     */
    
    public function replace($columns, $find = null, $replace='')
    {
        if($find && $list = $this->get()){
            if(is_array($columns)){
                $cols = $columns;
            }elseif(count($c = explode(',',str_replace(' ','', $columns)))){
                $cols = $c;
            }else{
                return false;
            }
            $i = 0;
            foreach ($list as $item) {
                foreach($cols as $col){
                    $col = trim($col);
                    if($col!=$this->_primaryKeyName && isset($item->{$col})){
                        $item->{$col} = str_replace($find, $replace, $item->{$col});
                        
                    }
                }
                $i++;
                $item->save();
            }
            return $i;
        }
        return false;
    }


    public function getSlug($str=null, $id=null, $col = null, $value=null)
    {
        if(!$str && $id==null) return null;
        if(!$str) return null;
        $aslug = str_slug($str,'-');
        $slug = null;
        $i = 1;
        $c = '';
        $s = true;
        $args = [];
        if($col){
            $args[$col] = $value;
        }
        do{
            $sl = $aslug.$c;
            $args['slug'] = $sl;

            if($pf = $this->first($args)){
                if($id && $pf->{$pf->getKeyName()} == $id){
                    $slug = $sl;
                    $s = false;
                    break;
                }
                $c='-'.$i;
            }else{
                $slug = $sl;
                $s = false;
                break;
            }

            $i++;
        }while($s);
        return $slug;
    }

    public function checkSlug($str=null, $id=null, $col = null, $value=null)
    {
        if(!strlen($str)) return -1;
        if(!preg_match('/^[A-z0-9\-\_]+[A-z0-9\-\_]*$/i', $str)) return -2;
        $args = [];
        if($col){
            $args[$col] = $value;
        }
        $args['slug'] = $str;

        if($result = $this->first($args)){
            if($id && $result->{$result->getKeyName()} == $id){
                return 1;
            }
            return 0;
        }
        return 1;
    }

}
