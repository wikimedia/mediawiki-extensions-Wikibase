<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Item as Item;
use Wikibase\Settings as Settings;
use Wikibase\ItemContent as ItemContent;

/**
 * Base class for test classes that test the API modules that derive from ApiWikibaseModifyItem.
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
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class ApiModifyItemBase extends ApiTestCase {

	/**
	 * @var ItemContent
	 */
	protected static $itemContent = false;
	
	/**
	 * This is to set up the environment.
	 */
	public function setUp() {
		parent::setUp();

		if ( self::$itemContent === false ) {
			self::$itemContent = ItemContent::newEmpty();
			self::$itemContent->save();
		}
		
		ApiTestCase::$users['wbeditor'] = new ApiTestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			array( 'wbeditor' )
		);
		
		// now we have to do the login with the previous user
		$data = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password )
		 );

		$token = $data[0]['login']['token'];

		$this->doApiRequest( array(
			'action' => 'login',
			'lgtoken' => $token,
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password
			),
			$data
		);
	}

	// FIXME: bad method name!
	protected function gettoken() {
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$first = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => ''
				),
				null,
				false,
				self::$users['wbeditor']->user
			);

			return $first[0]['wbsetitem']['setitemtoken'];
		}

		return null;
	}

	protected function assertSuccess( array $apiResponse ) {
		$this->assertArrayHasKey(
			'success',
			$apiResponse,
			"Must have an 'success' key in the result from the API"
		);

		$this->assertEquals(
			'1',
			$apiResponse['success'],
			"The success indicator must be 1"
		);

		$this->assertArrayHasKey(
			'item',
			$apiResponse,
			"Must have an 'item' key in the result from the API"
		);

		$this->assertArrayHasKey(
			'id',
			$apiResponse['item'],
			"Must have an 'id' key in the item elements part of the result from the API"
		);
	}

}
