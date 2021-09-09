<?php

/*
 * Tracer
 *
 */

namespace HexMakina\Tracer;

use \HexMakina\BlackBox\Database\TableManipulationInterface;
use \HexMakina\BlackBox\Database\QueryInterface;
use \HexMakina\BlackBox\Database\TraceInterface;
use \HexMakina\BlackBox\Database\TracerInterface;

class Tracer implements TracerInterface
{
    private $tracing_table = null;

    public function __construct(TableManipulationInterface $tracing_table)
    {
        $this->tracing_table = $tracing_table;
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
        $q->order_by(['', 'working_day', 'DESC']);
        $q->order_by([$this->tracingTable()->name(), 'query_table', 'DESC']);
        $q->order_by([$this->tracingTable()->name(), 'query_id', 'DESC']);

        $q->group_by(['working_day']);
        $q->group_by('query_table');
        $q->group_by('query_id');
        $q->having("action_by NOT LIKE '%D%'");

        $limit = 1000;
        if (!empty($options['limit'])) {
            $limit = intval($options['limit']);
            unset($options['limit']);
        }
        $q->limit($limit);

        $this->filter($q, $options);
        $res = $q->ret_num(); // ret num to list()
        return $this->export($res);
    }

    private function filter($q, $options)
    {
        if (isset($options['on'])) {
            $q->aw_like('query_on', $options['on'] . '%');
        }

        if (isset($options['by'])) {
            $q->aw_eq('query_by', $options['operator']);
        }

        if (isset($options['pk'])) {
            $q->aw_eq('query_id', $options['pk']);
        }

        if (isset($options['table'])) {
            $q->aw_eq('query_table', $options['table']);
        }

        if (isset($options['tables'])) {
            $q->aw_string_in('query_table', $options['tables']);
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
