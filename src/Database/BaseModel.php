<?php

declare(strict_types=1);

namespace RxMake\Database;

use Closure;
use DateTime;
use DB;
use JsonSerializable;
use PDO;
use ReflectionClass;
use ReflectionProperty;
use Rhymix\Framework\Exceptions\DBError;
use RuntimeException;
use RxMake\Traits\MapperConstructor;
use Serializable;
use stdClass;

abstract class BaseModel implements JsonSerializable, Serializable
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
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return isset($data[0]) ? static::create($data[0]) : null;
    }

    /**
     * Find records by a filter.
     *
     * @param Closure(Filter): Filter $where
     * @param string                  $orderBy
     * @param bool                    $ascending True to order ASC, false to order DESC
     * @param int                     $offset
     * @param int                     $limit
     *
     * @return array
     * @throws DBError
     */
    public static function find(Closure $where, string $orderBy = '', bool $ascending = true, int $offset = 0, int $limit = 10): array
    {
        $where($filter = new Filter());
        $filterOutput = $filter->get();

        $oDB = DB::getInstance();
        $stmt = $oDB->query(
            sprintf(
                'SELECT %s FROM %s AS %s WHERE %s ORDER BY %s %s LIMIT %s OFFSET %s',
                '*',
                static::TableName,
                static::TableName,
                $filterOutput['query'],
                $orderBy ?: static::PrimaryKey,
                $ascending ? 'ASC' : 'DESC',
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
        $stmt = $oDB->prepare(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                static::TableName,
                implode(', ', array_map(function ($column) {
                    return '`' . $column['name'] . '`';
                }, $item->getColumns())),
                implode(', ', array_map(fn () => '?', $item->getColumns())),
            ),
        );
        return $stmt->execute(
            array_values(array_map(function (array $column) use ($item) {
                $value = $item->{$column['name']};
                if ($value instanceof DateTime) {
                    $value = $value->format('YmdHis');
                }
                else if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }
                return $value;
            }, $item->getColumns()))
        );
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
        $stmt = $oDB->prepare(
            sprintf(
                'UPDATE %s SET %s WHERE %s = ?',
                static::TableName,
                implode(', ', array_map(function ($column) {
                    return '`' . $column['name'] . '` = ?';
                }, $item->getColumns())),
                static::PrimaryKey,
            )
        );
        return $stmt->execute(array_values([
            ...array_map(function (array $column) use ($item) {
                $value = $item->{$column['name']};
                if ($value instanceof DateTime) {
                    $value = $value->format('YmdHis');
                }
                else if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }
                return $value;
            }, $item->getColumns()),
            $item->{static::PrimaryKey},
        ]));
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
        $obj->fromPlainObject((object) $data);
        return $obj;
    }

    /**
     * Convert the instance to plain object that is compatible with Rhymix SQL.
     *
     * @return stdClass
     */
    public function toPlainObject(): object
    {
        $obj = new stdClass();
        foreach (static::getColumns() as $name => $column) {
            if (!isset($this->{$name})) {
                if ($column['default']) {
                    $obj->{$name} = $column['default'];
                    continue;
                }
                if ($column['nullable']) {
                    $obj->{$name} = null;
                    continue;
                }
                throw new RuntimeException();
            }
            if ($column['type'] === DateTime::class) {
                /** @var DateTime $value */
                $value = $this->{$name};
                $obj->{$name} = $value->format('YmdHis');
                continue;
            }
            if ($column['type'] === 'object') {
                $obj->{$name} = json_encode($this->{$name});
                continue;
            }
            $obj->{$name} = $this->{$name};
        }
        return $obj;
    }

    /**
     * Inject values to the instance from plain object.
     *
     * @param object $data
     *
     * @return void
     */
    public function fromPlainObject(object $data): void
    {
        foreach (static::getColumns() as $name => $column) {
            if (!isset($data->{$name})) {
                if ($column['default']) {
                    $this->{$name} = $column['default'];
                    continue;
                }
                if ($column['nullable']) {
                    $this->{$name} = null;
                    continue;
                }
                throw new RuntimeException();
            }
            if ($column['type'] === DateTime::class) {
                $this->{$name} = DateTime::createFromFormat(
                    format: 'YmdHis',
                    datetime: $data->{$name},
                );
                continue;
            }
            if ($column['type'] === 'object') {
                $this->{$name} = json_decode($data->{$name});
                continue;
            }
            if ($column['type'] === 'string') {
                $this->{$name} = (string) $data->{$name};
                continue;
            }
            if ($column['type'] === 'int') {
                $this->{$name} = (int) $data->{$name};
                continue;
            }
            if ($column['type'] === 'float') {
                $this->{$name} = (float) $data->{$name};
                continue;
            }
            if ($column['type'] === 'bool') {
                $this->{$name} = (int) $data->{$name} === 1;
                continue;
            }
            throw new RuntimeException();
        }
    }

    public function jsonSerialize(): object
    {
        return $this->toPlainObject();
    }

    public function __serialize(): array
    {
        return (array) $this->toPlainObject();
    }

    public function __unserialize(array $data): void
    {
        $this->fromPlainObject((object) $data);
    }

    public function serialize(): string|null
    {
        return serialize($this);
    }

    public function unserialize(string $data)
    {
        return unserialize($data);
    }
}
