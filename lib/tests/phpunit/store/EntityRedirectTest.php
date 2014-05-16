<?php

namespace Wikibase\Lib\Test\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRedirect;

/**
 * @covers Wikibase\Lib\Store\EntityRedirect
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirectTest extends \PHPUnit_Framework_TestCase {

	public function testConstruction() {
		$entityId = new ItemId( 'Q123' );
		$targetId = new ItemId( 'Q345' );

		$redirect = new EntityRedirect( $entityId, $targetId );

		$this->assertEquals( $entityId, $redirect->getEntityId(), '$redirect->getEntityId()' );
		$this->assertEquals( $targetId, $redirect->getTargetId(), '$redirect->getTargetId()' );
	}

}
