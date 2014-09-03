<?php
namespace Wikibase\Usage\Tests;

use ParserOutput;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Usage\UsageAccumulator;

/**
 * @covers Wikibase\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageAccumulatorTest extends \PHPUnit_Framework_TestCase {

	public function testAddGetUsage() {
		$acc = new UsageAccumulator( new ParserOutput() );

		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		$acc->addUsage( $q1, 'links' );
		$acc->addUsage( $q2, 'label' );
		$acc->addUsage( $q3, 'label' );

		$usage = $acc->getUsages();
		$expected = array(
			'links' => array( $q1 ),
			'label' => array( $q2, $q3 ),
		);

		$this->assertEquals( $expected, $usage );
	}

}
