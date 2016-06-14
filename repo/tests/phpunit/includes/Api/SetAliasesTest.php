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
 * @group SetAliasesTest
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
			$this->initTestEntities( array( 'Empty' ) );
		}
		self::$hasSetup = true;
	}

	public function testSetAliases_create() {
		$params = array(
			'action' => self::$testAction,
			'new' => 'property',
			'language' => 'en',
			'set' => 'Foo',
		);

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
		return array(
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			array( //0
				'p' => array( 'language' => 'en', 'set' => '' ),
				'e' => array( 'edit-no-change'  => true ) ),
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
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz' ) ), 'edit-no-change'  => true ) ),
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
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ), 'edit-no-change'  => true ) ),
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
				'e' => array() ),
			array( //13
				'p' => array( 'language' => 'en', 'set' => "  Foo\nBar  " ),
				'e' => array( 'value' => array( 'en' => array( 'Foo Bar' ) ) ) ),
		);
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
			$expected['value'] = array();
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
			$this->assertRevisionSummary( array( self::$testAction, $params['language'] ), $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( '/' . $params['summary']. '/', $result['entity']['lastrevid'] );
			}
		}
	}

	public function provideExceptionData() {
		return array(
			// p => params, e => expected

			// -- Test Exceptions -----------------------------
			array( //0
				'p' => array( 'language' => 'xx', 'add' => 'Foo' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'unknown_language'
				) )
			),
			array( //1
				'p' => array( 'language' => 'nl', 'set' => TermTestHelper::makeOverlyLongString() ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'modification-failed'
				) )
			),
			array( //2
				'p' => array( 'language' => 'pt', 'remove' => 'normalValue' ),
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
				'p' => array( 'id' => 'noANid', 'language' => 'fr', 'add' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-id',
					'message' => 'Could not find such an entity ID.'
				) )
			),
			array( //5
				'p' => array( 'site' => 'qwerty', 'language' => 'pl', 'set' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'unknown_site',
					'message' => "Unrecognized value for parameter 'site'"
				) )
			),
			array( //6
				'p' => array( 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'remove' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-link',
					'message' => 'No entity found matching site link'
				) )
			),
			array( //7
				'p' => array( 'title' => 'Blub', 'language' => 'en', 'add' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-illegal',
					'message' => 'Either provide the item "id" or pairs'
				) )
			),
			array( //8
				'p' => array( 'site' => 'enwiki', 'language' => 'en', 'set' => 'normalValue' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-illegal'
				) )
			),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetAliasesExceptions( $params, $expected ) {
		self::doTestSetTermExceptions( $params, $expected );
	}

}
