<?php

declare(strict_types=1);

namespace RxMake\Database;

use Closure;
use DateTime;
use DB;
use PDO;
use ReflectionClass;
use ReflectionProperty;
use Rhymix\Framework\Exceptions\DBError;
use RuntimeException;
use RxMake\Traits\MapperConstructor;

abstract class BaseModel
{
    use MapperConstructor;

    /**
     * @const Table name.
     */
    public const string TableName = '';

    /**
     * @const Primary key of the table.
     */
    public const string PrimaryKey = 'primarySrl';

    /**
     * @var array<string, array<string, array{name: string, type: string}>>
     */
    private static array $columns = [];

    /**
     * Get a record by an exact primary key.
     *
     * @param int|string $primaryKey
     *
     * @return static|null
     * @throws DBError
     */
    public static function get(int|string $primaryKey): static|null
    {
        $oDB = DB::getInstance();
        $stmt = $oDB->query(
            sprintf(
                'SELECT %s FROM %s AS %s WHERE %s = ?',
                '*',
                static::TableName,
                static::TableName,
                static::PrimaryKey,
            ),
            $primaryKey
        );
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? static::create($data) : null;
    }

    /**
     * Find records by a filter.
     *
     * @param Closure(Filter): Filter $where
     * @param int     $offset
     * @param int     $limit
     *
     * @return array
     * @throws DBError
     */
    public static function find(Closure $where, int $offset = 0, int $limit = 10): array
    {
        $where($filter = new Filter());
        $filterOutput = $filter->get();

        $oDB = DB::getInstance();
        $stmt = $oDB->query(
            sprintf(
                'SELECT %s FROM %s AS %s WHERE %s LIMIT %s OFFSET %s',
                '*',
                static::TableName,
                static::TableName,
                $filterOutput['query'],
                $limit,
                $offset,
            ),
            ...$filterOutput['bindings'],
        );
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function ($record) {
            return static::create($record);
        }, $data);
    }

    /**
     * Insert $item into database.
     *
     * @param static $item
     *
     * @throws DBError
     */
    public static function insert(self $item): bool
    {
        $oDB = DB::getInstance();
        $stmt = $oDB->query(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                static::TableName,
                implode(', ', array_keys($item->getColumns())),
                implode(', ', array_map(fn () => '?', $item->getColumns())),
            ),
            ...array_map(function (array $column) use ($item) {
                return $item->{$column['name']};
            }, $item->getColumns()),
        );
        return $stmt->execute();
    }

    /**
     * Update an $item.
     *
     * @param static $item
     *
     * @throws DBError
     */
    public static function update(self $item): bool
    {
        $oDB = DB::getInstance();
        $stmt = $oDB->query(
            sprintf(
                'UPDATE %s SET %s WHERE %s = ?',
                static::TableName,
                implode(', ', array_map(function ($column) {
                    return $column['name'] . ' = ?';
                }, $item->getColumns())),
                static::PrimaryKey,
            ),
            ...[
                ...array_map(function (array $column) use ($item) {
                    return $item->{$column['name']};
                }, $item->getColumns()),
                $item->{static::PrimaryKey},
            ]
        );
        return $stmt->execute();
    }

    /**
     * Delete an item.
     *
     * @param static $item
     *
     * @return bool
     * @throws DBError
     */
    public static function delete(self $item): bool
    {
        $oDB = DB::getInstance();
        $stmt = $oDB->query(
            sprintf(
                'DELETE FROM %s WHERE %s = ?',
                static::TableName,
                static::PrimaryKey,
            ),
            $item->{static::PrimaryKey},
        );
        return $stmt->execute();
    }

    /**
     * @return array<string, array{name: string, type: string}>
     */
    private static function getColumns(): array
    {
        if (static::$columns[static::class]) {
            return static::$columns[static::class];
        }

        $columns = [];
        $classRef = new ReflectionClass(static::class);
        $propertyRefs = $classRef->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($propertyRefs as $propertyRef) {
            $columns[$propertyRef->getName()] = [
                'name' => $propertyRef->getName(),
                'type' => $propertyRef->getType()->getName(),
                'nullable' => $propertyRef->getType()->allowsNull(),
                'default' => $propertyRef->getDefaultValue(),
            ];
        }
        return static::$columns[static::class] = $columns;
    }

    /**
     * Create a new instance.
     *
     * @param array|object $data
     *
     * @return static
     */
    private static function create(array|object $data): static
    {
        $obj = new static();
        foreach (static::getColumns() as $name => $column) {
            if (!isset($data[$name])) {
                if ($column['default']) {
                    $obj->{$name} = $column['default'];
                    continue;
                }
                if ($column['nullable']) {
                    continue;
                }
                throw new RuntimeException();
            }
            if ($column['type'] === DateTime::class) {
                $obj->{$name} = DateTime::createFromFormat(
                    format: 'YmdHis',
                    datetime: $data[$name],
                );
                continue;
            }
            if ($column['type'] === 'object') {
                $obj->{$name} = json_decode($data[$name]);
                continue;
            }
            if ($column['type'] === 'string') {
                $obj->{$name} = (string) $data[$name];
                continue;
            }
            if ($column['type'] === 'int') {
                $obj->{$name} = (int) $data[$name];
                continue;
            }
            if ($column['type'] === 'float') {
                $obj->{$name} = (float) $data[$name];
                continue;
            }
            if ($column['type'] === 'bool') {
                $obj->{$name} = (int) $data[$name] === 1;
                continue;
            }
            throw new RuntimeException();
        }
        return $obj;
    }
}
