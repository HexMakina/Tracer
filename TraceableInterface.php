<?php

namespace HexMakina\Tracer;

interface TraceableInterface
{
    public function traceable(): bool;
    public function traces(): array;
}
