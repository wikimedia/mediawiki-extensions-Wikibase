<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyContent;

/**
 * @covers Wikibase\PropertyContent
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTest {

	/**
	 * @return PropertyId
	 */
	protected function getDummyId() {
		return new PropertyId( 'P100' );
	}

	/**
	 * @param EntityId $propertyId
	 *
	 * @return PropertyContent
	 */
	protected function newEmpty( EntityId $propertyId = null ) {
		$empty = PropertyContent::newEmpty();

		if ( $propertyId !== null ) {
			$empty->getProperty()->setId( $propertyId );
		}

		return $empty;
	}

	public function provideGetEntityId() {
		$p11 = new PropertyId( 'P11' );

		return array(
			'property id' => array( $this->newEmpty( $p11 ), $p11 ),
		);
	}

}
