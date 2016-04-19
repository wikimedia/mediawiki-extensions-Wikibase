<?php

namespace Wikibase\Client\Tests\DataAccess;

use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Test\MockPropertyLabelResolver;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Client\DataAccess\PropertyIdResolver
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyIdResolverTest extends \PHPUnit_Framework_TestCase {

	private function getPropertyIdResolver() {
		$mockRepository = $this->getMockRepository();
		$propertyLabelResolver = new MockPropertyLabelResolver( 'en', $mockRepository );

		return new PropertyIdResolver( $mockRepository, $propertyLabelResolver );
	}

	private function getMockRepository() {
		$propertyId = new PropertyId( 'P1337' );

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setLabel( 'en', 'a kitten!' );

		$mockRepository = new MockRepository();
		$mockRepository->putEntity( $property );

		return $mockRepository;
	}

	/**
	 * @dataProvider resolvePropertyIdProvider
	 */
	public function testResolvePropertyId( PropertyId $expected, $propertyLabelOrId ) {
		$propertyIdResolver = $this->getPropertyIdResolver();

		$propertyId = $propertyIdResolver->resolvePropertyId( $propertyLabelOrId, 'en' );
		$this->assertEquals( $expected, $propertyId );
	}

	public function resolvePropertyIdProvider() {
		return array(
			array( new PropertyId( 'P1337' ), 'a kitten!' ),
			array( new PropertyId( 'P1337' ), 'p1337' ),
			array( new PropertyId( 'P1337' ), 'P1337' ),
		);
	}

	/**
	 * @dataProvider resolvePropertyIdWithInvalidInput_throwsExceptionProvider
	 */
	public function testResolvePropertyIdWithInvalidInput_throwsException( $propertyIdOrLabel ) {
		$propertyIdResolver = $this->getPropertyIdResolver();

		$this->setExpectedException( PropertyLabelNotResolvedException::class );

		$propertyIdResolver->resolvePropertyId( $propertyIdOrLabel, 'en' );
	}

	public function resolvePropertyIdWithInvalidInput_throwsExceptionProvider() {
		return array(
			array( 'hedgehog' ),
			array( 'Q100' ),
			array( 'P1444' )
		);
	}

}
