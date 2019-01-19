<?php

namespace MVF\Servicer;

interface ActionInterface
{
    /**
     * Executes the action.
     *
     * @param \stdClass $headers Headers of the action
     * @param \stdClass $body    Body of the action
     */
    public function handle(\stdClass $headers, \stdClass $body): void;
}