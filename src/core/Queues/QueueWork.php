<?php

namespace Saola\Core\Queues;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class QueueWork implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * callble
     *
     * @var Closure
     */
    public $callable;
    public $args = [];
    /**
     * callble
     *
     * @var Closure
     */
    public $onFailed = null;
    public $failedArgs = [];
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 86400;
    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;
    /**
     * Create a new job instance.
     * @param Closure $callable
     * @param array $args
     * @return void
     */
    public function __construct($callable, $args = [], $onFailed = null, $failedArgs = [])
    {
        //
        $this->callable = $callable;
        $this->args = $args;
        if ($onFailed && is_callable($onFailed)){
            $this->onFailed = $onFailed;
            if(is_array($failedArgs))
                $this->failedArgs = $failedArgs;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        call_user_func_array($this->callable, $this->args);
    }


    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        // Send user notification of failure, etc...
        $onFailed = $this->onFailed;
        if ($onFailed && is_callable($onFailed))
            call_user_func_array($onFailed, $this->failedArgs);
    }
}
