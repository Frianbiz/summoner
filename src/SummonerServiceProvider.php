<?php

namespace Frianbiz\Summoner;

use Illuminate\Support\ServiceProvider;

class SummonerServiceProvider extends ServiceProvider
{
    /**
     * Register summoner commands.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
	        $this->commands([
	            Commands\Summon::class,
	        ]);
	    }
    }
}