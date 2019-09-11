<?php

namespace Exp\Chunker;

use Illuminate\Support\Collection;

class CollectionChunker extends BaseChunker
{
    public function __construct(Collection $collection, $total)
    {
        $this->source = collect();
        foreach ($collection as $item) {
            if (!is_object($item)) {
                $this->source->push(new ItemValue($item));
            } else {
                $this->source->push($item);
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
            return $this->whereHandler ? $this->source->filter($this->whereHandler) : $this->source;
        };
        $offset = $fixed ? 0 : ($chunkCount * $this->chunkIndex);
        while (($slice = $filter()->slice($offset, $chunkCount))->isNotEmpty()) {
            $chunkResult = $callable($slice, $this->chunkIndex);
            if (false === $chunkResult) {
                return false;
            }
        }
        return true;
    }
}
