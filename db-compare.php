<?php

function _log(string $log)
{
    echo "\033[30m$log\033[0m\n";
}

function logError(string $log)
{
    echo "\033[31m$log\033[0m\n";
}

function logSuccess(string $log)
{
    echo "\033[32m$log\033[0m\n";
}

function logWarning(string $log)
{
    echo "\033[33m$log\033[0m\n";
}

function logInfo(string $log)
{
    echo "\033[36m$log\033[0m\n";
}

function openDBConnection(string $host, string $name, string $port, string $user, string $password)
{
    $dsn = "mysql:dbname=$name;host=$host;port=$port;charset=utf8mb4";
    return new \PDO($dsn, $user, $password);
}

function getTables(PDO $connection)
{
    $smtp = $connection->prepare("SHOW TABLES");
    $smtp->execute();
    $result = $smtp->fetchAll();
    return array_map(function ($table) {
        return $table[0];
    }, $result ?? []);
}

function getColumns(PDO $connection, array $tables)
{
    $columns = [];
    $columns_full = [];
    for ($i = 0; $i < count($tables); $i++) {
        $table = $tables[$i];
        $smtp = $connection->prepare("SHOW COLUMNS FROM $table");
        $smtp->execute();
        $cols = $smtp->fetchAll();
        for ($j = 0; $j < count($cols); $j++) {
            $col = $cols[$j];
            $columns[] = $table . ' ' . $col["Field"];
            $columns_full[$table . ' ' . $col["Field"]] = $table . ' ' . $col["Field"] . ' ' . $col["Type"] . ' ' . $col["Null"] . ' ' . $col["Key"] . ' ' . $col["Default"] . ' ' . $col["Extra"];
        }
    }
    return [$columns, $columns_full];
}

function getDiffTables(array $tables_db_01, array $tables_db_02)
{
    return array_diff($tables_db_02, $tables_db_01);
}

function getDiffColumns(array $columns_db_01, array $columns_db_02, bool $detail = false)
{
    $diff = [];

    if (!$detail)
        $diff = array_diff($columns_db_02, $columns_db_01);

    if ($detail) {
        $db_01_key = array_keys($columns_db_01);
        $db_02_key = array_keys($columns_db_02);
        $keys = array_intersect($db_02_key, $db_01_key);
        for ($j = 0; $j < count($keys); $j++) {
            if (isset($keys[$j])) {
                $key = $keys[$j];
                if ($columns_db_01[$key] !== $columns_db_02[$key]) {
                    $diff[] = $columns_db_02[$key] . ' !== ' . $columns_db_01[$key];
                }
            }
        }
    }

    return $diff;
}

function dbCompare(array $DB_01, array $DB_02, ?string $merge_direction)
{
    $columns_diff_in_db_02 = [];

    $DB_01["connection"] = openDBConnection($DB_01["host"], $DB_01["name"], $DB_01["port"], $DB_01["user"], $DB_01["password"]);
    $DB_02["connection"] = openDBConnection($DB_02["host"], $DB_02["name"], $DB_02["port"], $DB_02["user"], $DB_02["password"]);

    $DB_01["tables"] = getTables($DB_01["connection"]);
    $DB_02["tables"] = getTables($DB_02["connection"]);

    $db_01_columns = getColumns($DB_01["connection"], $DB_01["tables"]);
    $db_02_columns = getColumns($DB_02["connection"], $DB_02["tables"]);

    $DB_01["columns"] = $db_01_columns[0];
    $DB_02["columns"] = $db_02_columns[0];

    $DB_01["columns_full"] = $db_01_columns[1];
    $DB_02["columns_full"] = $db_02_columns[1];

    $columns_diff_in_db_02 = getDiffColumns($DB_01["columns_full"], $DB_02["columns_full"], true);

    $db_01_tag = $DB_01["host"] . '.' . $DB_01["name"];
    $db_02_tag = $DB_02["host"] . '.' . $DB_02["name"];

    if ($merge_direction == null || $merge_direction == '>') {
        logWarning("Tables only in: $db_01_tag");
        foreach ((array_diff($DB_01["tables"], $DB_02["tables"])) as $table) {
            logError($table);
        }
    }

    if ($merge_direction == null || $merge_direction == '<') {
        logWarning("Tables only in: $db_02_tag");
        foreach ((array_diff($DB_02["tables"], $DB_01["tables"])) as $table) {
            logError($table);
        }
    }

    if ($merge_direction == null || $merge_direction == '>') {
        logWarning("Columns only in: $db_01_tag");
        foreach ((array_diff($DB_01["columns"], $DB_02["columns"])) as $table) {
            logError($table);
        }
    }

    if ($merge_direction == null || $merge_direction == '<') {
        logWarning("Columns only in: $db_02_tag");
        foreach ((array_diff($DB_02["columns"], $DB_01["columns"])) as $table) {
            logError($table);
        }
    }

    logWarning("Columns diff: $db_02_tag <> $db_01_tag");
    foreach ($columns_diff_in_db_02 as $column) {
        logError($column);
    }
}

function run(string $db_to_compare_01, string $db_to_compare_02, ?string $merge_direction)
{
    logInfo("........ START DB COMPARE SCRIPT ........");

    $db_01 = [
        "host" => explode(',', $db_to_compare_01)[0],
        "port" => explode(',', $db_to_compare_01)[1],
        "name" => explode(',', $db_to_compare_01)[2],
        "user" => explode(',', $db_to_compare_01)[3],
        "password" => explode(',', $db_to_compare_01)[4],
        "connection" => null,
        "tables" => [],
        "columns" => [],
        "columns_full" => []
    ];
    $db_02 = [
        "host" => explode(',', $db_to_compare_02)[0],
        "port" => explode(',', $db_to_compare_02)[1],
        "name" => explode(',', $db_to_compare_02)[2],
        "user" => explode(',', $db_to_compare_02)[3],
        "password" => explode(',', $db_to_compare_02)[4],
        "connection" => null,
        "tables" => [],
        "columns" => [],
        "columns_full" => []
    ];

    logInfo("...........................................");
    logInfo("................................");
    logInfo(".....................");
    _log("\n");

    dbCompare($db_01, $db_02, $merge_direction);

    _log("\n");
    logInfo(".....................");
    logInfo("................................");
    logInfo("...........................................");
    logInfo("............ END DB COMPARE SCRIPT ...........");
}

$db_to_compare_01 = $argv[1] ?? null;
$db_to_compare_02 = $argv[2] ?? null;
$merge_direction = $argv[3] ?? null;

if ($db_to_compare_01 == null) {
    logError('A string de conexão 01 não foi informada e deve seguir o exemplo host,port,db_name,user,password');
    return;
}

if ($db_to_compare_02 == null) {
    logError('A string de conexão 02 não foi informada e deve seguir o exemplo host,port,db_name,user,password');
    return;
}

run($db_to_compare_01, $db_to_compare_02, $merge_direction);
