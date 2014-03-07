<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;

/**
 * @covers Wikibase\EntityRetrievingTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityRetrievingTermLookupTest extends EntityTermLookupTest {

	protected function getEntityTermLookup() {
		$entityLookup = new MockRepository();

		$property = Property::newFromType( 'string' );
		$property->setId( new PropertyId( 'P1' ) );
		$property->setLabels( array(
			'de' => 'de-label',
			'en' => 'en-label',
		) );
		$property->setDescriptions( array(
			'de' => 'de-description',
			'en' => 'en-description',
		) );
		$entityLookup->putEntity( $property );

		return new EntityRetrievingTermLookup( $entityLookup );
	}

}
