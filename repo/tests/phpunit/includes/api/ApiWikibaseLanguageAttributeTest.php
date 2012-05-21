<?php

/**
 * Tests for the ApiWikibaseSetAliases API module.
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
class ApiWikibaseLanguageAttributeTest extends ApiWikibaseModifyItemTest {

	public function paramProvider() {
		return array(
			// lang attribute, lang code, operation, value, expected, exception that should be trown
			array( 'label',			'en', 'set', 'Oslo', 'Oslo', null ),
			array( 'description',	'en', 'set', 'Back to capitol of Norway', 'Back to capitol of Norway', null ),
			array( 'label',			'en', 'add', 'Oslo', 'Oslo', 'UsageException' ),
			array( 'description',	'en', 'add', 'Capitol of Norway', 'Capitol of Norway', 'UsageException' ),
			array( 'label',			'en', 'update', 'Bergen', 'Bergen', null ),
			array( 'description',	'en', 'update', 'Not capitol of Norway', 'Not capitol of Norway', null ),
		);
	}

	/**
	 * @dataProvider paramProvider
	 */
	public function testLanguageAttributeLabel( $attr, $langCode, $op, $value, $expected, $exception ) {
		
		$req = array();
		$token = $this->gettoken();
		if ( $token ) {
			$req['token'] = $token;
		}
		
		$req = array_merge( $req, array(
			'id' => self::$item->getId(),
			'action' => 'wbsetlanguageattribute',
			'language' => $langCode,
			'item' => $op,
			$attr => $value
		) );

		try {
			$apiResponse = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
		}
		catch (Exception $e) {
			if ($exception !== null) {
				$this->assertTrue(is_a($e, $exception), "Not the expected exception");
				return;
			}
			else {
				throw $e;
			}
		}

		$apiResponse = $apiResponse[0];

		$this->assertSuccess( $apiResponse );

		self::$item->reload();
		if ( $attr === 'label') {
			$str = self::$item->getLabel( $langCode );
		}
		if ( $attr === 'description') {
			$str = self::$item->getDescription( $langCode );
		}
		
		$this->assertEquals(
			$expected,
			$str,
			"Setting of {$attr} does not return the expected result"
		);
	}

}
	
