<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\EntityArticleIdNullLookup;

/**
 * @covers \Wikibase\Lib\Store\EntityArticleIdNullLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityArticleIdNullLookupTest extends TestCase {

	public function testGetArticleId() {
		$this->assertNull( ( new EntityArticleIdNullLookup() )
			->getArticleId( new NumericPropertyId( 'P666' ) ) );
	}

}
