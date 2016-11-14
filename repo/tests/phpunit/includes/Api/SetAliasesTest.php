<?php

namespace Wikibase\Test\Repo\Api;

use UsageException;

/**
 * @covers Wikibase\Repo\Api\SetAliases
 * @covers Wikibase\Repo\Api\ModifyEntity
 *
 * @group Database
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group BreakingTheSlownessBarrier
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SetAliasesTest extends ModifyTermTestCase {

	private static $hasSetup;

	protected function setUp() {
		self::$testAction = 'wbsetaliases';
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Empty' ] );
		}
		self::$hasSetup = true;
	}

	public function testSetAliases_create() {
		$params = [
			'action' => self::$testAction,
			'new' => 'property',
			'language' => 'en',
			'set' => 'Foo',
		];

		// -- do the request --------------------------------------------------
		list( $result, , ) = $this->doApiRequestWithToken( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );

		$resAliases = $this->flattenArray( $result['entity']['aliases'], 'language', 'value', true );
		$this->assertArrayHasKey( $params['language'], $resAliases );
		$this->assertArrayEquals( explode( '|', $params['set'] ), $resAliases[ $params['language'] ] );
	}

	public function provideData() {
		return [
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			[ //0
				'p' => [ 'language' => 'en', 'set' => '' ],
				'e' => [ 'edit-no-change'  => true ] ],
			[ //1
				'p' => [ 'language' => 'en', 'set' => 'Foo' ],
				'e' => [ 'value' => [ 'en' => [ 'Foo' ] ] ] ],
			[ //2
				'p' => [ 'language' => 'en', 'add' => 'Foo|Bax' ],
				'e' => [ 'value' => [ 'en' => [ 'Foo', 'Bax' ] ] ] ],
			[ //3
				'p' => [ 'language' => 'en', 'set' => 'Foo|Bar|Baz' ],
				'e' => [ 'value' => [ 'en' => [ 'Foo', 'Bar', 'Baz' ] ] ] ],
			[ //4
				'p' => [ 'language' => 'en', 'set' => 'Foo|Bar|Baz' ],
				'e' => [ 'value' => [ 'en' => [ 'Foo', 'Bar', 'Baz' ] ], 'edit-no-change'  => true ] ],
			[ //5
				'p' => [ 'language' => 'en', 'add' => 'Foo|Spam' ],
				'e' => [ 'value' => [ 'en' => [ 'Foo', 'Bar', 'Baz', 'Spam' ] ] ] ],
			[ //6
				'p' => [ 'language' => 'en', 'add' => 'ohi' ],
				'e' => [ 'value' => [ 'en' => [ 'Foo', 'Bar', 'Baz', 'Spam', 'ohi' ] ] ] ],
			[ //7
				'p' => [ 'language' => 'en', 'set' => 'ohi' ],
				'e' => [ 'value' => [ 'en' => [ 'ohi' ] ] ] ],
			[ //8
				'p' => [ 'language' => 'de', 'set' => '' ],
				'e' => [ 'value' => [ 'en' => [ 'ohi' ] ], 'edit-no-change'  => true ] ],
			[ //9
				'p' => [ 'language' => 'de', 'set' => 'hiya' ],
				'e' => [ 'value' => [ 'en' => [ 'ohi' ], 'de' => [ 'hiya' ] ] ] ],
			[ //10
				'p' => [ 'language' => 'de', 'add' => '||||||opps||||opps||||' ],
				'e' => [ 'value' => [ 'en' => [ 'ohi' ], 'de' => [ 'hiya', 'opps' ] ] ] ],
			[ //11
				'p' => [ 'language' => 'de', 'remove' => 'opps|hiya' ],
				'e' => [ 'value' => [ 'en' => [ 'ohi' ] ] ] ],
			[ //12
				'p' => [ 'language' => 'en', 'remove' => 'ohi' ],
				'e' => [] ],
			[ //13
				'p' => [ 'language' => 'en', 'set' => "  Foo\nBar  " ],
				'e' => [ 'value' => [ 'en' => [ 'Foo Bar' ] ] ] ],
		];
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetAliases( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = self::$testAction;
		if ( !array_key_exists( 'id', $params ) ) {
			$params['id'] = EntityTestHelper::getId( 'Empty' );
		}
		if ( !array_key_exists( 'value', $expected ) ) {
			$expected['value'] = [];
		}

		// -- do the request --------------------------------------------------
		list( $result, , ) = $this->doApiRequestWithToken( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );

		if ( array_key_exists( $params['language'], $expected['value'] ) ) {
			$resAliases = $this->flattenArray( $result['entity']['aliases'], 'language', 'value', true );
			$this->assertArrayHasKey( $params['language'], $resAliases );
			$this->assertArrayEquals( $expected['value'][$params['language']], $resAliases[ $params['language'] ] );
		}

		// -- check any warnings ----------------------------------------------
		if ( array_key_exists( 'warning', $expected ) ) {
			$this->assertArrayHasKey( 'warnings', $result, "Missing 'warnings' section in response." );
			$this->assertEquals( $expected['warning'], $result['warnings']['messages']['0']['name'] );
			$this->assertArrayHasKey( 'html', $result['warnings']['messages'] );
		}

		// -- check item in database -------------------------------------------
		$dbEntity = $this->loadEntity( EntityTestHelper::getId( 'Empty' ) );
		$this->assertArrayHasKey( 'aliases', $dbEntity );
		$dbAliases = $this->flattenArray( $dbEntity['aliases'], 'language', 'value', true );
		foreach ( $expected['value'] as $valueLanguage => $value ) {
			$this->assertArrayEquals( $value, $dbAliases[ $valueLanguage ] );
		}

		// -- check the edit summary --------------------------------------------
		if ( empty( $expected['edit-no-change'] ) ) {
			$this->assertRevisionSummary( [ self::$testAction, $params['language'] ], $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( '/' . $params['summary']. '/', $result['entity']['lastrevid'] );
			}
		}
	}

	public function provideExceptionData() {
		return [
			// p => params, e => expected

			// -- Test Exceptions -----------------------------
			[ //0
				'p' => [ 'language' => 'xx', 'add' => 'Foo' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'unknown_language'
				] ]
			],
			[ //1
				'p' => [ 'language' => 'nl', 'set' => TermTestHelper::makeOverlyLongString() ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'modification-failed'
				] ]
			],
			[ //2
				'p' => [ 'language' => 'pt', 'remove' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'notoken',
					'message' => 'The token parameter must be set'
				] ]
			],
			[ //3
				'p' => [ 'language' => 'pt', 'value' => 'normalValue', 'token' => '88888888888888888888888888888888+\\' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'badtoken',
					'message' => 'Invalid token'
				] ]
			],
			[ //4
				'p' => [ 'id' => 'noANid', 'language' => 'fr', 'add' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'invalid-entity-id',
					'message' => 'Invalid entity ID.'
				] ]
			],
			[ //5
				'p' => [ 'site' => 'qwerty', 'language' => 'pl', 'set' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'unknown_site',
					'message' => "Unrecognized value for parameter 'site'"
				] ]
			],
			[ //6
				'p' => [ 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'remove' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'no-such-entity-link',
					'message' => 'No entity found matching site link'
				] ]
			],
			[ //7
				'p' => [ 'title' => 'Blub', 'language' => 'en', 'add' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'param-illegal',
					'message' => 'Either provide the item "id" or pairs'
				] ]
			],
			[ //8
				'p' => [ 'site' => 'enwiki', 'language' => 'en', 'set' => 'normalValue' ],
				'e' => [ 'exception' => [
					'type' => UsageException::class,
					'code' => 'param-illegal'
				] ]
			],
		];
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetAliasesExceptions( $params, $expected ) {
		self::doTestSetTermExceptions( $params, $expected );
	}

}
