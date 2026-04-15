<?php

namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\WorkerStarting;
use Saola\Core\System\System;
use Saola\Core\Services\Service;
use Saola\Core\Engines\ViewManager;
use Saola\Core\Engines\ViewDataEngine;
use Saola\Core\Engines\CacheEngine;
use Saola\Core\Engines\Helper;
use Saola\Core\Repositories\BaseRepository;
use Saola\Core\Concerns\MagicMethods;
use Saola\Core\Contracts\OctaneCompatible;
use Saola\Core\Http\Http;
use Saola\Core\Http\Client;
use Saola\Core\Languages\Locale;
use Saola\Core\Engines\ShortCode;

class OctaneServiceProvider extends ServiceProvider
{
    protected $container = [];
    
    /**
     * Danh sách các lớp triển khai OctaneCompatible
     * 
     * @var array
     */
    protected $octaneAwareClasses = [];
    
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!$this->app->bound('octane')) {
            // octane not found
            return;
        }

        // Phát hiện các lớp triển khai OctaneCompatible
        $this->discoverOctaneAwareClasses();

        // Xử lý khi worker bắt đầu
        $this->app['events']->listen(WorkerStarting::class, function () {
            // Khởi tạo trạng thái ban đầu
            $this->prepareOctaneEnvironment();
        });

        // Xử lý khi nhận request
        $this->app['events']->listen(RequestReceived::class, function () {
            // Chuẩn bị cho request mới
            $this->prepareForNextRequest();
        });

        // Xử lý khi request kết thúc
        $this->app['events']->listen(RequestTerminated::class, function () {
            // Reset các trạng thái tĩnh sau khi xử lý request
            $this->resetStaticState();
            $this->resetServicesState();
        });
    }

    /**
     * Add service to the container
     *
     * @param mixed $service
     * @return $this
     */
    public function addService($service)
    {
        $this->container[] = $service;
        return $this;
    }

    /**
     * Phát hiện các lớp triển khai OctaneCompatible
     * 
     * @return void
     */
    protected function discoverOctaneAwareClasses(): void
    {
        // Thêm các lớp đã biết triển khai OctaneCompatible
        $this->octaneAwareClasses = [
            // \Saola\Core\OctaneAwareService::class,
            // Thêm các lớp khác tại đây
        ];
    }

    /**
     * Chuẩn bị môi trường cho Octane
     */
    protected function prepareOctaneEnvironment(): void
    {
        // Cấu hình ban đầu cho worker
    }

    /**
     * Chuẩn bị cho request tiếp theo
     */
    protected function prepareForNextRequest(): void
    {
        // Thực hiện các tác vụ chuẩn bị cho mỗi request mới
    }

    /**
     * Reset trạng thái tĩnh
     */
    protected function resetStaticState(): void
    {
        // Reset các trạng thái tĩnh chính
        $this->resetViewEngines();
        
        // Reset trạng thái của System class
        $this->resetSystemState();

        // Reset singleton instances
        $this->resetSingletonInstances();

        // Reset HTTP classes
        $this->resetHttpClasses();

        // Reset Locale
        $this->resetLocaleState();

        // Reset CacheEngine
        $this->resetCacheEngineState();

        // Reset Helper
        $this->resetHelperState();

        // Reset trạng thái của MagicMethods và Event Listeners
        $this->resetMagicMethodsState();
        
        // Reset trạng thái của các lớp triển khai OctaneCompatible
        $this->resetOctaneAwareClasses();
    }
    
    /**
     * Reset trạng thái của các lớp triển khai OctaneCompatible
     * 
     * @return void
     */
    protected function resetOctaneAwareClasses(): void
    {
        foreach ($this->octaneAwareClasses as $class) {
            if (class_exists($class) && is_subclass_of($class, OctaneCompatible::class)) {
                // Reset trạng thái tĩnh
                $class::resetStaticState();
                
                // Reset trạng thái của instance nếu đã được đăng ký trong container
                if ($this->app->bound($class)) {
                    $instance = $this->app->make($class);
                    $instance->resetInstanceState();
                }
            }
        }
    }

    /**
     * Reset trạng thái của System class
     */
    protected function resetSystemState(): void
    {
        // Reset các thuộc tính tĩnh cụ thể của System 
        // mà có thể gây rò rỉ trạng thái giữa các requests
        if (class_exists(System::class)) {
            // Reset các trạng thái tĩnh quan trọng
            $reflectionClass = new \ReflectionClass(System::class);
            $staticProperties = $reflectionClass->getStaticProperties();
            
            // Reset các thuộc tính tĩnh cụ thể mà không phải là readonly
            if (isset($staticProperties['_appinfo'])) {
                System::$_appinfo = null;
            }
            
            // Reset filemanager instance
            try {
                $filemanagerProperty = $reflectionClass->getProperty('filemanager');
                $filemanagerProperty->setAccessible(true);
                $filemanagerProperty->setValue(null, null);
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
            
            // Reset packages, routes, menus - chỉ reset nếu cần thiết
            // Lưu ý: Có thể cần giữ lại giữa các request nếu là cấu hình global
            // Nếu cần reset, uncomment các dòng sau:
            // if (isset($staticProperties['packages'])) {
            //     System::$packages = [];
            // }
            // if (isset($staticProperties['routes'])) {
            //     System::$routes = [];
            // }
            // if (isset($staticProperties['menus'])) {
            //     System::$menus = [];
            // }
        }
    }

    /**
     * Reset View Engines
     * 
     * LƯU Ý: ViewContextManager KHÔNG được reset vì:
     * - Contexts cần được giữ lại giữa các requests (persistent state)
     * - Contexts có thể được cập nhật động (ví dụ: khi admin đổi theme)
     * - ViewContextManager được đăng ký như singleton và contexts là shared state
     */
    protected function resetViewEngines(): void
    {
        // Reset ViewManager
        if (class_exists(ViewManager::class)) {
            ViewManager::$shared = false;
            ViewManager::$themeFolder = '';
        }

        // Reset ViewDataEngine
        if (class_exists(ViewDataEngine::class)) {
            ViewDataEngine::$shared = false;
        }

        // KHÔNG reset ViewContextManager - contexts phải được giữ lại
        // ViewContextManager::resetInstanceState() đã được implement để không reset contexts

        // Reset ViewEngine instances - chỉ reset cache, không reset context manager
        // ViewEngine instances sẽ được reset thông qua resetServicesState()
        // ViewEngine chỉ reset resolvedPaths cache, không ảnh hưởng đến ViewContextManager
    }

    /**
     * Reset Singleton Instances
     */
    protected function resetSingletonInstances(): void
    {
        // Reset ShortCode instance
        if (class_exists(ShortCode::class)) {
            $reflection = new \ReflectionClass(ShortCode::class);
            try {
                $property = $reflection->getProperty('intance');
                $property->setAccessible(true);
                $property->setValue(null, null);
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
        }
    }

    /**
     * Reset HTTP Classes
     */
    protected function resetHttpClasses(): void
    {
        // Reset Http class
        if (class_exists(Http::class)) {
            $reflection = new \ReflectionClass(Http::class);
            try {
                // Reset instance
                $instanceProperty = $reflection->getProperty('instance');
                $instanceProperty->setAccessible(true);
                $instanceProperty->setValue(null, null);

                // Reset returnType
                $returnTypeProperty = $reflection->getProperty('returnType');
                $returnTypeProperty->setAccessible(true);
                $returnTypeProperty->setValue(null, '');

                // Reset debug mode và promise mode về mặc định
                $debugModeProperty = $reflection->getProperty('_debugMode');
                $debugModeProperty->setAccessible(true);
                $debugModeProperty->setValue(null, false);

                $usePromiseProperty = $reflection->getProperty('_usePromise');
                $usePromiseProperty->setAccessible(true);
                $usePromiseProperty->setValue(null, true);
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
        }

        // Reset Client class
        if (class_exists(Client::class)) {
            $reflection = new \ReflectionClass(Client::class);
            try {
                // Reset instance
                $instanceProperty = $reflection->getProperty('instance');
                $instanceProperty->setAccessible(true);
                $instanceProperty->setValue(null, null);

                // Reset returnType
                $returnTypeProperty = $reflection->getProperty('returnType');
                $returnTypeProperty->setAccessible(true);
                $returnTypeProperty->setValue(null, '');
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
        }
    }

    /**
     * Reset Locale State
     */
    protected function resetLocaleState(): void
    {
        if (class_exists(Locale::class)) {
            $reflection = new \ReflectionClass(Locale::class);
            try {
                $property = $reflection->getProperty('data');
                $property->setAccessible(true);
                $property->setValue(null, null);
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
        }
    }

    /**
     * Reset CacheEngine State
     */
    protected function resetCacheEngineState(): void
    {
        if (class_exists(CacheEngine::class)) {
            $reflection = new \ReflectionClass(CacheEngine::class);
            try {
                $property = $reflection->getProperty('domain');
                $property->setAccessible(true);
                $property->setValue(null, null);
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
        }
    }

    /**
     * Reset Helper State
     */
    protected function resetHelperState(): void
    {
        if (class_exists(Helper::class)) {
            $reflection = new \ReflectionClass(Helper::class);
            try {
                $property = $reflection->getProperty('device');
                $property->setAccessible(true);
                $property->setValue(null, null);
            } catch (\ReflectionException $e) {
                // Property không tồn tại hoặc không thể truy cập
            }
        }
    }

    /**
     * Reset trạng thái của MagicMethods và Event Listeners
     */
    protected function resetMagicMethodsState(): void
    {
        // Reset các event listeners và dynamic methods
        // để tránh rò rỉ trạng thái giữa các requests
        if (trait_exists(MagicMethods::class)) {
            $reflection = new \ReflectionClass(MagicMethods::class);
            
            // Tìm thuộc tính tĩnh $methods
            try {
                $methodsProperty = $reflection->getProperty('methods');
                $methodsProperty->setAccessible(true);
                
                // Lưu giữ các phương thức global
                $methods = $methodsProperty->getValue();
                $globalMethods = $methods['@global'] ?? ['static' => [], 'nonstatic' => []];
                
                // Reset và chỉ giữ lại global methods
                $methodsProperty->setValue([
                    '@global' => $globalMethods
                ]);
            } catch (\ReflectionException $e) {
                // Không tìm thấy thuộc tính, bỏ qua
            }
        }
        
        // Reset các lớp sử dụng MagicMethods
        $this->resetServiceState();
        $this->resetRepositoryState();
    }
    
    /**
     * Reset trạng thái của Service
     */
    protected function resetServiceState(): void
    {
        if (class_exists(Service::class)) {
            // Reset các trạng thái tĩnh của Service nếu cần
        }
    }
    
    /**
     * Reset trạng thái của Repository
     */
    protected function resetRepositoryState(): void
    {
        if (class_exists(BaseRepository::class)) {
            // Reset các trạng thái tĩnh của BaseRepository nếu cần
        }
    }

    protected function resetServicesState(): void
    {
        // Reset các phương thức có thể reset trạng thái của các service
        $resetFunctions = ['reset', 'resetState', 'clear', 'destroy'];
        // Reset các service trong container
        foreach ($this->container as $service) {
            // Kiểm tra nếu service là đối tượng và có phương thức reset
            if(!is_object($service)) {
                continue;
            }
            
            // Nếu service triển khai OctaneCompatible, gọi resetInstanceState
            if ($service instanceof OctaneCompatible) {
                $service->resetInstanceState();
                continue;
            }
            
            // Reset các phương thức có thể reset trạng thái của service
            foreach($resetFunctions as $function) {
                // Kiểm tra nếu phương thức tồn tại
                if(method_exists($service, $function)) {
                    // Gọi phương thức reset trạng thái của service
                    $service->$function();
                }
            }
        }
    }
} 