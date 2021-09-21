<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Fixtures\PropertyFixtures;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
use Wikibase\DataModel\Services\Lookup\PropertyLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyAdapterPropertyLookupTest extends TestCase {

	public function testGivenKnownProperty_getPropertyForIdReturnsIt() {
		$property = PropertyFixtures::newProperty();

		$lookup = new LegacyAdapterPropertyLookup( new InMemoryEntityLookup( $property ) );

		$this->assertEquals(
			$property,
			$lookup->getPropertyForId( $property->getId() )
		);
	}

	public function testWhenPropertyIsNotKnown_getPropertyForIdReturnsNull() {
		$lookup = new LegacyAdapterPropertyLookup( new InMemoryEntityLookup() );

		$this->assertNull(
			$lookup->getPropertyForId( new NumericPropertyId( 'P1' ) )
		);
	}

	public function testGetPropertyForIdThrowsCorrectExceptionType() {
		$id = new NumericPropertyId( 'P1' );

		$legacyLookup = new InMemoryEntityLookup();
		$legacyLookup->addException( new EntityLookupException( $id ) );

		$propertyLookup = new LegacyAdapterPropertyLookup( $legacyLookup );

		$this->expectException( PropertyLookupException::class );
		$propertyLookup->getPropertyForId( $id );
	}

}
