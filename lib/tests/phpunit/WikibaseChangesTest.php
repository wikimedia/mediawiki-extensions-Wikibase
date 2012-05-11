<?php

/**
 * Tests for the WikibaseChanges class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseChangesTest extends MediaWikiTestCase {

	public function newFromArrayProvider() {
		return array(
			array(),

		);
	}

	/**
	 * @dataProvider newFromArraysProvider
	 */
	public function testNewFromArray( array $data ) {

	}

}
	
