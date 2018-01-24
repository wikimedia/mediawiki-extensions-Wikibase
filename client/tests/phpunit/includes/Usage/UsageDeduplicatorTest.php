<?php

namespace Wikibase\Client\Tests\Usage;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\UsageDeduplicator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class UsageDeduplicatorTest extends \PHPUnit_Framework_TestCase {

	public function provideDeduplicate() {
		$q1 = new ItemId( 'Q1' );
		$q1Label = new EntityUsage( $q1, EntityUsage::LABEL_USAGE );
		$q1LabelEn = new EntityUsage( $q1, EntityUsage::LABEL_USAGE, 'en' );
		$q1All = new EntityUsage( $q1, EntityUsage::ALL_USAGE );
		$q1Statement = new EntityUsage( $q1, EntityUsage::STATEMENT_USAGE, 'P15' );

		$q2 = new ItemId( 'Q2' );
		$q2Label = new EntityUsage( $q2, EntityUsage::LABEL_USAGE );
		$q2Description = new EntityUsage( $q2, EntityUsage::DESCRIPTION_USAGE );
		$q2DescriptionFa = new EntityUsage( $q2, EntityUsage::DESCRIPTION_USAGE, 'fa' );

		return [
			[
				[ $q1LabelEn, $q1Label ],
				[ $q1Label ]
			],
			[
				[ $q1LabelEn ],
				[ $q1LabelEn ]
			],
			[
				[ $q1Label, $q1LabelEn, $q2Description, $q1All ],
				[ $q1Label, $q2Description, $q1All ]
			],
			[
				[ $q1LabelEn, $q2Label, $q1Statement ],
				[ $q1LabelEn, $q2Label, $q1Statement ]
			],
			[
				[ $q2Description, $q2DescriptionFa, $q1All ],
				[ $q2Description, $q1All ]
			],
		];
	}

	/**
	 * @dataProvider provideDeduplicate
	 * @param EntityUsage[] $usages
	 * @param EntityUsage[] $output
	 */
	public function testDeduplicate( array $usages, array $output ) {
		$expected = [];
		foreach ( $output as $usage ) {
			$expected[$usage->getIdentityString()] = $usage;
		}
		$this->assertEquals( $expected, ( new UsageDeduplicator() )->deduplicate( $usages ) );
	}

}
