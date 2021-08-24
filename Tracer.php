<?php

/*
 * Tracer
 *
 */

namespace HexMakina\Tracer;

use HexMakina\Crudites\Interfaces\TableManipulationInterface;
use HexMakina\Crudites\Interfaces\QueryInterface;

class Tracer implements TracerInterface
{
    private static $query_codes = [
    'insert' => self::CODE_CREATE,
    'select' => self::CODE_SELECT,
    'update' => self::CODE_UPDATE,
    'delete' => self::CODE_DELETE
    ];

    private $tracing_table = null;

    public function __construct(TableManipulationInterface $tracing_table)
    {
        $this->tracing_table = $tracing_table;
    }

    public function tracing_table(): TableManipulationInterface
    {
        return $this->tracing_table;
    }

    public function query_code($sql_statement): string
    {
        $first_five = strtolower(substr($sql_statement, 0, 6));

        if (!isset(self::$query_codes[$first_five])) {
            throw new \InvalidArgumentException('KADRO_ONLY_TRACES_CRUD');
        }

        return self::$query_codes[$first_five];
    }

    public function trace(QueryInterface $q, $operator_id, $model_id): bool
    {
        $trace = [];
        $trace['query_type'] = $this->query_code($q->statement());
        $trace['query_table'] = $q->table_name();
        $trace['query_id'] = $model_id;
        $trace['query_by'] = $operator_id;

        try {
            $this->tracing_table()->connection()->transact();
            $query = $this->tracing_table()->insert($trace)->run();

            // if we delete a record, we remove all traces of update
            if ($query->is_success() && $trace['query_type'] === self::CODE_DELETE) {
                $trace['query_type'] = self::CODE_UPDATE;
                unset($trace['query_by']);
                $this->tracing_table()->delete($trace)->run();
            }
            $this->tracing_table()->connection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->tracing_table()->connection()->rollback();
            return false;
        }
    }

    // -- CRUD Tracking:get for one model
    // DEPRECATED, now traces(), Traceable Trait, TightORM
    // public function history_by_model(ModelInterface $m)
    // {
    //     $q = $this->tracing_table()->select();
    //     $q->aw_fields_eq(['query_table' => get_class($m)::table_name(), 'query_id' => $m->get_id()]);
    //     $q->order_by(['query_on', 'DESC']);
    //     $q->run();
    //     $res = $q->ret_ass();
    //
    //     return $res;
    // }

    // -- CRUD Tracking:get for many models

    // DEPRECATED, now traces_by_model(), Traceable Trait, TightORM
    // public function traces_by_model(ModelInterface $m)
    // {
    //     return $this->traces(['id' => $m->get_id(), 'table' => get_class($m)::table_name()]);
    // }

    public function traces($options = []) : array
    {
        // TODO SELECT field order can't change without adapting the result parsing code (foreach $res)
        $select_fields = ['SUBSTR(query_on, 1, 10) AS working_day', 'query_table', 'query_id',  'GROUP_CONCAT(DISTINCT query_type, "-", query_by) as action_by'];
        $q = $this->tracing_table()->select($select_fields);
        $q->order_by(['', 'working_day', 'DESC']);
        $q->order_by([$this->tracing_table()->name(), 'query_table', 'DESC']);
        $q->order_by([$this->tracing_table()->name(), 'query_id', 'DESC']);

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

        $this->filter_by_options($q, $options);
        $res = $q->ret_num(); // ret num to list()
        return $this->organise_traces($res);
    }

    private function filter_by_options($q, $options)
    {
      if(isset($options['on']))
        $q->aw_like('query_on', $options['on'].'%');

      if(isset($options['by']))
        $q->aw_eq('query_by', $options['operator']);

      if(isset($options['pk']))
        $q->aw_eq('query_id', $options['pk']);

      if(isset($options['table']))
        $q->aw_eq('query_table', $options['table']);

      if(isset($options['tables']))
        $q->aw_string_in('query_table', $options['tables']);


        //
        // foreach ($options as $o => $v) {
        //     if (preg_match('/id/', $o)) {
        //         $q->aw_eq('query_id', $v);
        //     } elseif (preg_match('/tables/', $o)) {
        //         $q->aw_string_in('query_table', is_array($v) ? $v : [$v]);
        //     } elseif (preg_match('/table/', $o)) {
        //         $q->aw_eq('query_table', $v);
        //     } elseif (preg_match('/(type|action)/', $o)) {
        //         $q->aw_string_in('query_type', is_array($v) ? $v : [$v]);
        //     } elseif (preg_match('/(date|query_on)/', $o)) {
        //         $q->aw_like('query_on', "$v%");
        //     } elseif (preg_match('/(oper|user|query_by)/', $o)) {
        //         $q->aw_eq('query_by', $v);
        //     }
        // }
    }
    private function organise_traces($res)
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
