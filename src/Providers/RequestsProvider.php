<?php
/**
 * Prepare the package.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019
 * @license MIT
 */

namespace Laramore\Providers;

use Illuminate\Support\ServiceProvider;
use Laramore\Commands\ModelRequestMakeCommand;

class RequestsProvider extends ServiceProvider
{
    /**
     * During booting, load our migration views, Migration singletons.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModelRequestMakeCommand::class,
            ]);
        }
    }
}
