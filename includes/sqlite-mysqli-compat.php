<?php
/**
 * Small mysqli-style adapter used by the existing portal pages when SQLite is selected.
 * Keeps their current query and form code working without adding a second application stack.
 */

class AakashSqliteConnection
{
    private $pdo;
    public $insert_id = 0;
    public $affected_rows = 0;

    public function __construct($path, $schemaPath)
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the SQLite data directory.');
        }

        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new RuntimeException('SQLite schema file could not be read.');
        }
        // The schema uses CREATE IF NOT EXISTS and INSERT OR IGNORE, so this is safe
        // to run on every connection and also applies additive setup to an older DB.
        $this->pdo->exec($schema);
    }

    public function set_charset($charset)
    {
        return true;
    }

    public function prepare($sql)
    {
        return new AakashSqliteStatement($this, $this->pdo, $this->translate($sql));
    }

    public function query($sql)
    {
        $sql = $this->translate($sql);
        if (preg_match('/^\s*(SELECT|WITH|PRAGMA)\b/i', $sql)) {
            $statement = $this->pdo->query($sql);
            return new AakashSqliteResult($statement->fetchAll());
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $this->insert_id = (int) $this->pdo->lastInsertId();

        return new AakashSqliteResult(array());
    }

    public function real_escape_string($value)
    {
        return str_replace("'", "''", (string) $value);
    }

    public function recordInsertId()
    {
        $this->insert_id = (int) $this->pdo->lastInsertId();
    }

    public function translate($sql)
    {
        $sql = preg_replace('/\bNOW\s*\(\s*\)/i', 'CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace(
            "/IF\\s*\\(\\s*status\\s*=\\s*'active'\\s*,\\s*'suspended'\\s*,\\s*'active'\\s*\\)/i",
            "CASE WHEN status = 'active' THEN 'suspended' ELSE 'active' END",
            $sql
        );

        return $sql;
    }
}

class AakashSqliteStatement
{
    private $connection;
    private $pdo;
    private $sql;
    private $parameters = array();
    private $rows = array();
    public $num_rows = 0;

    public function __construct($connection, $pdo, $sql)
    {
        $this->connection = $connection;
        $this->pdo = $pdo;
        $this->sql = $sql;
    }

    public function bind_param($types, &...$parameters)
    {
        $this->parameters = array();
        foreach ($parameters as &$parameter) {
            $this->parameters[] = &$parameter;
        }
        unset($parameter);

        return true;
    }

    public function execute()
    {
        $statement = $this->pdo->prepare($this->sql);
        $values = array();
        foreach ($this->parameters as $parameter) {
            $values[] = $parameter;
        }

        $statement->execute($values);
        $this->connection->affected_rows = $statement->rowCount();
        $this->rows = $statement->columnCount() > 0 ? $statement->fetchAll() : array();
        $this->num_rows = count($this->rows);
        $this->connection->recordInsertId();

        return true;
    }

    public function get_result()
    {
        return new AakashSqliteResult($this->rows);
    }

    public function store_result()
    {
        return true;
    }

    public function close()
    {
        return true;
    }
}

class AakashSqliteResult
{
    private $rows;
    private $offset = 0;
    public $num_rows = 0;

    public function __construct($rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc()
    {
        if (!isset($this->rows[$this->offset])) {
            return null;
        }

        return $this->rows[$this->offset++];
    }
}