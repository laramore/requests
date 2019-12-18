<?php
/**
 * Generate a model request.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019
 * @license MIT
 */

namespace Laramore\Commands;

use Laramore\Facades\Metas;

class ModelRequestMakeCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model-request';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:model-request {--force} {--all} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model request class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';

    /**
     * The name on which we are working.
     *
     * @var string
     */
    protected $inputName;

    /**
     * Execute the console command.
     *
     * @return boolean|null
     */
    public function handle()
    {
        if ($this->option('all')) {
            $baseName = trim($this->argument('name'));

            foreach (Metas::all() as $meta) {
                $this->inputName = $meta->getModelClassName().$baseName;

                $this->handleOne();
            }
        } else {
            return $this->handleOne();
        }
    }

    /**
     * Execute the console command for one file.
     *
     * @return boolean|null
     */
    public function handleOne()
    {
        return parent::handle();
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return ($this->inputName ?? trim($this->argument('name')));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../../resources/stubs/model-request.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  mixed $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Requests';
    }
}
