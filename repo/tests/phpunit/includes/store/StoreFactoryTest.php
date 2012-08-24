<?php

namespace Wikibase\Test;
use Wikibase\Store as Store;
use Wikibase\StoreFactory as StoreFactory;

/**
 * Tests for the Wikibase\StoreFactory class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreFactoryTest extends \MediaWikiTestCase {

	public function testGetStore() {
		$this->assertInstanceOf( '\Wikibase\Store', StoreFactory::getStore() );
		$this->assertTrue( StoreFactory::getStore() === StoreFactory::getStore() );
	}

}
