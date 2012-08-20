<?php

namespace Wikibase\Test;
use Wikibase\SnakList as SnakList;
use Wikibase\Snaks as Snaks;
use Wikibase\Snak as Snak;
use \Wikibase\PropertyValueSnak as PropertyValueSnak;
use \Wikibase\InstanceOfSnak as InstanceOfSnak;
use \DataValue\DataValueObject as DataValueObject;

/**
 * Tests for the Wikibase\SnakList class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakListTest extends HashArrayTest {

	/**
	 * @see GenericArrayObjectTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return '\Wikibase\SnakList';
	}

	/**
	 * @see GenericArrayObjectTest::elementInstancesProvider
	 */
	public function elementInstancesProvider() {
		return array(
			new InstanceOfSnak( 42 ),
			new InstanceOfSnak( 9001 ),
			new PropertyValueSnak( 42, new DataValueObject() ),
		);
	}

	public function constructorProvider() {
		return array(
			array(),
			array( array() ),
			array( array(
				new InstanceOfSnak( 42 )
			) ),
			array( array(
				new InstanceOfSnak( 42 ),
				new InstanceOfSnak( 9001 ),
			) ),
			array( array(
				new InstanceOfSnak( 42 ),
				new InstanceOfSnak( 9001 ),
				new PropertyValueSnak( 42, new DataValueObject() ),
			) ),
		);
	}

}
