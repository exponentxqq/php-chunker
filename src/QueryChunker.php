<?php

namespace Exp\Chunker;

use Illuminate\Database\Eloquent\Builder;

class QueryChunker extends BaseChunker
{
    public function __construct(Builder $builder, $total)
    {
        $this->source = $builder;
        $this->total = $total;
    }

    protected function pageChunk(int $chunkCount, callable $batchCallable): bool
    {
        if ($this->whereHandler) {
            $query = call_user_func_array($this->whereHandler, [$this->source]);
        } else {
            $query = $this->source;
        }
        return $query->chunk($chunkCount, $batchCallable);
    }

    protected function fixChunk(int $chunkCount, callable $batchCallable): bool
    {
        // 固定获取第一页，因为上一个第一页已经被更新了数据，不满足条件了
        $page = 1;
        do {
            if ($this->whereHandler) {
                $query = call_user_func_array($this->whereHandler, [$this->source]);
            } else {
                $query = $this->source;
            }
            $results = $query->forPage($page, $chunkCount)->get();
            $resultsCount = $results->count();

            if (!$resultsCount) {
                break;
            }
            if (false === $batchCallable($results, $this->chunkIndex)) {
                return false;
            }
            unset($results);
        } while ($resultsCount == $chunkCount);

        return true;
    }
}
