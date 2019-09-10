<?php

namespace Exp\Chunker;

use Illuminate\Support\Collection;

class CollectionChunker extends BaseChunker
{
    public function __construct(Collection $collection, $total)
    {
        $this->source = $collection;
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
        $filteredSource = $this->whereHandler ? $this->source->filter($this->whereHandler) : $this->source;
        while (
            ($slice = $filteredSource->slice($fixed ? 0 : ($chunkCount * $this->chunkIndex), $chunkCount))->isNotEmpty()
        ) {
            $chunkResult = $callable($slice, $this->chunkIndex);
            if (false === $chunkResult) {
                return false;
            }
        }
        return true;
    }
}
