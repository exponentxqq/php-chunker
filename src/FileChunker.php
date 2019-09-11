<?php

namespace Exp\Chunker;

use Exp\Chunker\Exceptions\NotSupportFixedException;

class FileChunker extends BaseChunker
{
    public function __construct($filePath)
    {
        $this->source = new \SplFileObject($filePath, 'rb+');
        $this->total = $this->countLine();
    }

    /**
     * 分页chunk
     *
     * @param $chunkCount
     * @param  callable  $callable
     * @return mixed
     */
    protected function pageChunk(int $chunkCount, callable $callable): bool
    {
        $lines = [];
        $lineCount = 0;
        $this->source->seek($chunkCount * $this->chunkIndex);
        while (!$this->source->eof()) {
            $lines[] = $this->source->current();
            $this->source->next();
            $lineCount++;
            if ($lineCount >= $chunkCount) {
                $chunkResult = $callable($lines, $this->chunkIndex);
                if (false === $chunkResult) {
                    return false;
                }
                $lineCount = 0;
                $lines = [];
            }
        }
        if ($lines) {
            $chunkResult = $callable($lines, $this->chunkIndex);
            if (false === $chunkResult) {
                return false;
            }
        }
        return true;
    }

    /**
     * 固定取第一页
     *
     * @param $chunkCount
     * @param  callable  $callable
     * @return mixed
     */
    protected function fixChunk(int $chunkCount, callable $callable): bool
    {
        throw new NotSupportFixedException("file chunker not support fixed chunk");
    }

    private function countLine()
    {
        $lineCount = 0;
        while (!$this->source->eof()) {
            $lineCount++;
            $this->source->current();
            $this->source->next();
        }
        $this->source->rewind();
        return $lineCount;
    }
}
