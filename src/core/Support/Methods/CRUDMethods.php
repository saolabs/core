<?php

namespace Saola\Core\Support\Methods;

use Illuminate\Http\Request;
use Saola\Core\Masks\EmptyCollection;
use Saola\Core\Masks\EmptyMask;
use ReflectionClass;
use Saola\Core\Validators\ExampleValidator;
use Saola\Core\Repositories\BaseRepository;
use Saola\Core\Validators\Validator;

/**
 * @method \Saola\Core\Repositories\BaseRepository getRepository()
 * @method \Saola\Core\Repositories\BaseRepository setRepository(\Saola\Core\Repositories\BaseRepository $repository)
 * @method \Saola\Core\Repositories\BaseRepository setRepositoryClass(string $repositoryClass)
 * @method \Saola\Core\Repositories\BaseRepository repositoryTap(callable $callback, mixed $default = null, bool $logError = false)
 * @property mixed $repositoryClass
 * @property \Saola\Core\Repositories\BaseRepository $repository
 * @property string $validatorClass
 * @property array $validateAttrs
 * @property string $validatorNamespace
 * @property \Saola\Core\Repositories\BaseRepository $validatorRepository
 * @property string $appNamespace
 * @property \Saola\Core\Validators\Validator $validator
 */
trait CRUDMethods
{
    /**
     * @var string $validatorClass
     * full class name 
     */
    protected $validatorClass = 'ExampleValidator';

    /**
     *
     * @var array
     */
    protected $validateAttrs = [];
    /**
     * validator namespace
     *
     * @var string
     */
    protected $validatorNamespace = 'Saola\Core\Validators';


    /**
     * app validator namespace
     *
     * @var string
     */
    protected $appNamespace = 'App\Validators';

    /**
     * validator repository
     *
     * @var \Saola\Core\Repositories\BaseRepository
     */
    protected $validatorRepository = null;


    

    public function initCRUD()
    {

        return $this;
    }



    /**
     * set validator repository
     *
     * @param \Saola\Core\Repositories\BaseRepository $validatorRepository
     * @return $this instance
     */
    public function setValidatorRepository($validatorRepository)
    {
        if (is_object($validatorRepository) && ($validatorRepository instanceof BaseRepository || is_a($validatorRepository, BaseRepository::class))) {
            $this->validatorRepository = $validatorRepository;
        } elseif (is_string($validatorRepository) && class_exists($validatorRepository)) {
            $this->validatorRepository = app($validatorRepository);
        }
        return $this;
    }

    /**
     * Lấy validator repository
     * 
     * Ưu tiên:
     * 1. validatorRepository (nếu đã set)
     * 2. repository (nếu có)
     * 3. repositoryClass (resolve từ container nếu là string hợp lệ)
     * 4. null
     * 
     * @return \Saola\Core\Repositories\BaseRepository|null
     */
    public function getValidatorRepository()
    {
        // Ưu tiên 1: validatorRepository
        if ($this->validatorRepository !== null) {
            return $this->validatorRepository;
        }

        // Ưu tiên 2: repository
        if ($this->repository !== null) {
            return $this->repository;
        }

        // Ưu tiên 3: repositoryClass (resolve từ container)
        if ($this->repositoryClass && is_string($this->repositoryClass) && class_exists($this->repositoryClass)) {
            return app($this->repositoryClass);
        }

        return null;
    }

    /**
     * dat validator class
     * @param string $validatorClass tên class
     * @return $this instance
     */
    public function setValidatorClass($validatorClass)
    {
        if (class_exists($validatorClass)) {
            $this->validatorClass = $validatorClass;
        } elseif (class_exists($validatorClass . 'Validator')) {
            $this->validatorClass = $validatorClass . 'Validator';
        } elseif (class_exists($this->appNamespace . "\\" . $validatorClass)) {
            $this->validatorClass = $this->appNamespace . "\\" . $validatorClass;
        } elseif (class_exists($this->appNamespace . "\\" . $validatorClass . 'Validator')) {
            $this->validatorClass = $this->appNamespace . "\\" . $validatorClass . 'Validator';
        } elseif (class_exists($this->validatorNamespace . "\\" . $validatorClass)) {
            $this->validatorClass = $this->validatorNamespace . "\\" . $validatorClass;
        } elseif (class_exists($this->validatorNamespace . "\\" . $validatorClass . 'Validator')) {
            $this->validatorClass = $this->validatorNamespace . "\\" . $validatorClass . 'Validator';
        }
        return $this;
    }




