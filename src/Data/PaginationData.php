<?php
namespace Daniardev\LaravelTsd\Data;

class PaginationData
{
    public function __construct(
        public string $sortBy = 'created_at',
        public string $direction = 'desc',
        public int $page = 1,
        public int $size = 10,
    ) {}
}
