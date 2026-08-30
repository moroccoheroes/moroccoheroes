<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:search-hero')]
#[Description('Command description')]
class SearchHero extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
