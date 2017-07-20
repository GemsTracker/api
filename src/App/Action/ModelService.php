<?php


namespace App\Action;


class ModelService
{
    protected $loader;

    public function __construct(ContainerInterface $container)
    {
        $this->loader = $container;
    }

    public function loadModel($class)
    {
        $this->model = false;

        $model = $this->loader->create($class);

        if ($model) {
            $this->model = $model;
        }
    }


}