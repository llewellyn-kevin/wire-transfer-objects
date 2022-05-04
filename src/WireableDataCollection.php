<?php

namespace LlewellynKevin\WireTransferObjects;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Enumerable;
use Livewire\Wireable;
use Spatie\LaravelData\DataCollection;

class WireableDataCollection extends DataCollection implements Wireable
{
    public function __construct(
        private string $dataClass,
        Enumerable|array|CursorPaginator|Paginator|DataCollection $items,
    ) {
        parent::__construct($dataClass, $items);
    }

    public function toLivewire()
    {
        return [...$this->toArray(), 'lw-datacollection-object' => $this->dataClass];
    }

    public static function fromLivewire($data)
    {
        $dataObject = $data['lw-datacollection-object'];

        $metaIndex = array_search('lw-datacollection-object', array_keys($data));
        array_splice($data, $metaIndex, 1);

        return new static($dataObject, $data);
    }
}
