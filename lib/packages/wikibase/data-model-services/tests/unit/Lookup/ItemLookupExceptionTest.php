<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\ItemLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\ItemLookupException
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ItemLookupExceptionTest extends TestCase {

	public function testConstructorWithJustAnId() {
		$itemId = new ItemId( 'Q123' );
		$exception = new ItemLookupException( $itemId );

		$this->assertEquals( $itemId, $exception->getEntityId() );
		$this->assertEquals( $itemId, $exception->getItemId() );
	}

}
