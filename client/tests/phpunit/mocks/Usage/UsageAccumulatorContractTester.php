<?php

namespace Wikibase\Client\Tests\Mocks\Usage;

use PHPUnit\Framework\Assert;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * Contract tester for implementations of the UsageAccumulator interface.
 *
 * @uses Wikibase\Client\Usage\EntityUsage
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class UsageAccumulatorContractTester {

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	public function __construct( UsageAccumulator $usageAccumulator ) {
		$this->usageAccumulator = $usageAccumulator;
	}

	public function testAddGetUsage() {
		$this->testAddAndGetLabelUsage();
		$this->testAddAndGetDescriptionUsage();
		$this->testAddAndGetTitleUsage();
		$this->testAddAndGetStatementUsage();
		$this->testAddAndGetSiteLinksUsage();
		$this->testAddAndGetOtherUsage();
		$this->testAddAndGetAllUsage();

		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$expected = [
			new EntityUsage( $q2, EntityUsage::LABEL_USAGE, 'xx' ),
			new EntityUsage( $q3, EntityUsage::DESCRIPTION_USAGE, 'ru' ),
			new EntityUsage( $q2, EntityUsage::TITLE_USAGE ),
			new EntityUsage( $q3, EntityUsage::STATEMENT_USAGE, 'P42' ),
			new EntityUsage( $q2, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q2, EntityUsage::OTHER_USAGE ),
			new EntityUsage( $q3, EntityUsage::ALL_USAGE ),
		];

		$usages = $this->usageAccumulator->getUsages();
		$this->assertSameUsages( $expected, $usages );
	}

	private function testAddAndGetLabelUsage() {
		$q2 = new ItemId( 'Q2' );
		$this->usageAccumulator->addLabelUsage( $q2, 'xx' );

		$expected = new EntityUsage( $q2, EntityUsage::LABEL_USAGE, 'xx' );

		$usages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $usages );
	}

	private function testAddAndGetDescriptionUsage() {
		$q3 = new ItemId( 'Q3' );
		$this->usageAccumulator->addDescriptionUsage( $q3, 'ru' );

		$expected = new EntityUsage(
			$q3,
			EntityUsage::DESCRIPTION_USAGE,
			'ru'
		);

		$usages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $usages );
	}

	private function testAddAndGetTitleUsage() {
		$q2 = new ItemId( 'Q2' );
		$this->usageAccumulator->addTitleUsage( $q2 );

		$expected = new EntityUsage( $q2, EntityUsage::TITLE_USAGE );

		$entityUsages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $entityUsages );
	}

	private function testAddAndGetStatementUsage() {
		$q3 = new ItemId( 'Q3' );
		$p42 = new NumericPropertyId( 'P42' );
		$this->usageAccumulator->addStatementUsage( $q3, $p42 );

		$expected = new EntityUsage(
			$q3,
			EntityUsage::STATEMENT_USAGE,
			$p42->getSerialization()
		);

		$usages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $usages );
	}

	private function testAddAndGetSiteLinksUsage() {
		$q2 = new ItemId( 'Q2' );
		$this->usageAccumulator->addSiteLinksUsage( $q2 );

		$expected = new EntityUsage( $q2, EntityUsage::SITELINK_USAGE );

		$entityUsages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $entityUsages );
	}

	private function testAddAndGetOtherUsage() {
		$q2 = new ItemId( 'Q2' );
		$this->usageAccumulator->addOtherUsage( $q2 );

		$expected = new EntityUsage( $q2, EntityUsage::OTHER_USAGE );

		$usages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $usages );
	}

	private function testAddAndGetAllUsage() {
		$q3 = new ItemId( 'Q3' );
		$this->usageAccumulator->addAllUsage( $q3 );

		$expected = new EntityUsage( $q3, EntityUsage::ALL_USAGE );

		$usages = $this->usageAccumulator->getUsages();
		$this->assertContainsUsage( $expected, $usages );
	}

	/**
	 * @param EntityUsage $expected
	 * @param EntityUsage[] $actual
	 * @param string $message
	 */
	private function assertContainsUsage( EntityUsage $expected, array $actual, $message = '' ) {
		$expected = $expected->getIdentityString();
		$actual = $this->getIdentityStrings( $actual );

		Assert::assertContains( $expected, $actual, $message );
	}

	/**
	 * @param EntityUsage[] $expected
	 * @param EntityUsage[] $actual
	 * @param string $message
	 */
	private function assertSameUsages( array $expected, array $actual, $message = '' ) {
		$expected = $this->getIdentityStrings( $expected );
		$actual = $this->getIdentityStrings( $actual );
		sort( $expected );
		sort( $actual );

		Assert::assertEquals( $expected, $actual, $message );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	private function getIdentityStrings( array $usages ) {
		return array_values(
			array_map( function( EntityUsage $usage ) {
				return $usage->getIdentityString();
			}, $usages )
		);
	}

}
