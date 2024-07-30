<?php

declare(strict_types=1);

namespace RxMake\Database;

use Closure;
use DateTime;

/**
 * @template T of BaseModel
 */
class Filter
{
    private array $stack = [
        'type' => 'group',
        'operator' => 'AND',
        'children' => [],
        'parentStack' => null,
    ];

    private array $currentStack;

    public function __construct()
    {
        $this->currentStack = &$this->stack;
    }

    /**
     * Get query string and bindings from an instance.
     *
     * @return array{
     *     query: string,
     *     bindings: array<string>
     * }
     */
    public function get(): array
    {
        $handleExpression = function (array $stack) {
            if ($stack['value'] instanceof DateTime) {
                $stack['value'] = $stack['value']->format('YmdHis');
            }
            else if (is_bool($stack['value'])) {
                $stack['value'] = $stack['value'] ? 1 : 0;
            }
            if (is_array($stack['value'])) {
                foreach ($stack['value'] as $key => $value) {
                    if ($stack['value'] instanceof DateTime) {
                        $stack['value'][$key] = $stack['value']->format('YmdHis');
                    }
                    else if (is_bool($stack['value'])) {
                        $stack['value'] = $stack['value'] ? 1 : 0;
                    }
                }
                $stack['value'] = array_values($stack['value']);
                return [
                    sprintf(
                        '%s %s (%s)',
                        $stack['column'],
                        $stack['operator'],
                        implode(', ', array_map(fn () => '?', $stack['value']))
                    ),
                    $stack['value'],
                ];
            }
            return [
                sprintf(
                    '%s %s %s',
                    $stack['column'],
                    $stack['operator'],
                    '?',
                ),
                [ $stack['value'] ],
            ];
        };
        $handleGroup = function (array $stack) use (&$handleGroup, $handleExpression) {
            $groupQuery = '';
            $groupBindings = [];
            foreach ($stack['children'] as $i => $child) {
                if ($child['type'] === 'group') {
                    [ $queries, $bindings ] = $handleGroup($child);
                }
                else if ($child['type'] === 'expression') {
                    [ $queries, $bindings ] = $handleExpression($child);
                }
                else {
                    continue;
                }
                $groupQuery .= ($i !== 0 ? (' ' . $stack['operator'] . ' ') : '') . $queries;
                $groupBindings = array_merge($groupBindings, $bindings);
            }
            return [ '(' . $groupQuery . ')', $groupBindings ];
        };
        [ $query, $bindings ] = $handleGroup($this->stack);

        return [
            'query' => $query,
            'bindings' => $bindings,
        ];
    }

    /**
     * Add equal(column = value) expression into filter stack.
     *
     * @param string                   $column
     * @param string|int|bool|DateTime $value
     *
     * @return Filter
     */
    public function eq(string $column, string|int|bool|DateTime $value): self
    {
        return $this->expression('=', $column, $value);
    }

    /**
     * Add not-equal(column != value) expression into filter stack.
     *
     * @param string                   $column
     * @param string|int|bool|DateTime $value
     *
     * @return self
     */
    public function neq(string $column, string|int|bool|DateTime $value): self
    {
        return $this->expression('!=', $column, $value);
    }

    /**
     * Add greater-than(column > value) expression into filter stack.
     *
     * @param string                   $column
     * @param string|int|bool|DateTime $value
     *
     * @return self
     */
    public function gt(string $column, string|int|bool|DateTime $value): self
    {
        return $this->expression('>', $column, $value);
    }

    /**
     * Add greater-than-or-equal(column >= value) expression into filter stack.
     *
     * @param string                   $column
     * @param string|int|bool|DateTime $value
     *
     * @return self
     */
    public function gte(string $column, string|int|bool|DateTime $value): self
    {
        return $this->expression('>=', $column, $value);
    }

    /**
     * Add lesser-than(column < value) expression into filter stack.
     *
     * @param string                   $column
     * @param string|int|bool|DateTime $value
     *
     * @return self
     */
    public function lt(string $column, string|int|bool|DateTime $value): self
    {
        return $this->expression('<', $column, $value);
    }

    /**
     * Add lesser-than-or-equal(column <= value) expression into filter stack.
     *
     * @param string                   $column
     * @param string|int|bool|DateTime $value
     *
     * @return self
     */
    public function lte(string $column, string|int|bool|DateTime $value): self
    {
        return $this->expression('<=', $column, $value);
    }

    /**
     * Add between(column >= min AND column <= max) expression into filter stack.
     *
     * @param string              $column
     * @param string|int|DateTime $min
     * @param string|int|DateTime $max
     *
     * @return self
     */
    public function between(string $column, string|int|DateTime $min, string|int|DateTime $max): self
    {
        return $this->and(
            fn (self $f) => $f
                ->gte($column, $min)
                ->lte($column, $max),
        );
    }

    /**
     * Add not-between(column < min OR column > max) expression into filter stack.
     * @param string              $column
     * @param string|int|DateTime $min
     * @param string|int|DateTime $max
     *
     * @return self
     */
    public function notBetween(string $column, string|int|DateTime $min, string|int|DateTime $max): self
    {
        return $this->or(
            fn (self $f) => $f
                ->gt($column, $max)
                ->lt($column, $min),
        );
    }

    /**
     * Add like(column LIKE value) expression into filter stack.
     *
     * @param string $column
     * @param string $value
     *
     * @return self
     */
    public function like(string $column, string $value): self
    {
        return $this->expression('LIKE', $column, $value);
    }

    /**
     * Add not-like(column NOT LIKE value) expression into filter stack.
     *
     * @param string $column
     * @param string $value
     *
     * @return self
     */
    public function notLike(string $column, string $value): self
    {
        return $this->expression('NOT LIKE', $column, $value);
    }

    /**
     * Add in(column IN (...VALUES)) expression into filter stack.
     *
     * @param string $column
     * @param array<string|int|bool|DateTime>  $values
     *
     * @return self
     */
    public function in(string $column, array $values): self
    {
        return $this->expression('IN', $column, $values);
    }

    /**
     * Add not-in(column NOT IN (...VALUES)) expression into filter stack.
     *
     * @param string $column
     * @param array<string|int|bool|DateTime>  $values
     *
     * @return self
     */
    public function notIn(string $column, array $values): self
    {
        return $this->expression('NOT IN', $column, $values);
    }

    /**
     * Add or pipe group into filter stack.
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function or(Closure $callback): self
    {
        return $this->group('OR', $callback);
    }

    /**
     * Add and pipe group into filter stack.
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function and(Closure $callback): self
    {
        return $this->group('AND', $callback);
    }

    private function expression(string $operator, string $column, string|int|bool|DateTime|array $value): self
    {
        $this->currentStack['children'][] = [
            'type' => 'expression',
            'operator' => $operator,
            'column' => $column,
            'value' => $value,
        ];

        return $this;
    }

    private function group(string $operator, Closure $callback): self
    {
        $newStack = [
            'type' => 'group',
            'operator' => $operator,
            'children' => [],
            'parentStack' => &$this->currentStack,
        ];
        $this->currentStack['children'][] = &$newStack;
        $this->currentStack = &$newStack;
        $callback($this);
        $this->currentStack = &$this->currentStack['parentStack'];

        return $this;
    }
}
