<?php

namespace Wikibase\Test;
use Wikibase\ReferenceList as ReferenceList;
use Wikibase\References as References;
use Wikibase\Reference as Reference;
use Wikibase\ReferenceObject as ReferenceObject;

/**
 * Tests for the Wikibase\ReferenceList class.
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
class ReferenceListTest extends HashArrayTest {

	/**
	 * @see GenericArrayObjectTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return '\Wikibase\ReferenceList';
	}

	/**
	 * @see GenericArrayObjectTest::elementInstancesProvider
	 */
	public function elementInstancesProvider() {
		return array(
			new ReferenceObject(),
		);
	}

	public function constructorProvider() {
		return array(
			array(),
			array( new \Wikibase\ReferenceObject() ),
		);
	}

}
