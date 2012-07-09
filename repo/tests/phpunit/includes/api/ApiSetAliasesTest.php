<?php

namespace Wikibase\Test;
use ApiTestCase;
use ApiTestUser;
use \Wikibase\Settings as Settings;
use UsageException;
//use ApiModifyItemBase;
use Wikibase\Test\ApiModifyItemBase as ApiModifyItemBase;

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
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
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
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiSetAliasesTest extends ApiModifyItemBase {

	public function paramProvider() {
		return array(
			// lang code, list name, list values, expected result
			array( 'en', 'set', 'Foo|bar', 'Foo|bar' ),
			array( 'en', 'set', 'Foo|bar|baz', 'Foo|bar|baz' ),
			array( 'en', 'add', 'Foo|bar', 'Foo|bar|baz' ),
			array( 'en', 'add', 'Foo|spam', 'Foo|bar|baz|spam' ),
			array( 'en', 'add', 'ohi', 'Foo|bar|baz|spam|ohi' ),

			array( 'de', 'add', 'ohi', 'ohi' ),
			array( 'de', 'set', 'ohi|ohi|spam|spam', 'ohi|spam' ),

			array( 'en', 'remove', 'ohi', 'Foo|bar|baz|spam' ),
			array( 'en', 'remove', 'ohi', 'Foo|bar|baz|spam' ),
			array( 'en', 'remove', 'Foo|bar|baz|o_O', 'spam' ),
			array( 'en', 'add', 'o_O', 'spam|o_O' ),
			array( 'en', 'set', 'o_O', 'o_O' ),
			array( 'en', 'remove', 'o_O', '' ),
		);
	}

	/**
	 * @dataProvider paramProvider
	 */
	public function testSetAliases( $langCode, $param, $value, $expected ) {
		$req = array();
		$token = $this->getItemToken();

		if ( $token ) {
			$req['token'] = $token;
		}

		$item = self::$itemContent->getItem();
		$this->assertInstanceOf( '\Wikibase\Item', $item );

		$req = array_merge( $req, array(
			'id' => $item->getId(),
			'action' => 'wbsetaliases',
			//'usekeys' => true, // this comes from Settings::get( 'apiUseKeys' )
			'format' => 'json',
			'language' => $langCode,
			$param => $value
		) );

		$apiResponse = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

		$apiResponse = $apiResponse[0];

		$this->assertSuccess( $apiResponse );
		if ( $param === 'add') {
			$this->assertTrue(
				Settings::get( 'apiUseKeys' ) ? array_key_exists($langCode, $apiResponse['item']['aliases']) : !array_key_exists($langCode, $apiResponse['item']['aliases']),
				"Found '{$langCode}' and it should" . (Settings::get( 'apiUseKeys' ) ? ' ' : ' not ') . "exist in aliases"
			);
		}

		$expected = $expected === '' ? array() : explode( '|', $expected );
		self::$itemContent->reload();

		$item = self::$itemContent->getItem();
		$this->assertInstanceOf( '\Wikibase\Item', $item );

		$actual = array_values( $item->getAliases( $langCode ) );

		asort( $expected );
		asort( $actual );

		$this->assertEquals(
			$expected,
			$actual
		);
	}

}
