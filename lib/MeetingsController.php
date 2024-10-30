<?php

namespace Meetings;

use Psr\Container\ContainerInterface;

class MeetingsController
{
    /**
     * Der Konstruktor.
     *
     * @param ContainerInterface $container der Dependency Container
     */
    public function __construct(protected ContainerInterface $container)
    {
    }
}
