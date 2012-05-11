<?php

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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiWikibaseModifyItemTest extends ApiTestCase {

	/**
	 * @var WikibaseItem
	 */
	protected static $item = false;
	
	/**
	 * This is to set up the environment.
	 */
	public function setUp() {
		parent::setUp();

		if ( self::$item === false ) {
			self::$item = WikibaseItem::newEmpty();
			self::$item->save();
		}
		
		ApiTestCase::$users['wbeditor'] = new ApiTestUser(
				'Apitesteditor',
				'Api Test Editor',
				'api_test_editor@example.com',
				array( 'wbeditor' )
			);
		$wgUser = self::$users['wbeditor']->user;
		
		// now we have to do the login with the previous user
		$data = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password )
		 );

		$token = $data[0]['login']['token'];

		$resp = $this->doApiRequest( array(
			'action' => 'login',
			'lgtoken' => $token,
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password
			),
			$data );
	}

	/**
	 * This is to tear down the environment.
	 */
	public function tearDown() {
		//self::$item->remove();

		parent::tearDown();
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
	
