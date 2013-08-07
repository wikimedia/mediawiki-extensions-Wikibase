<?php

namespace Wikibase\Test\Api;

/**
 * Test case for language attributes API modules.
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
 * @group LanguageAttributeTest
 * @group BreakingTheSlownessBarrier
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class LangAttributeTestCase extends WikibaseApiTestCase {

	protected static $testAction;
	protected static $testId;

	public function setUp() {
		parent::setUp();
		if( !isset( self::$testId ) ){
			self::$testId = $this->getTestEntityId();
		}
	}

	public static function provideData() {
		return array(
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			array( //0
				'p' => array( 'language' => 'en', 'value' => '' ),
				'e' => array( 'warning' => 'edit-no-change' ) ),
			array( //1
				'p' => array( 'language' => 'en', 'value' => 'Value' ),
				'e' => array( 'value' => array( 'en' => 'Value' ) ) ),
			array( //2
				'p' => array( 'language' => 'en', 'value' => 'Value' ),
				'e' => array( 'value' => array( 'en' => 'Value' ), 'warning' => 'edit-no-change' ) ),
			array( //3
				'p' => array( 'language' => 'en', 'value' => 'Another Value', 'summary' => 'Test summary!' ),
				'e' => array( 'value' => array( 'en' => 'Another Value' ) ) ),
			array( //4
				'p' => array( 'language' => 'en', 'value' => 'Different Value' ),
				'e' => array( 'value' => array( 'en' => 'Different Value' ) ) ),
			array( //5
				'p' => array( 'language' => 'bat-smg', 'value' => 'V?sata' ),
				'e' => array( 'value' => array( 'bat-smg' => 'V?sata','en' => 'Different Value' ) ) ),
			array( //6
				'p' => array( 'language' => 'en', 'value' => '' ),
				'e' => array( 'value' => array( 'bat-smg' => 'V?sata' ) ) ),
			array( //7
				'p' => array( 'language' => 'bat-smg', 'value' => '' ),
				'e' => array( ) ),
		);
	}
	
	public function doTestSetLangAttribute( $attribute ,$params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if( !array_key_exists( 'id', $params ) ){
			$params['id'] = self::$testId;
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

		// -- check the result only has our changed data (if any)  ------------
		if( $attribute !== 'aliases' ){
			$this->assertEquals( 1, count( $result['entity'][$attribute] ), "Entity return contained more than a single language" );
			$this->assertArrayHasKey( $params['language'], $result['entity'][$attribute], "Entity doesn't return expected language");
			$this->assertEquals( $params['language'], $result['entity'][$attribute][ $params['language'] ]['language'], "Returned incorrect language" );
		}

		if( array_key_exists( $params['language'], $expected['value'] ) ){
			if( is_array( $expected['value'][ $params['language'] ] ) ){
				//todo check alias array is equal here
			} else {
				$this->assertEquals( $expected['value'][ $params['language'] ], $result['entity'][$attribute][$params['language']]['value'] , "Returned incorrect label" );
			}
		} else if( empty( $value ) ){
			if( $attribute !== 'aliases' ){
				$this->assertArrayHasKey( 'removed', $result['entity'][$attribute][ $params['language'] ], "Entity doesn't return expected 'removed' marker");
			}
		}

		// -- check any warnings ----------------------------------------------
		if( array_key_exists( 'warning', $expected ) ){
			$this->assertArrayHasKey( 'warnings', $result, "Missing 'warnings' section in response." );
			$this->assertEquals( $expected['warning'], $result['warnings']['messages']['0']['name']);
			$this->assertArrayHasKey( 'html', $result['warnings']['messages'] );
		}

		// -- check item in database -------------------------------------------
		$dbEntity = $this->loadEntity( self::$testId );
		if( count( $expected['value'] ) ){
			$this->assertArrayHasKey( $attribute, $dbEntity );
			$dbLabels = self::flattenArray( $dbEntity[$attribute], 'language', 'value', true );
			foreach( $expected['value'] as $valueLanguage => $value ){
				if( is_array( $value ) ){
					//todo check aliases are the same here
				} else {
					$this->assertArrayHasKey( $valueLanguage, $dbLabels );
					$this->assertEquals( $value, $dbLabels[$valueLanguage][0] );
				}
			}
		} else {
			$this->assertArrayNotHasKey( $attribute, $dbEntity );
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
				'p' => array( 'language' => '', 'value' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_language' ) ) ),
			array( //1
				'p' => array( 'language' => 'nl', 'value' => LangAttributeTestHelper::makeOverlyLongString() ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'failed-save' ) ) ),
			array( //2
				'p' => array( 'language' => 'pt', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'badtoken', 'message' => 'loss of session data' ) ) ),
			array( //3
				'p' => array( 'id' => 'noANid', 'language' => 'fr', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-id', 'message' => 'No entity found' ) ) ),
			array( //4
				'p' => array( 'site' => 'qwerty', 'language' => 'pl', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_site', 'message' => "Unrecognized value for parameter 'site'" ) ) ),
			array( //5
				'p' => array( 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-link', 'message' => 'No entity found matching site link' ) ) ),
			array( //6
				'p' => array( 'title' => 'Blub', 'language' => 'en', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal', 'message' => 'Either provide the item "id" or pairs' ) ) ),
			array( //7
				'p' => array( 'site' => 'enwiki', 'language' => 'en', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal', 'message' => 'Either provide the item "id" or pairs' ) ) ),
		);
	}

	public function doTestSetLangAttributeExceptions( $params, $expected ){

		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if( !array_key_exists( 'id', $params )  && !array_key_exists( 'site', $params ) && !array_key_exists( 'title', $params ) ){
			$params['id'] = self::$testId;
		}

		// -- catch and check expected exceptions ---------------------
		try{
			if( $expected['exception']['code'] == 'badtoken' ){
				if ( !self::$usetoken ) {
					$this->markTestSkipped( "tokens disabled" );
				}
				$this->doApiRequest( $params );
			} else {
				$this->doApiRequestWithToken( $params );
			}
			$this->fail( "Failed to throw exception, {$expected['exception']['type']} " );

		} catch( \Exception $exception ){

			/** @var $exception \UsageException */ // trick IDEs into not showing errors
			if( array_key_exists( 'type', $expected['exception'] ) ){
				$this->assertInstanceOf( $expected['exception']['type'], $exception );
			}

			if( array_key_exists( 'code', $expected['exception'] ) ){
				$this->assertEquals( $expected['exception']['code'], $exception->getCodeString() );
			}

			if( array_key_exists( 'message', $expected['exception'] ) ){
				$this->assertContains( $expected['exception']['message'], $exception->getMessage() );
			}
		}
	}

}
