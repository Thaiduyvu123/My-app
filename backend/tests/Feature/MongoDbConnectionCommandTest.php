<?php

namespace Tests\Feature;

use Tests\TestCase;

class MongoDbConnectionCommandTest extends TestCase
{
    public function test_connection_check_fails_clearly_when_uri_is_not_configured(): void
    {
        config(['database.connections.mongodb.dsn' => '']);

        $this->artisan('mongodb:check')
            ->expectsOutput('Set DB_URI in backend/.env to your complete Atlas URI before checking the connection.')
            ->assertFailed();
    }

    public function test_connection_check_does_not_attempt_a_uri_with_a_password_placeholder(): void
    {
        config([
            'database.connections.mongodb.dsn' => 'mongodb+srv://user:<db_password>@cluster.example.mongodb.net/',
        ]);

        $this->artisan('mongodb:check')
            ->expectsOutput('Set DB_URI in backend/.env to your complete Atlas URI before checking the connection.')
            ->assertFailed();
    }
}
