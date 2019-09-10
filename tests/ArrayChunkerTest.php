<?php

namespace Exp\Tests;

use Exp\Chunker\ArrayChunker;

class ArrayChunkerTest extends BaseCase
{
    /**
     * @dataProvider arrayProvider
     */
    public function testEach($array)
    {
        $chunker = new ArrayChunker($array, count($array));

        $chunkCount = 2;
        $chunker->each(function ($item, $chunkIndex, $batchIndex) use ($chunkCount, $array) {
            $currentIndex = $chunkIndex * $chunkCount + $batchIndex;
            $this->assertEquals($array[$currentIndex], $item);
        })->chunk($chunkCount);
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testBatch($array)
    {
        $chunker = new ArrayChunker($array, count($array));

        $chunkCount = 2;
        $chunker->batch(function ($batch, $chunkIndex) use ($array, $chunkCount) {
            $slice = array_slice($array, $chunkIndex * $chunkCount, $chunkCount);
            $this->assertEquals($slice, $batch);
        })->chunk($chunkCount);
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testLimit($array)
    {
        $chunker = new ArrayChunker($array, count($array));

        $limit = 5;
        $chunkCount = 2;
        $pushes = [];
        $chunker->limit($limit)->each(function ($item) use (&$pushes) {
            $pushes[] = $item;
        })->chunk($chunkCount);

        $except = [1,2,3,4,5];
        $this->assertEquals($except, $pushes);
    }

    public function arrayProvider()
    {
        return [
            [range(1, 20)]
        ];
    }
}