    /**
     * lay doi tuong validator
     * @param Request $request
     * @param string $validatorClass
     * @return \Saola\Core\Validators\Validator
     */
    public function getValidator(Request $request, $validatorClass = null)
    {
        if ($validatorClass) {
            $this->setValidatorClass($validatorClass);
        }
        $this->fire('beforegetvalidator', $this, $request);
        if ($this->validatorClass) {
            $c = null;

            if (class_exists($this->validatorClass)) {
                $c = $this->validatorClass;
            } elseif (class_exists($class = $this->validatorNamespace . '\\' . $this->validatorClass)) {
                $c = $class;
            } else {
                $c = 'Saola\Core\Validators\ExampleValidator';
            }
            $rc = new ReflectionClass($c);
            return $rc->newInstanceArgs([$request, $this->getValidatorRepository()]);
        }
        return new ExampleValidator($request, $this->getValidatorRepository());
    }

    /**
     *
     * lay doi tuong validator
     * @param Request $request
     * @param string $validatorClass
     * @return \Saola\Core\Validators\Validator
     */
    public function validator(Request $request, $validatorClass = null)
    {
        $this->fire('beforevalidator', $this, $request);
        $validator = $this->getValidator($request, is_string($validatorClass) ? $validatorClass : null);
        $validator->check(is_array($validatorClass) ? $validatorClass : []);
        return $validator;
    }

    /**
     * lay du lieu da duoc validate
     * @param Request $request
     * @param string|array $ruleOrvalidatorClass
     * @param array $messages
     * @return array
     */
    public function validate(Request $request, $ruleOrvalidatorClass = null, $messages = [])
    {
        $this->fire('beforevalidate', $this, $request);
        return $this->getValidator(
            $request,
            is_string($ruleOrvalidatorClass) ? $ruleOrvalidatorClass : null
        )->validate(
            is_array($ruleOrvalidatorClass) ? $ruleOrvalidatorClass : [],
            is_array($messages) ? $messages : []
        );
    }

    /**
     * lay du lieu da duoc validate
     * @param Request $Request
     * @param string|array $ruleOrvalidatorClass
     * @param array $messages
     * @return array
     */
    public function getValidateData(Request $request, $ruleOrvalidatorClass = null, $messages = [])
    {
        return $this->validate($request, $ruleOrvalidatorClass, $messages);
    }


    public function setValidatoAttrs(...$attrs)
    {
        if (is_array($attrs) && count($attrs)) {
            foreach ($attrs as $attr) {
                if (is_string($attr)) {
                    if ($attr == '*') {
                        $this->validateAttrs = '*';
                        return;
                    }
                    $this->validateAttrs[] = $attr;
                } elseif (is_array($attr)) {
                    $this->validateAttrs = array_merge($this->validateAttrs, $attr);
                }
            }
        }
    }

    public function getValidateAttrs()
    {
        if (is_array($this->validateAttrs) && count($this->validateAttrs)) {
            return $this->validateAttrs;
        }
        return null;
    }


    /**
     * lấy dữ liệu damg5 danh sách
     * @param Request $request
     * @param array $args
     *
     * @return collection
     */
    public function getResults(Request $request, array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($request, $args) {
            return $repository->getResults($request, $args);
        }, EmptyCollection::class);
    }

    public function getDetail(int|array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($args) {
            return $repository->detail($args);
        }, EmptyMask::class);
    }

    public function getTrashedResults(Request $request, array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($request, $args) {
            return $repository->trashed()->getResults($request, $args);
        }, EmptyCollection::class);
    }

    public function moveToTrash(int|array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($args) {
            return $repository->moveToTrash($args);
        }, false);
    }
    public function restoreFromTrash(int|array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($args) {
            return $repository->restoreFromTrash($args);
        }, false);
    }
    public function delete(int|array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($args) {
            return $repository->delete($args);
        }, false);
    }
    public function erase(int|array $args = [])
    {
        return $this->repositoryTap(function ($repository) use ($args) {
            return $repository->erase($args);
        }, false);
    }

    public function update(int|array $args = [], array $data = [])
    {
        return $this->repositoryTap(function ($repository) use ($args, $data) {
            return $repository->update($args, $data);
        }, false);
    }
    public function create(array $data = [])
    {
        return $this->repositoryTap(function ($repository) use ($data) {
            return $repository->create($data);
        }, false);
    }
    public function createMany(array $data = [])
    {
        return $this->repositoryTap(function ($repository) use ($data) {
            return $repository->createMany($data);
        }, false);
    }
}
