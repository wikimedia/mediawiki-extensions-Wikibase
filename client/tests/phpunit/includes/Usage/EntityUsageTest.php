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

	public function testGetEntityId() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );

		$this->assertEquals( $id, $usage->getEntityId() );
	}

	public function testGetAspect() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );
		$this->assertEquals( $aspect, $usage->getAspect() );
	}

	public function testGetIdentityString() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );
		$this->assertInternalType( 'string', $usage->getIdentityString() );
	}

	public function testGetAspectKey() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::LABEL_USAGE;
		$modifier = 'ru';

		$usage = new EntityUsage( $id, $aspect );
		$this->assertEquals( $aspect, $usage->getAspectKey() );

		$usage = new EntityUsage( $id, $aspect, $modifier );
		$this->assertEquals( "$aspect.$modifier", $usage->getAspectKey() );
	}

	public function provideSplitAspectKey() {
		return array(
			array( 'L', array( 'L', null ) ),
			array( 'L.x', array( 'L', 'x' ) ),
			array( 'L.x.y', array( 'L', 'x.y' ) ),
		);
	}

	/**
	 * @dataProvider provideSplitAspectKey
	 */
	public function testSplitAspectKey( $aspectKey, $expectedParts ) {
		$parts = EntityUsage::splitAspectKey( $aspectKey );
		$this->assertEquals( $expectedParts, $parts );
	}

	public function provideMakeAspectKey() {
		return array(
			array( 'L', null, 'L' ),
			array( 'L', 'x', 'L.x' ),
		);
	}

	/**
	 * @dataProvider provideMakeAspectKey
	 */
	public function testMakeAspectKey( $aspect, $modifier, $expectedKey ) {
		$key = EntityUsage::makeAspectKey( $aspect, $modifier );
		$this->assertEquals( $expectedKey, $key );
	}

}
