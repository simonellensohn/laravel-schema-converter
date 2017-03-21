<?php

namespace Towa\Converter\Test\Integration;

use Carbon\Carbon;
use Towa\Converter\Test\TestCase;
use Towa\Converter\Test\Models\Event;
use Towa\Converter\Test\Models\Address;
use Towa\Converter\Test\Models\Location;

class SchemaTest extends TestCase
{
    private $address;
    private $location;
    private $event;

    public function setUp()
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
            'start_date' => Carbon::now()->subWeek()->toDateTimeString(),
            'end_date' => Carbon::now()->subDays(6)->toDateTimeString(),
            'location_id' => $this->location->id,
        ]);
    }

    /** @test */
    public function it_can_convert_a_simple_model_to_a_schema_object()
    {
        $schema = $this->address->convertToSchema()->toArray();

        $this->assertArraySubset([
            '@context' => 'http://schema.org',
            '@type' => 'PostalAddress',
            'streetAddress' => 'address_street',
            'addressLocality' => 'address_city',
            'postalCode' => '1111',
        ], $schema);
    }

    /** @test */
    public function it_can_determine_the_schema_type_of_a_relation() {
        $schema = $this->location->convertToSchema()->toArray();

        $this->assertArraySubset([
            '@type' => 'EventVenue',
            'name' => 'location_name',
            'description' => 'location_description',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'address_street',
            ]
        ], $schema);
    }

    /** @test */
    public function it_can_parse_carbon_fields() {
         $schema = $this->event->convertToSchema()->toArray();

         $this->assertArraySubset([
             'startDate' => $this->event->start_date->toAtomString(),
             'endDate' => $this->event->end_date->toAtomString(),
         ], $schema);
    }
}
