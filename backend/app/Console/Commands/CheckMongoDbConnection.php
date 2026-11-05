<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Exception\Exception as MongoDriverException;

class CheckMongoDbConnection extends Command
{
    protected $signature = 'mongodb:check';

    protected $description = 'Check the configured MongoDB connection without displaying credentials';

    public function handle(): int
    {
        if (! extension_loaded('mongodb')) {
            $this->error('The MongoDB PHP extension is not loaded by this PHP runtime.');

            return self::FAILURE;
        }

        $dsn = config('database.connections.mongodb.dsn');

        if (! is_string($dsn) || trim($dsn) === '' || str_contains($dsn, '<db_password>')) {
            $this->error('Set DB_URI in backend/.env to your complete Atlas URI before checking the connection.');

            return self::FAILURE;
        }

        try {
            DB::connection('mongodb')
                ->getMongoClient()
                ->selectDatabase(config('database.connections.mongodb.database'))
                ->command(['ping' => 1]);
        } catch (MongoDriverException|\InvalidArgumentException $exception) {
            $this->error('MongoDB ping failed ('.$exception::class.'). Check DB_URI, Atlas network access, and database-user permissions.');

            return self::FAILURE;
        }

        $this->info('MongoDB ping succeeded for the configured database.');

        return self::SUCCESS;
    }
}
