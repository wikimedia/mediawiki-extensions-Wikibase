<?php

namespace Wikibase\Test;

use Wikibase\StoreFactory;

/**
 * @covers Wikibase\StoreFactory
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreFactoryTest extends \MediaWikiTestCase {

	public function testGetStore() {
		$this->assertInstanceOf( '\Wikibase\Store', StoreFactory::getStore() );
	}

}
