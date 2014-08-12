<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

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
	 * @see EntityContentTest::getContentClass
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @return EntityId
	 */
	protected function getDummyId() {
		return new PropertyId( 'P100' );
	}

	/**
	 * @see EntityContentTest::newEmpty
	 */
	protected function newEmpty( EntityId $id = null ) {
		$content = parent::newEmpty( $id );
		$content->getProperty()->setDataTypeId( 'string' );

		return $content;
	}

	public function provideGetEntityId() {
		$p11 = new PropertyId( 'P11' );

		return array(
			'property id' => array( $this->newEmpty( $p11 ), $p11 ),
		);
	}

}
