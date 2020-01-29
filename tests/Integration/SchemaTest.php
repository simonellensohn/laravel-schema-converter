<?php

namespace Towa\Converter\Test\Integration;

use Carbon\Carbon;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Towa\Converter\Test\TestCase;
use Towa\Converter\Test\Models\Event;
use Towa\Converter\Test\Models\Address;
use Towa\Converter\Test\Models\Location;

class SchemaTest extends TestCase
{
    use ArraySubsetAsserts;

    private $address;
    private $location;
    private $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->address = Address::create([
            'street' => 'address_street',
            'city' => 'address_city',
            'zipcode' => '1111',
            'country' => 'address_country',
        ]);

        $this->location = Location::create([
            'name' => 'location_name',
            'description' => 'location_description',
            'address_id' => $this->address->id,
        ]);

        $this->event = Event::create([
            'name' => 'event_name',
            'description' => 'event_description',
            'location_id' => $this->location->id,
        ]);

        $this->event->dates()->create([
            'start_date' => Carbon::now()->subWeek()->toDateTimeString(),
            'end_date' => Carbon::now()->subDays(6)->toDateTimeString(),
        ]);

        $this->event->dates()->create([
            'start_date' => Carbon::now()->subDays(2)->toDateTimeString(),
            'end_date' => Carbon::now()->subDay()->toDateTimeString(),
        ]);
    }

    /** @test */
    public function it_can_convert_a_simple_model_to_a_schema_object()
    {
        $schema = $this->address->convertToSchema()->toArray();

        $this->assertArraySubset([
            '@context' => 'https://schema.org',
            '@type' => 'PostalAddress',
            'streetAddress' => $this->address->street,
            'addressLocality' => $this->address->city,
            'postalCode' => $this->address->zipcode,
        ], $schema);
    }

    /** @test */
    public function it_can_convert_a_model_with_a_relation()
    {
        $schema = $this->location->convertToSchema()->toArray();

        $this->assertArraySubset([
            '@type' => 'EventVenue',
            'name' => $this->location->name,
            'description' => $this->location->description,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->location->address->street,
            ],
        ], $schema);
    }

    /** @test */
    public function it_can_parse_dates()
    {
        $schema = $this->event->convertToSchema()->toArray();

        $this->assertArraySubset([
             'startDate' => $this->event->dates->map(function ($date) {
                 return $date->start_date->toAtomString();
             })->toArray(),
             'endDate' => $this->event->dates->map(function ($date) {
                 return $date->end_date->toAtomString();
             })->toArray(),
         ], $schema);
    }
}
