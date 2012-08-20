<?php

namespace Wikibase\Test;
use DataValue\DataValueObject as DataValueObject;
use Wikibase\PropertyValueSnak as PropertyValueSnak;
use Wikibase\ReferenceObject as ReferenceObject;

/**
 * Tests for the Wikibase\ReferenceObject class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseReference
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceObjectTest extends \MediaWikiTestCase {

	public function snakListProvider() {
		// TODO
		$snakLists = array();

		$snakLists[] = array(
			new PropertyValueSnak( 1, new DataValueObject() )
		);

		$snakLists[] = array(
			new PropertyValueSnak( 1, new DataValueObject() ),
			new PropertyValueSnak( 2, new DataValueObject() ),
			new PropertyValueSnak( 3, new DataValueObject() ),
		);

		return $snakLists;
	}

	/**
	 * @dataProvider snakListProvider
	 */
	public function testConstructor( array $snaks ) {
		$omnomnomReference = new ReferenceObject(  );

		// TODO
	}

}
