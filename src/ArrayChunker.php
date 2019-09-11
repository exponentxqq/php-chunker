<?php

namespace Exp\Chunker;

class ArrayChunker extends BaseChunker
{
    public function __construct(array $source, $total)
    {
        foreach ($source as $item) {
            if (!is_object($item)) {
                $this->source[] = new ItemValue($item);
            } else {
                $this->source[] = $item;
            }
        }
        $this->total = $total;
    }

    protected function pageChunk(int $chunkCount, callable $batchCallable): bool
    {
        return $this->chunkInterval($chunkCount, $batchCallable, false);
    }

    protected function fixChunk(int $chunkCount, callable $batchCallable): bool
    {
        return $this->chunkInterval($chunkCount, $batchCallable, true);
    }

    private function chunkInterval(int $chunkCount, callable $callable, bool $fixed): bool
    {
        $filter = function () {
            if ($this->whereHandler) {
                $filteredSource = array_filter($this->source, $this->whereHandler);
            } else {
                $filteredSource = $this->source;
            }
            return $filteredSource;
        };
        while (($slice = array_slice($filter(), $fixed ? 0 : ($this->chunkIndex * $chunkCount), $chunkCount))) {
            $eachResult = $callable($slice, $this->chunkIndex);
            if (false === $eachResult) {
                return false;
            }
        }
        return true;
    }
}
