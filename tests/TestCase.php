<?php

namespace Towa\Converter\Test;

use File;
use Illuminate\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);
    }

    /**
     * @param  $app
     */
    protected function setUpDatabase(Application $app)
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);

        $schemaBuilder = $app['db']->connection()->getSchemaBuilder();

        $schemaBuilder->create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        $schemaBuilder->create('locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->longText('description')->nullable();
            $table->unsignedInteger('address_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('address_id')
                ->references('id')
                ->on('addresses');
        });

        $schemaBuilder->create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations');
        });

        $schemaBuilder->create('event_dates', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('event_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('event_id')
                ->references('id')
                ->on('events');
        });
    }

    protected function initializeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }

        File::makeDirectory($directory);
    }

    public function getTempDirectory() : string
    {
        return __DIR__.'/temp';
    }
}
