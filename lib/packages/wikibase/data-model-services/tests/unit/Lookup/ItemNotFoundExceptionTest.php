<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\ItemNotFoundException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\ItemNotFoundException
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$itemId = new ItemId( 'Q42' );
		$exception = new ItemNotFoundException( $itemId );

		$this->assertEquals( $itemId, $exception->getItemId() );
	}

}
