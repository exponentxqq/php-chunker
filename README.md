# php-chunker

分批次处理array、laravel的collection、laravel的db-query以及文件的数据

## Installation
1. `composer require xuqinqin/php-chunker`

## Example
1. ArrayChunker
    ```php
    <?php
 
    use Exp\Chunker\ArrayChunker;
 
    $arr = [1, 2, 3, 4, 5, 6, 7];
    $chunker = new ArrayChunker($arr, count($arr));
    $chunker->batch(function($batch) {
        // [1, 2], [3, 4], [5, 6], [7]
    })->each(function($item) {
        // 1, 2, 3, 4, 5, 6, 7
    })->chunk(2);
    ```
2. CollectionChunker
    ```php
    <?php
    
    use Exp\Chunker\CollectionChunker;
 
    $collection = collect([1, 2, 3, 4, 5, 6, 7]);
    $chunker = new CollectionChunker($collection, $collection->count());
    $chunker->batch(function ($batch) {
       // collect([1, 2, 3]), collect([4, 5, 6]), collect([7]) 
    })->each(function ($item) {
       // 1, 2, 3, 4, 5, 6, 7
    })->chunk(3);
    ```
3. QueryChunker
    ```php
    <?php
 
    use Exp\Chunker\QueryChunker;
    
    $query = User::query(); // or DB::table('user');
    $chunker = new QueryChunker($query, $query->count());
    $chunker->batch(function ($batch) {
       // $batch is Collection|User[]
    })->each(function (User $user) {
       // 1, 2, 3, 4, 5, 6, 7
    })->chunk(3);
    ```
4. FileChunker
    ```php
    <?php
    
    use Exp\Chunker\FileChunker;
    $file = 'xxx.txt';
    $chunker = new FileChunker($file);
    $chunker->batch(function ($lines) {
       // handle lines 
    })->each(function ($line) {
       // handle line 
    })->chunk(3);
    ```

## Other Example
1. progress-bar(进度条)
    ```php
    $chunker->showProgress()->chunk(3);
    ```
    ![haaha](https://raw.githubusercontent.com/exponentxqq/readme-images/master/php-chunker/progress.png)
    
    ps: 只在命令模式下生效（Effective only in command-line mode）
2. fixed(固定取第一页，配合where方法使用，否则会陷入死循环)
    ```php
    $arr = [1, 2, 3, 4, 5, 6, 7];
    $chunker = new ArrayChunker($arr, count($arr));
    $chunker->fixed()->batch(function ($batch) {
        // handle batch
    })->each(function (ItemValue $item) {
        echo $item->getValue()."\n";
        $item->setValue($item->getValue() + 1);
    })->where(function (ItemValue $item) {
        return $item->getValue() < 5;
    })->chunk(3); // $chunker->source = 5, 5, 5, 5, 5, 6, 7
 
    $collection = collect([1, 2, 3, 4, 5, 6, 7]);
    $chunker = new CollectionChunker($collection, $collection->count());
    $chunker->fixed()->where(function (ItemValue $itemValue) {
        return $itemValue->getValue() > 3;
    })->each(function (ItemValue $itemValue) {
        echo $itemValue->getValue()."\n";
        $itemValue->setValue($itemValue->getValue() - 1);
    })->chunk(2); // $chunker->source = 1, 2, 3, 3, 3, 3, 3
 
    $query = User::query()->where('is_login', 1);
    $chunker = new QueryChunker($query, $query->count());
    $chunker->fixed()->each(function (User $user) {
        $user->is_login = 0;
        $user->save();
    })->chunk(3);
    ```