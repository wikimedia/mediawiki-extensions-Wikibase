<?php

namespace Wikibase\Test;
use \Wikibase\Settings as Settings;

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
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group ApiLanguageAttributeTest
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ApiLangAttributeBase extends ApiModifyItemBase {


	/**
	 * @dataProvider paramProvider
	 */
	public function doLanguageAttribute( $handle, $action, $attr, $langCode, $value, $exception = null ) {
		$id = $this->getItemId( $handle );

		// update the item ----------------------------------------------------------------
		$req = array(
			'token' => $this->getItemToken(),
			'id' => $id,
			'action' => $action,
			'language' => $langCode,
			'value' => $value
		);

		try {
			list( $apiResponse,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			if ( $exception ) {
				$this->fail( "expected exception $exception" );
			}
		}
		catch ( \Exception $e ) {
			if ( $exception !== null ) {
				$this->assertTrue( is_a( $e, $exception ), "Not the expected exception" );
				return;
			}
			else {
				throw $e;
			}
		}

		$this->assertSuccess( $apiResponse );

		$item = $apiResponse['entity'];
		$section = "{$attr}s";
		$record = null;

		foreach ( $item[$section] as $rec ) {
			if ( $rec['language'] == $langCode ) {
				$record = $rec;
				break;
			}
		}

		$this->assertNotNull( $record, "no $attr entry found for $langCode" );

		if ( $value === '' ) {
			$this->assertArrayHasKey( 'removed', $record );
		} else {
			$this->assertEquals( $value, $record['value'] );
		}

		// check item in the database ----------------------------------------------------------------
		$item = $this->loadItem( $id );
		$values = self::flattenArray( $item[$section], 'language', 'value' );

		if ( $value !== '' ) {
			$this->assertArrayHasKey( $langCode, $values, "should be present" );
			$this->assertEquals( $value, $values[$langCode], "should have been updated" );
		} else {
			$this->assertArrayNotHasKey( $langCode, $values, "should have been removed" );
		}

		// cleanup ----------------------------------------------------------------
		$this->resetItem( $handle );
	}

}
