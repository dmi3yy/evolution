<?php namespace EvolutionCMS\Salo\Console;

use Illuminate\Console\Command;

class UpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salo:up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Evo Salo Docker Compose services';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        passthru('./core/vendor/bin/salo up -d');

        $this->info('Salo scaffolding run successfully.');
        return Command::SUCCESS;
    }
}
