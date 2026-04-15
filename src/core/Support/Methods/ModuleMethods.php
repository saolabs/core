<?php

namespace Saola\Core\Support\Methods;

use Saola\Core\Magic\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Saola\Core\Html\Menu;

use Saola\Core\Laravel\Router;
use Saola\Core\Masks\EmptyCollection;
use Saola\Core\Masks\EmptyMask;
use Saola\Core\Repositories\BaseRepository;

/**
 * các thuộc tính và phương thức của form sẽ được triển trong ManagerController
 * @property \Saola\Core\Repositories\BaseRepository $repository
 */
trait ModuleMethods
{


    /**
     * @var string $repositoryClass
     * full class name 
     */
    protected $repositoryClass = '';


    /**
     * @var \Saola\Core\Repositories\BaseRepository $repository
     */
    protected $repository = null;
    /**
     * thiết lập module
     */
    public function initModule()
    {
        if($this->repositoryClass){
            $this->setRepositoryClass($this->repositoryClass);
        }
        
        
        if ($this->repository)
            $this->repository->notTrashed();
        
    }


    public function setRepositoryClass($repositoryClass)
    {
        if(is_string($repositoryClass) && class_exists($repositoryClass)){
            $this->repositoryClass = $repositoryClass;
        }

        return $this;
    }


    public function setRepository($repository){
        if(is_object($repository) && ($repository instanceof BaseRepository || is_a($repository, BaseRepository::class))){
            $this->repository = $repository;
        }
        elseif(is_string($repository) && class_exists($repository)){
            $this->repository = app($repository);
        }
        return $this;
    }

    public function getRepository(){
        return $this->repository??$this->repositoryClass?app($this->repositoryClass):null;
    }

    /**
     * Thực hiện một hành động với repository một cách an toàn
     * 
     * Hàm này cho phép thực hiện các thao tác với repository mà không làm gián đoạn
     * luồng xử lý nếu có lỗi xảy ra. Nếu có lỗi hoặc repository không tồn tại,
     * sẽ trả về giá trị mặc định.
     *
     * @param callable(\Saola\Core\Repositories\BaseRepository):mixed $callback Callback thực hiện với repository
     * @param mixed $default Giá trị mặc định trả về khi có lỗi. Có thể là:
     *                      - Class string: sẽ tự động resolve từ container
     *                      - Object: trả về object đó
     *                      - Giá trị khác: trả về giá trị đó
     * @param bool $logError Có log lỗi ra không (mặc định: true trong debug mode)
     * @return mixed Kết quả từ callback hoặc giá trị mặc định
     */
    public function repositoryTap(callable $callback, mixed $default = null, bool $logError = false): mixed
    {
        
        // Kiểm tra repository có tồn tại không
        if (!is_object($this->repository)) {
            return $this->resolveDefaultValue($default);
        }
        try {
            return $callback($this->repository);
        } catch (\Throwable $e) {
            if ($logError) {
                Log::warning('RepositoryTap error', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'repository' => get_class($this->repository),
                ]);
            }
            
            return $this->resolveDefaultValue($default);
        }
    }
    
    /**
     * Resolve giá trị mặc định từ tham số
     * 
     * @param mixed $default Giá trị mặc định
     * @return mixed Giá trị đã được resolve
     */
    protected function resolveDefaultValue(mixed $default): mixed
    {
        // Nếu là class string và class tồn tại, resolve từ container
        if (is_string($default) && $default !== '' && class_exists($default)) {
            return app($default);
        }
        
        // Trả về giá trị gốc
        return $default;
    }

    
    
}
