<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;

/**
 * Test case for language attributes API modules.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
abstract class ModifyTermTestCase extends WikibaseApiTestCase {

	/** @var string */
	protected static $testAction;
	/** @var bool */
	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Empty' ] );
		}
		self::$hasSetup = true;
	}

	public function provideData() {
		return [
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			[ //0
				'p' => [ 'language' => 'en', 'value' => '' ],
				'e' => [ 'edit-no-change' => true ] ],
			[ //1
				'p' => [ 'language' => 'en', 'value' => 'Value' ],
				'e' => [ 'value' => [ 'en' => 'Value' ] ] ],
			[ //2
				'p' => [ 'language' => 'en', 'value' => 'Value' ],
				'e' => [ 'value' => [ 'en' => 'Value' ], 'edit-no-change'  => true ] ],
			[ //3
				'p' => [ 'language' => 'en', 'value' => 'Another Value', 'summary' => 'Test summary!' ],
				'e' => [ 'value' => [ 'en' => 'Another Value' ] ] ],
			[ //4
				'p' => [ 'language' => 'en', 'value' => 'Different Value' ],
				'e' => [ 'value' => [ 'en' => 'Different Value' ] ] ],
			[ //5
				'p' => [ 'language' => 'sgs', 'value' => 'V?sata' ],
				'e' => [ 'value' => [ 'sgs' => 'V?sata','en' => 'Different Value' ] ] ],
			[ //6
				'p' => [ 'language' => 'en', 'value' => '' ],
				'e' => [ 'value' => [ 'sgs' => 'V?sata' ] ] ],
			[ //7
				'p' => [ 'language' => 'sgs', 'value' => '' ],
				'e' => [] ],
			[ //8
				'p' => [ 'language' => 'en', 'value' => "  x\nx  " ],
				'e' => [ 'value' => [ 'en' => 'x x' ] ] ],
		];
	}

	public function doTestSetTerm( $attribute, $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if ( !array_key_exists( 'id', $params ) ) {
			$params['id'] = EntityTestHelper::getId( 'Empty' );
		}
		if ( !array_key_exists( 'value', $expected ) ) {
			$expected['value'] = [];
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
		} else {
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
		$this->assertCount( count( $expected['value'] ), $dbLabels, 'Database contains exact number of terms' );
		foreach ( $expected['value'] as $valueLanguage => $value ) {
			$this->assertArrayHasKey( $valueLanguage, $dbLabels );
			$this->assertEquals( $value, $dbLabels[$valueLanguage][0] );
		}

		// -- check the edit summary --------------------------------------------
		if ( empty( $expected['edit-no-change'] ) ) {
			$this->assertRevisionSummary( [ self::$testAction, $params['language'] ], $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( "/{$params['summary']}/", $result['entity']['lastrevid'] );
			}
		}
	}

	public function provideExceptionData() {
		return [
			// p => params, e => expected

			// -- Test Exceptions -----------------------------
			[ //0
				'p' => [ 'language' => 'xx', 'value' => 'Foo' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'unknown_language' ),
						$this->equalTo( 'badvalue' )
					),
				] ],
			],
			[ //1
				'p' => [ 'language' => 'nl', 'value' => TermTestHelper::makeOverlyLongString() ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'modification-failed',
				] ],
			],
			[ //2
				'p' => [ 'language' => 'pt', 'value' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'notoken' ),
						$this->equalTo( 'missingparam' )
					),
					'message' => 'The "token" parameter must be set',
				] ],
				'token' => false,
			],
			[ //3
				'p' => [ 'language' => 'pt', 'value' => 'normalValue', 'token' => '88888888888888888888888888888888+\\' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'badtoken',
					'message' => 'Invalid CSRF token.',
				] ],
				'token' => false,
			],
			[ //4
				'p' => [ 'id' => 'noANid', 'language' => 'fr', 'value' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
					'message' => 'Invalid entity ID.',
				] ],
			],
			[ //5
				'p' => [ 'site' => 'qwerty', 'language' => 'pl', 'value' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'unknown_site' ),
						$this->equalTo( 'badvalue' )
					),
					'message' => 'Unrecognized value for parameter "site"',
				] ],
			],
			[ //6
				'p' => [ 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'value' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-entity-link',
				] ],
			],
			[ //7
				'p' => [ 'title' => 'Blub', 'language' => 'en', 'value' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ],
			],
			[ //8
				'p' => [ 'site' => 'enwiki', 'language' => 'en', 'value' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ],
			],
		];
	}

	public function doTestSetTermExceptions( $params, $expected, $token = true ) {
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if ( !array_key_exists( 'id', $params )
			&& !array_key_exists( 'site', $params )
			&& !array_key_exists( 'title', $params )
		) {
			$params['id'] = EntityTestHelper::getId( 'Empty' );
		}
		$this->doTestQueryExceptions( $params, $expected['exception'], null, $token );
	}

}
