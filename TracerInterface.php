<?php

namespace HexMakina\Tracer;

use HexMakina\Crudites\Interfaces\TableManipulationInterface;
use HexMakina\Crudites\Interfaces\QueryInterface;

interface TracerInterface
{
    const CODE_CREATE = 'C';
    const CODE_SELECT = 'R';
    const CODE_UPDATE = 'U';
    const CODE_DELETE = 'D';

    public function tracing_table(): TableManipulationInterface;

    public function trace(QueryInterface $q, $operator_id, $model_id): bool;
    public function traces() : array;

    public function query_code($sql_statement): string;

    // public function history($table_name, $table_pk, $sort='DESC') : array;
}
