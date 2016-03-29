<?php

namespace Wikibase\Test\Repo\Api;

use UsageException;

/**
 * Test case for language attributes API modules.
 *
 * @license GPL-2.0+
 * @author Addshore
 */
abstract class ModifyTermTestCase extends WikibaseApiTestCase {

	protected static $testAction;
	protected static $testId;
	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( array( 'Empty' ) );
		}
		self::$hasSetup = true;
	}

	public function provideData() {
		return array(
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			array( //0
				'p' => array( 'language' => 'en', 'value' => '' ),
				'e' => array( 'edit-no-change' => true ) ),
			array( //1
				'p' => array( 'language' => 'en', 'value' => 'Value' ),
				'e' => array( 'value' => array( 'en' => 'Value' ) ) ),
			array( //2
				'p' => array( 'language' => 'en', 'value' => 'Value' ),
				'e' => array( 'value' => array( 'en' => 'Value' ), 'edit-no-change'  => true ) ),
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
				'e' => array() ),
			array( //8
				'p' => array( 'language' => 'en', 'value' => "  x\nx  " ),
				'e' => array( 'value' => array( 'en' => 'x x' ) ) ),
		);
	}

	public function doTestSetTerm( $attribute, $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if ( !array_key_exists( 'id', $params ) ) {
			$params['id'] = EntityTestHelper::getId( 'Empty' );
		}
		if ( !array_key_exists( 'value', $expected ) ) {
			$expected['value'] = array();
		}

		// -- do the request --------------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );

		// -- check the result only has our changed data (if any)  ------------
		$this->assertCount(
			1,
			$result['entity'][$attribute],
			'Entity return contained more than a single language'
		);
		$this->assertArrayHasKey(
			$params['language'],
			$result['entity'][$attribute],
			"Entity doesn't return expected language" );
		$this->assertEquals(
			$params['language'],
			$result['entity'][$attribute][$params['language']]['language'],
			'Returned incorrect language'
		);

		if ( array_key_exists( $params['language'], $expected['value'] ) ) {
			$this->assertEquals(
				$expected['value'][ $params['language'] ],
				$result['entity'][$attribute][$params['language']]['value'], "Returned incorrect attribute {$attribute}"
			);
		} elseif ( empty( $value ) ) {
			$this->assertArrayHasKey(
				'removed',
				$result['entity'][$attribute][ $params['language'] ],
				"Entity doesn't return expected 'removed' marker"
			);
		}

		// -- check any warnings ----------------------------------------------
		if ( array_key_exists( 'warning', $expected ) ) {
			$this->assertArrayHasKey( 'warnings', $result, "Missing 'warnings' section in response." );
			$this->assertEquals( $expected['warning'], $result['warnings']['messages']['0']['name'] );
			$this->assertArrayHasKey( 'html', $result['warnings']['messages'] );
		}

		// -- check item in database -------------------------------------------
		$dbEntity = $this->loadEntity( EntityTestHelper::getId( 'Empty' ) );
		$this->assertArrayHasKey( $attribute, $dbEntity );
		$dbLabels = $this->flattenArray( $dbEntity[$attribute], 'language', 'value', true );
		foreach ( $expected['value'] as $valueLanguage => $value ) {
			$this->assertArrayHasKey( $valueLanguage, $dbLabels );
			$this->assertEquals( $value, $dbLabels[$valueLanguage][0] );
		}

		// -- check the edit summary --------------------------------------------
		if ( empty( $expected['edit-no-change'] ) ) {
			$this->assertRevisionSummary( array( self::$testAction, $params['language'] ), $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( "/{$params['summary']}/", $result['entity']['lastrevid'] );
			}
		}
	}

	public function provideExceptionData() {
		return array(
			// p => params, e => expected

			// -- Test Exceptions -----------------------------
			array( //0
				'p' => array( 'language' => 'xx', 'value' => 'Foo' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'unknown_language'
				) )
			),
			array( //1
				'p' => array( 'language' => 'nl', 'value' => TermTestHelper::makeOverlyLongString() ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'modification-failed'
				) )
			),
			array( //2
				'p' => array( 'language' => 'pt', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'notoken',
					'message' => 'The token parameter must be set'
				) )
			),
			array( //3
				'p' => array( 'language' => 'pt', 'value' => 'normalValue', 'token' => '88888888888888888888888888888888+\\' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'badtoken',
					'message' => 'Invalid token'
				) )
			),
			array( //4
				'p' => array( 'id' => 'noANid', 'language' => 'fr', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-id',
					'message' => 'Could not find such an entity ID.'
				) )
			),
			array( //5
				'p' => array( 'site' => 'qwerty', 'language' => 'pl', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'unknown_site',
					'message' => "Unrecognized value for parameter 'site'"
				) )
			),
			array( //6
				'p' => array( 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-link',
					'message' => 'No entity found matching site link'
				) )
			),
			array( //7
				'p' => array( 'title' => 'Blub', 'language' => 'en', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-illegal',
					'message' => 'Either provide the item "id" or pairs'
				) )
			),
			array( //8
				'p' => array( 'site' => 'enwiki', 'language' => 'en', 'value' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-illegal',
					'message' => 'Either provide the item "id" or pairs'
				) )
			),
		);
	}

	public function doTestSetTermExceptions( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if ( !array_key_exists( 'id', $params )
			&& !array_key_exists( 'site', $params )
			&& !array_key_exists( 'title', $params )
		) {
			$params['id'] = EntityTestHelper::getId( 'Empty' );
		}
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

}
