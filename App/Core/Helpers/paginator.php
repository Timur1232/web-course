<?php namespace App\Core\Helpers;

/*
 * @template T
 */
final class Paginator {
    public final const DEFAUL_PER_PAGE = 10;

    /**
     * @param T[] $items
     */
    public function __construct(
        public array $items,
        public int $per_page = self::DEFAUL_PER_PAGE,
    ) {
        Error::assert($per_page > 0, __CLASS__.": per_page field must be greater than 0 (per_page > 0)");
    }

    /**
     * @return iterable<?T[]>
     */
    public function paginate(): iterable {
        for ($i = 0; $i < $this->page_count(); ++$i) {
            yield $this->nth_page($i);
        }
    }

    /**
     * @return ?T[]
     *
     * NOTE: 0 <= $page < page_count
     */
    public function nth_page(int $page): ?array {
        if ($page < 0 || $page >= $this->page_count()) return null;
        if ($page === $this->page_count()-1) {
            if ($this->page_rem() === 0) {
                return array_slice($this->items, count($this->items) - $this->per_page);
            } else {
                return array_slice($this->items, count($this->items) - $this->page_rem());
            }
        } else {
            return array_slice($this->items, $page*$this->per_page, $this->per_page);
        }
    }

    public function page_count(): int {
        return (int)(count($this->items) / $this->per_page) + ($this->page_rem() === 0 ? 0 : 1);
    }

    public function page_rem(): int {
        return count($this->items) % $this->per_page;
    }
}
