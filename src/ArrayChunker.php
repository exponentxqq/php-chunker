<?php

namespace Exp\Chunker;

class ArrayChunker extends BaseChunker
{
    public function __construct(array $source, $total)
    {
        $this->source = $source;
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
        if ($this->whereHandler) {
            $filteredSource = array_filter($this->source, $this->whereHandler);
        } else {
            $filteredSource = $this->source;
        }
        while (($slice = array_slice($filteredSource, $fixed ? 0 : ($this->chunkIndex * $chunkCount), $chunkCount))) {
            $eachResult = $callable($slice, $this->chunkIndex);
            if (false === $eachResult) {
                return false;
            }
        }
        return true;
    }
}
