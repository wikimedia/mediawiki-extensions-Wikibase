<?php

namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;

/**
 * @covers Wikibase\Test\MockPropertyInfoStore
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockPropertyInfoStoreTest extends \MediaWikiTestCase {

	/**
	 * @var PropertyInfoStoreTestHelper
	 */
	public $helper;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->helper = new PropertyInfoStoreTestHelper( $this, array( $this, 'newMockPropertyInfoStore' ) );
	}

	public function newMockPropertyInfoStore() {
		return new MockPropertyInfoStore();
	}

	public function provideSetPropertyInfo() {
		return $this->helper->provideSetPropertyInfo();
	}

	/**
	 * @dataProvider provideSetPropertyInfo
	 */
	public function testSetPropertyInfo( EntityId $id, array $info, $expectedException ) {
		$this->helper->testSetPropertyInfo( $id, $info, $expectedException );
	}

	public function testGetPropertyInfo() {
		$this->helper->testGetPropertyInfo();
	}

	public function testGetAllPropertyInfo() {
		$this->helper->testGetAllPropertyInfo();
	}

	public function testRemovePropertyInfo() {
		$this->helper->testRemovePropertyInfo();
	}

}
