<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class EntityRetrievingDataTypeLookupIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * With data type specific value deserialization (see Wikibase ADR 24), we introduced a possible infinite loop when
	 * deserializing newly created Properties, that include statements using itself:
	 *  -> EntityRetrievingDataTypeLookup calls EntityLookup to request the Property of which it wants the data type
	 *  -> the Property is read from the database in JSON and being deserialized
	 *  -> SnakDeserializer requires a PropertyDataTypeLookup for statement values of type 'wikibase-entityid'
	 *  -> ...
	 *  -> EntityRetrievingDataTypeLookup is called for the same Property
	 *  -> ∞
	 *
	 * This can happen before a deferred update has been written to the secondary 'PropertyInfo' database table.
	 * Therefore, EntityRetrievingDataTypeLookup includes a loop detection, which is tested here by calling
	 * EntityRetrievingDataTypeLookup::getDataTypeIdForProperty() for a new Property, that is also used in a statement
	 * on itself. Without loop detection, this test would run into an infinite loop and fail eventually.
	 */
	public function testLoopDetection() {
		$item = new Item();
		$property = Property::newFromType( 'wikibase-item' );

		$services = $this->getServiceContainer();
		$store = WikibaseRepo::getEntityStore( $services );

		$store->saveEntity( $item, 'test case item', $this->getTestUser()->getUser(), EDIT_NEW );
		$store->saveEntity( $property, 'test case property', $this->getTestUser()->getUser(), EDIT_NEW );
		$propertyId = $property->getId();

		$property->getStatements()->addStatement(
			NewStatement::forProperty( $propertyId )
				->withGuid( ( new GuidGenerator() )->newGuid( $propertyId ) )
				->withValue( $item->getId() )
				->build()
		);
		$store->saveEntity( $property, 'added statement', $this->getTestUser()->getUser() );

		$this->setService( 'WikibaseRepo.PropertyInfoLookup', $this->createStub( PropertyInfoLookup::class ) );
		$lookup = new EntityRetrievingDataTypeLookup( WikibaseRepo::getEntityLookup( $services ) );

		$this->assertSame( 'wikibase-item', $lookup->getDataTypeIdForProperty( $propertyId ) );
	}
}
