<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyInfoTable;

/**
 * @covers Wikibase\PropertyInfoTable
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoTableTest extends MediaWikiTestCase {

	/**
	 * @var PropertyInfoStoreTestHelper
	 */
	private $helper;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->helper = new PropertyInfoStoreTestHelper( $this, array( $this, 'newPropertyInfoTable' ) );
	}

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_property_info table." );
		}

		$this->tablesUsed[] = 'wb_property_info';
	}

	public function newPropertyInfoTable() {
		return new PropertyInfoTable( false );
	}

	public function provideSetPropertyInfo() {
		return $this->helper->provideSetPropertyInfo();
	}

	/**
	 * @dataProvider provideSetPropertyInfo
	 */
	public function testSetPropertyInfo( PropertyId $id, array $info, $expectedException ) {
		$this->helper->testSetPropertyInfo( $id, $info, $expectedException );
	}

	public function testGetPropertyInfo() {
		$this->helper->testGetPropertyInfo();
	}

	public function testGetPropertyInfoForDataType() {
		$this->helper->testGetPropertyInfoForDataType();
	}

	public function testGetAllPropertyInfo() {
		$this->helper->testGetAllPropertyInfo();
	}

	public function testRemovePropertyInfo() {
		$this->helper->testRemovePropertyInfo();
	}

	public function testPropertyInfoPersistance() {
		$this->helper->testPropertyInfoPersistance();
	}

}
