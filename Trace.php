<?php

namespace HexMakina\Tracer;

class Trace
{
    private $query_type;
    private $query_table;
    private $query_id;
    private $query_by;

    public function isUpdate($setter=null)
    {
      if(is_bool($setter))
        $this->query_type = TracerInterface::CODE_UPDATE;

      return $this->query_type === TracerInterface::CODE_UPDATE;
    }

    public function isDelete($setter=null)
    {
      if(is_bool($setter))
        $this->query_type = TracerInterface::CODE_DELETE;

      return $this->query_type === TracerInterface::CODE_DELETE;
    }

    public function isInsert($setter=null)
    {
      if(is_bool($setter))
        $this->query_type = TracerInterface::CODE_CREATE;

      return $this->query_type === TracerInterface::CODE_CREATE;
    }

    public function isSelect($setter=null)
    {
      if(is_bool($setter))
        $this->query_type = TracerInterface::CODE_SELECT;

      return $this->query_type === TracerInterface::CODE_SELECT;
    }


    public function queryCode()
    {
      return $this->query_type;
    }

    public function tableName($setter=null)
    {
      if(!is_null($setter))
        $this->query_table = $setter;
      return $this->query_table;
    }

    public function tablePk($setter=null)
    {
      if(!is_null($setter))
        $this->query_id = $setter;
      return $this->query_id;
    }

    public function operatorId($setter=null)
    {
      if(!is_null($setter))
        $this->query_by = $setter;
      return $this->query_by;
    }
}
