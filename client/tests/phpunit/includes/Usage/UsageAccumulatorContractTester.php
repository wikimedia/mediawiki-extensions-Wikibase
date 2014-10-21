<?php
namespace Wikibase\Client\Usage\Tests;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Usage\UsageAccumulator;
use PHPUnit_Framework_Assert as Assert;

/**
 * Contract tester for implementations of the UsageAccumulator interface
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageAccumulatorContractTester  {

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	public function __construct( UsageAccumulator $usageAccumulator ) {
		$this->usageAccumulator = $usageAccumulator;
	}

	public function testAddGetUsage() {
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		$this->usageAccumulator->addSitelinksUsage( $q2 );
		$this->usageAccumulator->addLabelUsage( $q2 );
		$this->usageAccumulator->addTitleUsage( $q2 );
		$this->usageAccumulator->addAllUsage( $q3 );

		$usage = $this->usageAccumulator->getUsages();

		$expected = array(
			new EntityUsage( $q2, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q2, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q2, EntityUsage::TITLE_USAGE ),
			new EntityUsage( $q3, EntityUsage::ALL_USAGE ),
		);

		$this->assertSameUsages( $expected, $usage );
	}

	/**
	 * @param EntityUsage[] $expected
	 * @param EntityUsage[] $actual
	 * @param string $message
	 */
	private function assertSameUsages( array $expected, array $actual, $message = '' ) {
		$expected = $this->getUsageStrings( $expected );
		$actual = $this->getUsageStrings( $actual );

		Assert::assertEquals( $expected, $actual, $message );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	private function getUsageStrings( array $usages ) {
		return array_values(
			array_map( function( EntityUsage $usage ) {
				return $usage->getIdentityString();
			}, $usages )
		);
	}

}
