<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for the ApiSetAliases API module.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
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
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group SetAliasesTest
 * @group BreakingTheSlownessBarrier
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetAliasesTest extends WikibaseApiTestCase {

	private static $hasSetup;

	public function setup() {
		parent::setup();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Oslo' ) );
		}
		self::$hasSetup = true;
	}

	public function paramProvider() {
		return array(
			// lang code, list name, list values, expected result
			array( 'Oslo', 'en', 'set', 'Foo', 'Foo' ),
			array( 'Oslo', 'en', 'add', 'Foo|bax', 'Foo|bax' ),
			array( 'Oslo', 'en', 'set', 'Foo|bar|baz', 'Foo|bar|baz' ),
			array( 'Oslo', 'en', 'add', 'Foo|spam', 'Foo|bar|baz|spam' ),
			array( 'Oslo', 'en', 'add', 'ohi', 'Foo|bar|baz|spam|ohi' ),

			array( 'Oslo', 'de', 'set', '', '' ),
			array( 'Oslo', 'de', 'add', 'ohi', 'ohi' ),

			array( 'Oslo', 'en', 'remove', 'ohi', 'Foo|bar|baz|spam' ),
			array( 'Oslo', 'en', 'remove', 'ohi', 'Foo|bar|baz|spam' ),
			array( 'Oslo', 'en', 'remove', 'Foo|bar|baz|o_O', 'spam' ),
			array( 'Oslo', 'en', 'add', 'o_O', 'spam|o_O' ),
			array( 'Oslo', 'en', 'set', 'o_O', 'o_O' ),
			array( 'Oslo', 'en', 'remove', 'o_O', '' ),
		);
	}

	/**
	 * @dataProvider paramProvider
	 */
	public function testSetAliases( $handle, $langCode, $op, $value, $expected ) {
		$id = EntityTestHelper::getId( $handle );
		$expected = $expected === '' ? array() : explode( '|', $expected );

		// update the item ----------------------------------------------------------------
		$req = array(
			'id' => $id,
			'action' => 'wbsetaliases',
			'language' => $langCode,
			$op => $value
		);

		list( $apiResponse,, ) = $this->doApiRequestWithToken( $req, null, self::$users['wbeditor']->user );

		$this->assertResultSuccess( $apiResponse );

		// check return value --------------------------------------------------
		if ( $expected ) {
			$this->assertResultSuccess( $apiResponse );
			$this->assertResultHasKeyInPath( $apiResponse, 'entity', 'aliases' );

			$aliases = self::flattenArray( $apiResponse['entity']['aliases'], 'language', 'value', true );
			$actual = isset( $aliases[ $langCode ] ) ? $aliases[ $langCode ] : array();

			$this->assertArrayEquals( $expected, $actual );
		} else {
			$this->assertFalse( !empty( $apiResponse['entity']['aliases'] ), "found aliases when there should be none" );
		}

		// check item in database --------------------------------------------------
		$item = $this->loadEntity( $id );

		$aliases = self::flattenArray( $item['aliases'], 'language', 'value', true );
		$actual = isset( $aliases[ $langCode ] ) ? $aliases[ $langCode ] : array();

		$this->assertArrayEquals( $expected, $actual );

		//TODO: we should have such checks for all API modules!
		$this->assertRevisionSummary(
			array_merge( array( "wbsetaliases-$op", $langCode ), explode( '|', $value ) ),
			$apiResponse['entity']['lastrevid'] );
	}

	public function testSetAliases_length( ) {
		$handle = 'Oslo';
		$id = EntityTestHelper::getId( $handle );
		$langCode = 'en';
		$op = 'add';
		$value = LangAttributeBase::makeOverlyLongString();
		$exception = 'UsageException';

		// update the item ----------------------------------------------------------------
		$req = array(
			'id' => $id,
			'action' => 'wbsetaliases',
			'language' => $langCode,
			$op => $value
		);

		try {
			list( $apiResponse,, ) = $this->doApiRequestWithToken( $req, null, self::$users['wbeditor']->user );
		} catch ( \Exception $e ) {
			if ( $exception !== null && ! $e instanceof \PHPUnit_Framework_AssertionFailedError ) {
				$this->assertTrue( is_a( $e, $exception ), "Not the expected exception" );
				return;
			}
			else {
				throw $e;
			}
		}
	}

	public function testSetAliases_invalidId() {
		$badId = 'xyz123+++';

		$req = array(
			'id' => $badId,
			'action' => 'wbsetaliases',
			'language' => 'en',
			'set' => 'foo'
		);

		$this->setExpectedException( 'UsageException' );
		$this->doApiRequestWithToken( $req, null, self::$users['wbeditor']->user );
	}
}