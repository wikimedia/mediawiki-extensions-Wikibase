<?php

namespace Wikibase\Test;

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
	 * @see EntityContentTest::getContentClass
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @see EntityContentTest::newEmpty
	 */
	protected function newEmpty() {
		$content = PropertyContent::newEmpty();
		$content->getProperty()->setDataTypeId( 'string' );

		return $content;
	}

	/**
	 * Injects a property data type into the generic entity data array.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function prepareEntityData( array $data ) {

		if ( !isset( $data['datatype'] ) ) {
			$data['datatype'] = 'string';
		}

		return $data;
	}

	public function provideGetEntityId() {
		$p11 = new PropertyId( 'P11' );

		return array(
			'no id' => array( $this->newEmpty(), null ),
			'with id' => array( $this->newFromArray( array( 'entity' => 'P11' ) ), $p11 ),
		);
	}

}
