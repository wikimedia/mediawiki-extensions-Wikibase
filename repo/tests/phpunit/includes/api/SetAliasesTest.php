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
 * @author Adam Shorland
 */
class SetAliasesTest extends LangAttributeTestCase {

	private static $hasSetup;

	public function setUp() {
		self::$testAction = 'wbsetaliases';
		parent::setUp();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Empty' ) );
		}
		self::$hasSetup = true;
	}

	public static function provideData() {
		return array(
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			array( //0
				'p' => array( 'language' => 'en', 'set' => '' ),
				'e' => array( 'warning' => 'edit-no-change' ) ),
			array( //1
				'p' => array( 'language' => 'en', 'set' => 'Foo' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo' ) ) ) ),
			array( //2
				'p' => array( 'language' => 'en', 'add' => 'Foo|Bax' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bax' ) ) ) ),
			array( //3
				'p' => array( 'language' => 'en', 'set' => 'Foo|Bar|Baz' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz' ) ) ) ),
			array( //4
				'p' => array( 'language' => 'en', 'set' => 'Foo|Bar|Baz' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz' ) ), 'warning' => 'edit-no-change' ) ),
			array( //5
				'p' => array( 'language' => 'en', 'add' => 'Foo|Spam' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz', 'Spam' ) ) ) ),
			array( //6
				'p' => array( 'language' => 'en', 'add' => 'ohi' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz', 'Spam', 'ohi' ) ) ) ),
			array( //7
				'p' => array( 'language' => 'en', 'set' => 'ohi' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ) ) ),
			array( //8
				'p' => array( 'language' => 'de', 'set' => '' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ), 'warning' => 'edit-no-change' ) ),
			array( //9
				'p' => array( 'language' => 'de', 'set' => 'hiya' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ), 'de' => array( 'hiya' ) ) ) ),
			array( //10
				'p' => array( 'language' => 'de', 'add' => '||||||opps||||opps||||' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ), 'de' => array( 'hiya', 'opps' ) ) ) ),
			array( //11
				'p' => array( 'language' => 'de', 'remove' => 'opps|hiya' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ) ) ),
			array( //12
				'p' => array( 'language' => 'en', 'remove' => 'ohi' ),
				'e' => array(  ) ),
		);
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetAliases( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if( !array_key_exists( 'id', $params ) ){
			$params['id'] = EntityTestHelper::getId( 'Empty' );
		}
		if( !array_key_exists( 'value', $expected ) ){
			$expected['value'] = array();
		}

		// -- do the request --------------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );

		if( array_key_exists( $params['language'], $expected['value'] ) ){
			$resAliases = self::flattenArray( $result['entity']['aliases'], 'language', 'value', true );
			$this->assertArrayHasKey( $params['language'], $resAliases );
			$this->assertArrayEquals( $expected['value'][$params['language']], $resAliases[ $params['language'] ] );
		}

		// -- check any warnings ----------------------------------------------
		if( array_key_exists( 'warning', $expected ) ){
			$this->assertArrayHasKey( 'warnings', $result, "Missing 'warnings' section in response." );
			$this->assertEquals( $expected['warning'], $result['warnings']['messages']['0']['name']);
			$this->assertArrayHasKey( 'html', $result['warnings']['messages'] );
		}

		// -- check item in database -------------------------------------------
		$dbEntity = $this->loadEntity( EntityTestHelper::getId( 'Empty' ) );
		if( count( $expected['value'] ) ){
			$this->assertArrayHasKey( 'aliases', $dbEntity );
			$dbAliases = self::flattenArray( $dbEntity['aliases'], 'language', 'value', true );
			foreach( $expected['value'] as $valueLanguage => $value ){
				$this->assertArrayEquals( $value, $dbAliases[ $valueLanguage ] );
			}
		} else {
			$this->assertArrayNotHasKey( 'aliases', $dbEntity );
		}

		// -- check the edit summary --------------------------------------------
		if( !array_key_exists( 'warning', $expected ) || $expected['warning'] != 'edit-no-change' ){
			$this->assertRevisionSummary( array( self::$testAction, $params['language'] ), $result['entity']['lastrevid'] );
			if( array_key_exists( 'summary', $params) ){
				$this->assertRevisionSummary( "/{$params['summary']}/" , $result['entity']['lastrevid'] );
			}
		}
	}

	public static function provideExceptionData() {
		return array(
			// p => params, e => expected

			// -- Test Exceptions -----------------------------
			array( //0
				'p' => array( 'language' => '', 'add' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_language' ) ) ),
			array( //1
				'p' => array( 'language' => 'nl', 'set' => LangAttributeTestHelper::makeOverlyLongString() ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'failed-save' ) ) ),
			array( //2
				'p' => array( 'language' => 'pt', 'remove' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'badtoken', 'message' => 'loss of session data' ) ) ),
			array( //3
				'p' => array( 'id' => 'noANid', 'language' => 'fr', 'add' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-entity-id', 'message' => 'No entity found' ) ) ),
			array( //4
				'p' => array( 'site' => 'qwerty', 'language' => 'pl', 'set' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_site', 'message' => "Unrecognized value for parameter 'site'" ) ) ),
			array( //5
				'p' => array( 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'remove' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-link', 'message' => 'No entity found matching site link' ) ) ),
			array( //6
				'p' => array( 'title' => 'Blub', 'language' => 'en', 'add' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal', 'message' => 'Either provide the item "id" or pairs' ) ) ),
			array( //7
				'p' => array( 'site' => 'enwiki', 'language' => 'en', 'set' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal', 'message' => 'Either provide the item "id" or pairs' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetLabelExceptions( $params, $expected ){
		self::doTestSetLangAttributeExceptions( $params, $expected );
	}
}