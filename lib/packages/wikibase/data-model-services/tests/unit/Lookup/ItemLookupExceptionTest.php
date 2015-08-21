<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\ItemLookupException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\ItemLookupException
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ItemLookupExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithJustAnId() {
		$itemId = new ItemId( 'Q123' );
		$exception = new ItemLookupException( $itemId );

		$this->assertEquals( $itemId, $exception->getEntityId() );
		$this->assertEquals( $itemId, $exception->getItemId() );
	}

}
