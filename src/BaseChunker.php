<?php

namespace Exp\Chunker;

use Exp\Chunker\Exceptions\TotalInvalidException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * 该类用于将数据分批次处理
 */
abstract class BaseChunker
{
    /** 需要处理的数据 */
    protected $source;
    /** 数据总数 */
    protected $total = 0;
    /** 是否固定批次，固定批次时需要注意条件处理，避免陷入死循环 */
    protected $fixedChunk = false;

    /**@var ProgressBar $progressBar [进度条，只在命令行模式下生效] */
    private $progressBar;
    /** 限制处理的item数量，达到limit的限制后，跳过后续的item */
    private $limit = 0;
    /** log的process名称 */
    private $name = '';
    /** 标识是否跳过每个批次或每个item的处理过程中的错误，如果为false，遇到错误时会抛出异常终止chunk */
    private $errorSkip = false;

    /** 当前处理的批次 */
    protected $chunkIndex = 0;

    /** item的处理函数 */
    protected $eachHandler;
    /** batch的处理函数 */
    protected $batchHandler;
    /** 过滤数据的条件处理函数，每个新的chunk都会调用该handler（如果有的话） */
    protected $whereHandler;

    /**
     * 分页chunk
     *
     * @param $chunkCount
     * @param  callable  $callable
     * @return mixed
     */
    abstract protected function pageChunk(int $chunkCount, callable $callable): bool;

    /**
     * 固定取第一页
     *
     * @param $chunkCount
     * @param  callable  $callable
     * @return mixed
     */
    abstract protected function fixChunk(int $chunkCount, callable $callable): bool;

    /**
     * 设置是否为固定取第一页，为避免死循环的情况，请注意chunk的条件
     *
     * @return $this
     */
    public function fixed()
    {
        $this->fixedChunk = true;
        return $this;
    }

    /**
     * 显示进度条，该方法只可以在命令行模式下生效
     *
     * @return $this
     */
    final public function showProgress()
    {
        if (!$this->total) {
            throw new TotalInvalidException(0, 'Total is 0');
        }
        $this->createProgressBar();
        return $this;
    }

    /**
     * 指定log的process的名称
     *  log格式：processed for name 1/22
     *
     * @param $name
     * @return $this
     */
    final public function progressName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 限制处理多少个item
     *
     * @param  int  $count
     * @return $this
     */
    final public function limit(int $count)
    {
        $this->limit = $count;
        return $this;
    }

    /**
     * 跳过错误
     *
     * @return $this
     */
    final public function errorSkip()
    {
        $this->errorSkip = true;
        return $this;
    }

    /**
     * chunk的执行方法
     *
     * @param  int  $chunkCount  [每个批次的数量]
     * @return bool
     */
    public function chunk(int $chunkCount)
    {
        $callable = function (iterable $collection) use ($chunkCount) {
            $batchResult = $this->runBatch($collection, $chunkCount);
            $this->chunkIndex++;
            return $batchResult;
        };
        if ($this->fixedChunk) {
            $chunkResult = $this->fixChunk($chunkCount, $callable);
        } else {
            $chunkResult = $this->pageChunk($chunkCount, $callable);
        }
        if ($this->progressBar) {
            $this->progressBar->finish();
            echo "\n";
        }
        return $chunkResult;
    }

    /**
     * 传入每个item的处理方法
     *
     * @param  callable($item, $chunkIndex, $batchIndex)  $callable
     * @return $this
     */
    public function each(callable $callable)
    {
        $this->eachHandler = $callable;
        return $this;
    }

    /**
     * 传入每批chunk的处理方法
     *
     * @param  callable($batch, $chunkIndex)  $callable
     * @return $this
     */
    public function batch(callable $callable)
    {
        $this->batchHandler = $callable;
        return $this;
    }

    /**
     * 每次chunk都会调用传入的where方法进行过滤
     *
     * @param  callable  $callable
     * @return $this
     */
    public function where(callable $callable)
    {
        $this->whereHandler = $callable;
        return $this;
    }

    private function createProgressBar()
    {
        $output = new ConsoleOutput();
        $progressBar = new ProgressBar($output, $this->total);
        if ('\\' !== DIRECTORY_SEPARATOR) {
            $progressBar->setEmptyBarCharacter('░');    // light shade character \u2591
            $progressBar->setProgressCharacter('');
            $progressBar->setBarCharacter('▓');         // dark shade character \u2593
        }
        $this->progressBar = $progressBar;
    }

    private function runBatch(iterable $collection, int $chunkCount)
    {
        if ($this->batchHandler) {
            $batchResult = $this->runAndCatchHandler($this->batchHandler, [$collection, $this->chunkIndex]);
        } else {
            $batchResult = true;
        }
        foreach ($collection as $key => $item) {
            $eachResult = $this->runEach($item, $key, $chunkCount);
            if (false === $eachResult) {
                break;
            }
            if ($this->limit && $this->chunkIndex * $chunkCount + $key + 1 >= $this->limit) {
                return false;
            }
            unset($collection);
        }
        return $batchResult;
    }

    private function runEach($item, int $batchIndex, int $chunkCount)
    {
        if ($this->eachHandler) {
            $eachResult = $this->runAndCatchHandler($this->eachHandler, [$item, $this->chunkIndex, $batchIndex]);
        } else {
            $eachResult = true;
        }
        if ($this->progressBar) {
            $this->progressBar->advance();
        }
        if ($this->name) {
            \Log::debug(
                "processed for ".$this->name." ".
                (string) ($this->chunkIndex * $chunkCount + $batchIndex + 1)."/".$this->total
            );
        }
        unset($item);
        return $eachResult;
    }

    private function runAndCatchHandler(callable $callable, $params)
    {
        try {
            $eachResult = call_user_func_array($callable, $params);
        } catch (\Exception $e) {
            \Log::error($e);
            $eachResult = true;
            if (!$this->errorSkip) {
                throw $e;
            }
        }
        return $eachResult;
    }
}
