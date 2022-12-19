<?php

namespace Wikibase\Client\Tests\Unit\Usage;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageAspectTransformer;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\Usage\UsageAspectTransformer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class UsageAspectTransformerTest extends \PHPUnit\Framework\TestCase {

	public function testGetRelevantAspects() {
		$q1 = new ItemId( 'Q1' );
		$q99 = new ItemId( 'Q99' );

		$aspects = [
			EntityUsage::TITLE_USAGE,
			EntityUsage::LABEL_USAGE,
		];

		$transformer = new UsageAspectTransformer();
		$transformer->setRelevantAspects( $q1, $aspects );

		$this->assertEquals( $aspects, $transformer->getRelevantAspects( $q1 ) );
		$this->assertEquals( [], $transformer->getRelevantAspects( $q99 ) );
	}

	public function provideGetFilteredUsages() {
		$q1 = new ItemId( 'Q1' );

		return [
			'empty' => [
				$q1,
				[],
				[],
				[],
			],
			'non relevant' => [
				$q1,
				[],
				[ 'X' ],
				[],
			],
			'non used' => [
				$q1,
				[ 'X' ],
				[],
				[],
			],
			'simple filter' => [
				$q1,
				[ 'T', 'L' ],
				[ 'L', 'S' ],
				[ 'Q1#L' ],
			],
			'all filter' => [
				$q1,
				[ 'X' ],
				[ 'S', 'L' ],
				[ 'Q1#L', 'Q1#S' ],
			],
			'filter all' => [
				$q1,
				[ 'S', 'L' ],
				[ 'X' ],
				[ 'Q1#L', 'Q1#S' ],
			],
			'modifier: direct match' => [
				$q1,
				[ 'L.de', 'L.en' ],
				[ 'L.en', 'L.ru' ],
				[ 'Q1#L.en' ],
			],
			'modifier: mismatch' => [
				$q1,
				[ 'L.ru' ],
				[ 'L.en' ],
				[],
			],
			'modifier: match unmodified used aspect' => [
				$q1,
				[ 'L.en' ],
				[ 'L', 'L.ru' ],
				[ 'Q1#L.en' ],
			],
			'modifier: do not match unmodified relevant aspect' => [
				$q1,
				[ 'L' ],
				[ 'L.ru' ],
				[],
			],
			'modifier: mixed' => [
				$q1,
				[ 'L.en', 'L' ],
				[ 'L', 'L.ru' ],
				[ 'Q1#L', 'Q1#L.en' ],
			],
		];
	}

	/**
	 * @dataProvider provideGetFilteredUsages
	 */
	public function testGetFilteredUsages( ItemId $entityId, array $relevant, array $used, array $expected ) {
		$transformer = new UsageAspectTransformer();
		$transformer->setRelevantAspects( $entityId, $relevant );

		$usages = $transformer->getFilteredUsages( $entityId, $used );
		$this->assertEquals( $expected, array_keys( $usages ) );
	}

	public function provideTransformPageEntityUsages() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$usages = new PageEntityUsages( 23, [
			new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q1, EntityUsage::TITLE_USAGE ),
			new EntityUsage( $q2, EntityUsage::ALL_USAGE ),
		] );

		return [
			'empty' => [
				[],
				$usages,
				[],
			],
			'non relevant' => [
				[],
				$usages,
				[],
			],
			'simple filter' => [
				[
					'Q1' => [ 'T' ],
				],
				$usages,
				[ 'Q1#T' ],
			],
			'all filter' => [
				[
					'Q2' => [ 'T', 'L' ],
					'Q1' => [ 'X' ],
				],
				$usages,
				[ 'Q1#L', 'Q1#T', 'Q2#L', 'Q2#T' ],
			],
		];
	}

	/**
	 * @dataProvider provideTransformPageEntityUsages
	 */
	public function testTransformPageEntityUsages( array $relevant, PageEntityUsages $usages, array $expected ) {
		$transformer = new UsageAspectTransformer();

		foreach ( $relevant as $id => $aspects ) {
			$transformer->setRelevantAspects( new ItemId( $id ), $aspects );
		}

		$transformed = $transformer->transformPageEntityUsages( $usages );

		$this->assertEquals( $usages->getPageId(), $transformed->getPageId() );
		$this->assertEquals( $expected, array_keys( $transformed->getUsages() ) );
	}

}
