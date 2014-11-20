<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemNotFoundException;

/**
 * @covers Wikibase\DataModel\Entity\ItemNotFoundException
 * @uses Wikibase\DataModel\Entity\ItemId
 *
 * @group WikibaseDataModel
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
