<?php

namespace HexMakina\Tracer;

class Trace implements \HexMakina\BlackBox\Database\TraceInterface
{
    private string $query_type;
    private string $query_table;
    private string $query_id;
    private string $query_by;

    public function isUpdate(bool $setter = null) : bool
    {
        if (is_bool($setter)) {
            $this->query_type = self::CODE_UPDATE;
        }

        return $this->query_type === self::CODE_UPDATE;
    }

    public function isDelete(bool $setter = null) : bool
    {
        if (is_bool($setter)) {
            $this->query_type = self::CODE_DELETE;
        }

        return $this->query_type === self::CODE_DELETE;
    }

    public function isInsert(bool $setter = null) : bool
    {
        if (is_bool($setter)) {
            $this->query_type = self::CODE_CREATE;
        }

        return $this->query_type === self::CODE_CREATE;
    }

    public function isSelect(bool $setter = null) : bool
    {
        if (is_bool($setter)) {
            $this->query_type = self::CODE_SELECT;
        }

        return $this->query_type === self::CODE_SELECT;
    }


    public function queryCode() : string
    {
        return $this->query_type;
    }

    public function tableName(string $setter = null) : string
    {
        if ($setter !== null) {
            $this->query_table = $setter;
        }
        return $this->query_table;
    }

    public function tablePk(string $setter = null) : string
    {
        if ($setter !== null) {
            $this->query_id = $setter;
        }
        return $this->query_id;
    }

    public function operatorId(string $setter = null) : string
    {
        if ($setter !== null) {
            $this->query_by = $setter;
        }
        return $this->query_by;
    }
}
