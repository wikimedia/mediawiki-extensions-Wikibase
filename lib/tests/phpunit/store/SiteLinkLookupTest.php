<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\SiteLinkLookup;
use Wikibase\Client\WikibaseClient;
use Wikibase\StoreFactory;

/**
 * Tests for the Wikibase\SiteLinkLookup implementing classes.
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkLookupTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local site link table." );
		}
	}

	public function testGetConflictsForItem() {
		$lookup = StoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();

		$conflicts = $lookup->getConflictsForItem( Item::newEmpty() );
		$this->assertTrue( $conflicts === array() );
	}

}
