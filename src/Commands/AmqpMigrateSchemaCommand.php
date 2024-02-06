<?php

namespace Medeiroz\AmqpToolkit\Commands;

use Illuminate\Console\Command;
use Medeiroz\AmqpToolkit\SchemaMigration\Runner;

class AmqpMigrateSchemaCommand extends Command
{
    protected $signature = 'amqp:migrate
        {--rollback : Rollback the last schema command}
        {--refresh : Rollback and run all schema commands}
        {--step=1 : Rollback batch steps. Default 1}
    ';

    protected $description = 'Run the amqp schema commands';

    public function handle(Runner $runner): int
    {
        if ($this->option('rollback')) {
            $this->info('Running amqp schema rollback...');
            $runner->rollback($this->option('step') ?: 1);

        } elseif ($this->option('refresh')) {
            $this->info('Running amqp schema refresh...');
            $runner->refresh();

        } else {
            $this->info('Running amqp schema migrate...');
            $runner->migrate();
        }

        $this->info('amqp schema commands finished!');

        return 0;
    }
}
