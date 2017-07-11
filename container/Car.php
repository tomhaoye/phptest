<?php

class Car
{
    protected $power;

    public function __construct(PowerInterface $power)
    {
        $this->power = $power;
    }
}
