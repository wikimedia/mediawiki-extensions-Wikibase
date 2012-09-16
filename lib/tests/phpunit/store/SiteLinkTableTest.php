<?php

namespace Wikibase\Test;
use \Wikibase\SiteLinkTable as SiteLinkTable;

/**
 * Tests for the Wikibase\SiteLinkTable class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SiteLink
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkTableTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		return array(
			array( 'its_a_table_name' ),
		);
	}

	public function testConstructor( $tableName ) {
		$instance = new SiteLinkTable( $tableName );

		$this->assertTrue( true );

		// TODO: migrate tests from ItemDeletionUpdate and ItemStructuredSave
	}

}