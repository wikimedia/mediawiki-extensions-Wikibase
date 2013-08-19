<?php

namespace Wikibase\Test\Api;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Property;
use Wikibase\PropertyContent;

/**
 * Unit tests for the Wikibase\Repo\Api\MergeItems class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group MergeItemsTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MergeItemsTest extends WikibaseApiTestCase {

	private static $hasSetup;
	private static $fromItem;
	private static $toItem;

	public function setUp() {
		parent::setUp();
		if( !isset( self::$hasSetup ) ){
			//setup a property
			$prop42 = PropertyId::newFromNumber( 47 );
			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop42 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'MergeItemsTest' );

			//setup 2 items
			$fromItem = ItemContent::newEmpty();
			$fromItem->save( 'MergeItemsTest' );
			$toItem = ItemContent::newEmpty();
			$toItem->save( 'MergeItemsTest' );
			self::$fromItem = $fromItem->getEntity()->getId()->getPrefixedId();
			self::$toItem = $toItem->getEntity()->getId()->getPrefixedId();
		}
		self::$hasSetup = true;
	}

	public static function tearDownAfterClass(){
		//clearup
		ItemContent::

	}

	//todo test bad merges and validation / exceptions

	public static function provideData(){
		return array(
			array(
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array(),
				array(),
				array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			),
			array(
				array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
				array(),
				array(),
				array( 'descriptions' => array( 'de' => array( 'language' => 'de', 'value' => 'foo' ) ) ),
			),
			array(
				array( 'aliases' => array( array( "language" => "nl", "value" => "Dickes B" ) ) ),
				array(),
				array(),
				array( 'aliases' => array( array( "language" => "nl", "value" => "Dickes B" ) ) ),
			),
			array(
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array(),
				array(),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
			),
			array(
				array( 'claim' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array(),
				array(),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
			),
			//todo test moving claims and combinations
		);
	}

	/**
	 * @dataProvider provideData
	 */
	function testMergeRequest( $pre1, $pre2, $expected1, $expected2 ){
		// -- prefill the entities --------------------------------------------
		$this->doApiRequestWithToken( array( 'action' => 'wbeditentity', 'id' => EntityTestHelper::getId( 'Empty' ) ,'clear' => '', 'data' => json_encode( $pre1 ) ) );
		$this->doApiRequestWithToken( array( 'action' => 'wbeditentity', 'id' => EntityTestHelper::getId( 'Empty2' ) ,'clear' => '', 'data' => json_encode( $pre2 ) ) );

		// -- do the request --------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( array(
			'action' => 'wbmergeitems',
			'fromid' => EntityTestHelper::getId( 'Empty' ),
			'toid' => EntityTestHelper::getId( 'Empty2' ),
			'summary' => 'CustomSummary!',
		) );

		// -- check the result --------------------------------------------
		$this->assertResultSuccess( $result );
		$this->assertArrayHasKey( 'from', $result );
		$this->assertArrayHasKey( 'to', $result );
		$this->assertArrayHasKey( 'id', $result['from'] );
		$this->assertArrayHasKey( 'id', $result['to'] );
		$this->assertArrayHasKey( 'lastrevid', $result['from'] );
		$this->assertArrayHasKey( 'lastrevid', $result['to'] );
		$this->assertGreaterThan( 0, $result['from']['lastrevid'] );
		$this->assertGreaterThan( 0, $result['to']['lastrevid'] );

		// -- check the items --------------------------------------------
		$this->assertEntityEquals( $expected1, $this->loadEntity( $result['from']['id'] ) );
		$this->assertEntityEquals( $expected2, $this->loadEntity( $result['to']['id'] ) );

		// -- check the edit summaries --------------------------------------------
		$this->assertRevisionSummary( array( 'wbmergeitems' ), $result['from']['lastrevid'] );
		$this->assertRevisionSummary( "/CustomSummary/" , $result['from']['lastrevid'] );
		$this->assertRevisionSummary( array( 'wbmergeitems' ), $result['to']['lastrevid'] );
		$this->assertRevisionSummary( "/CustomSummary/" , $result['to']['lastrevid'] );
	}

}
