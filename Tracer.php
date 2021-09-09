<?php

/*
 * Tracer
 *
 */

namespace HexMakina\Tracer;

use \HexMakina\BlackBox\Database\DatabaseInterface;
use \HexMakina\BlackBox\Database\TableManipulationInterface;
use \HexMakina\BlackBox\Database\TraceInterface;

class Tracer implements \HexMakina\BlackBox\Database\TracerInterface
{
    private $tracing_table = null;
    private $database = null;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function setTracingTableName($table_name)
    {
      $this->tracing_table = $this->database->inspect($table_name);
    }

    public function tracingTable(): TableManipulationInterface
    {
        return $this->tracing_table;
    }

    public function trace(TraceInterface $t): bool
    {
        $trace = [];
        $trace['query_type'] = $t->queryCode();
        $trace['query_table'] = $t->tableName();
        $trace['query_id'] = $t->tablePk();
        $trace['query_by'] = $t->operatorId();

        try {
            $this->tracingTable()->connection()->transact();
            $query = $this->tracingTable()->insert($trace)->run();

            // if we delete a record, we remove all traces of update
            if ($query->isSuccess() && $t->isDelete()) {
                $trace['query_type'] = self::CODE_UPDATE;
                unset($trace['query_by']);
                $this->tracingTable()->delete($trace)->run();
            }
            $this->tracingTable()->connection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->tracingTable()->connection()->rollback();
            return false;
        }
    }


    public function traces($options = []): array
    {
        // TODO SELECT field order can't change without adapting the result parsing code (foreach $res)
        $select_fields = [
          'SUBSTR(query_on, 1, 10) AS working_day',
          'query_table',
          'query_id',
          'GROUP_CONCAT(DISTINCT query_type, "-", query_by) as action_by'
        ];
        $q = $this->tracingTable()->select($select_fields);
        $q->orderBy(['', 'working_day', 'DESC']);
        $q->orderBy([$this->tracingTable()->name(), 'query_table', 'DESC']);
        $q->orderBy([$this->tracingTable()->name(), 'query_id', 'DESC']);

        $q->groupBy(['working_day']);
        $q->groupBy('query_table');
        $q->groupBy('query_id');
        $q->having("action_by NOT LIKE '%D%'");

        $limit = 1000;
        if (!empty($options['limit'])) {
            $limit = intval($options['limit']);
            unset($options['limit']);
        }
        $q->limit($limit);

        $this->filter($q, $options);
        $res = $q->retNum(); // ret num to list()
        return $this->export($res);
    }

    private function filter($q, $options)
    {
        if (isset($options['on'])) {
            $q->whereLike('query_on', $options['on'] . '%');
        }

        if (isset($options['by'])) {
            $q->whereEQ('query_by', $options['operator']);
        }

        if (isset($options['pk'])) {
            $q->whereEQ('query_id', $options['pk']);
        }

        if (isset($options['table'])) {
            $q->whereEQ('query_table', $options['table']);
        }

        if (isset($options['tables'])) {
            $q->whereStringIn('query_table', $options['tables']);
        }
    }

    private function export($res)
    {
        $ret = [];

        foreach ($res as $r) {
            list($working_day, $class, $instance_id, $logs) = $r;

            if (!isset($ret[$working_day])) {
                $ret[$working_day] = [];
            }
            if (!isset($ret[$working_day][$class])) {
                $ret[$working_day][$class] = [];
            }

            $ret[$working_day][$class][$instance_id] = $logs;
        }
        return $ret;
    }
}
