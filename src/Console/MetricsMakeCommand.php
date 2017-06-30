<?php namespace GurmanAlexander\Metrics\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MetricsMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new metrics class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Metrics';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('time')) {
            return __DIR__.'/stubs/metrics.time.stub';
        } elseif ($this->option('count')) {
            return __DIR__.'/stubs/metrics.count.stub';
        }

        return __DIR__.'/stubs/metrics.count.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Metrics';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['time', 't', InputOption::VALUE_NONE, 'Generate a Timer metrics class.'],

            ['count', 'c', InputOption::VALUE_NONE, 'Generate a Counter metrics class.'],
        ];
    }
}
