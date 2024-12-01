<?php

declare(strict_types=1);

namespace RxMake\Database;

use JsonSerializable;
use Serializable;

final class Pagination implements JsonSerializable, Serializable
{
    private int $totalItems;
    private int $totalPages;
    private int $limit;

    public function __construct(int $totalItems, int $limit)
    {
        $this->initialize($totalItems, $limit);
    }

    private function initialize(int $totalItems, int $limit): void
    {
        $this->totalItems = $totalItems;
        $this->totalPages = (int) ceil($totalItems / $limit);
        $this->limit = $limit;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function jsonSerialize(): object
    {
        return (object) [
            'totalCount' => $this->totalItems,
            'limit' => $this->limit,
        ];
    }

    public function serialize(): string|null
    {
        return serialize($this);
    }

    public function unserialize(string $data)
    {
        return unserialize($data);
    }

    public function __serialize(): array
    {
        return (array) $this->jsonSerialize();
    }

    public function __unserialize(array $data): void
    {
        $this->initialize($data['totalCount'], $data['limit']);
    }
}
