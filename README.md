# php-chunker

分批次处理数据

## Installation
1. 编辑composer.json
    ```$xslt
    "repositories": [
        {
            "type": "git",
            "url": "git@gitlab.Exp.cn:xu.qinqin/php-chunker.git"
        }
    ],
    ```
2. 执行`composer require xuqinqin/php-chunker:dev-master`

## Example
```$xslt
$arr = [1, 2, 3, 4, 5, 6, 7];
$chunker = new ArrayChunker($arr, count($arr));
$chunker->batch(function($batch) {
    // [1, 2], [3, 4], [5, 6], [7]
})->each(function($item) {
    // 1, 2, 3, 4, 5, 6, 7
})->chunk(2);
```