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

	public function instanceProvider() {
		$instances = array();

		if ( defined( 'WB_VERSION' ) ) {
			$instances[] = StoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();
		}

		if ( defined( 'WBC_VERSION' ) ) {
			$instances[] = WikibaseClient::getDefaultInstance()->getStore( 'sqlstore' )->getSiteLinkTable();
		}

		if ( empty( $instances ) ) {
			$this->markTestIncomplete( 'No sitelink lookup tables available' );
		}

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetConflictsForItem( SiteLinkLookup $lookup ) {
		$conflicts = $lookup->getConflictsForItem( Item::newEmpty() );
		$this->assertTrue( $conflicts === array() );
	}

}
