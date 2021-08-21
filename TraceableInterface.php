<?php

namespace HexMakina\Tracer;

interface TraceableInterface
{
    public function traces(): array;
    public function trace();

}
