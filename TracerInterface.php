<?php

namespace HexMakina\Tracer;

use HexMakina\Crudites\Interfaces\TableManipulationInterface;
use HexMakina\Crudites\Interfaces\QueryInterface;

interface TracerInterface
{
    public const CODE_CREATE = 'C';
    public const CODE_SELECT = 'R';
    public const CODE_UPDATE = 'U';
    public const CODE_DELETE = 'D';

    public function tracing_table(): TableManipulationInterface;

    public function trace(QueryInterface $q, $operator_id, $model_id): bool;
    public function traces($options = []): array;

    public function query_code($sql_statement): string;

    // public function history($table_name, $table_pk, $sort='DESC') : array;
}
