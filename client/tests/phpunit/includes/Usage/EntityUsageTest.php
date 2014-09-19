<?php
namespace Wikibase\Client\Tests\Usage;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\EntityUsage
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityUsageTest extends PHPUnit_Framework_TestCase {

	public function testGetters() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );

		$this->assertEquals( $id, $usage->getEntityId() );
		$this->assertEquals( $aspect, $usage->getAspect() );

		$this->assertInternalType( 'string', $usage->toString() );
	}

}
