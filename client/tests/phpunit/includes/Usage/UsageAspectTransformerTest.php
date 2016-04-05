<?php

namespace Wikibase\Client\Usage\Tests;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageAspectTransformer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @covers Wikibase\Client\Usage\UsageAspectTransformer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UsageAspectTransformerTest extends \PHPUnit_Framework_TestCase {

	public function testGetRelevantAspects() {
		$q1 = new ItemId( 'Q1' );
		$q99 = new ItemId( 'Q99' );

		$aspects = array(
			EntityUsage::TITLE_USAGE,
			EntityUsage::LABEL_USAGE,
		);

		$transformer = new UsageAspectTransformer();
		$transformer->setRelevantAspects( $q1, $aspects );

		$this->assertEquals( $aspects, $transformer->getRelevantAspects( $q1 ) );
		$this->assertEquals( [], $transformer->getRelevantAspects( $q99 ) );
	}

	public function provideGetFilteredUsages() {
		$q1 = new ItemId( 'Q1' );

		return array(
			'empty' => array(
				$q1,
				[],
				[],
				[]
			),
			'non relevant' => array(
				$q1,
				[],
				array( 'X' ),
				[]
			),
			'non used' => array(
				$q1,
				array( 'X' ),
				[],
				[]
			),
			'simple filter' => array(
				$q1,
				array( 'T', 'L' ),
				array( 'L', 'S' ),
				array( 'Q1#L' )
			),
			'all filter' => array(
				$q1,
				array( 'X' ),
				array( 'S', 'L' ),
				array( 'Q1#L', 'Q1#S' )
			),
			'filter all' => array(
				$q1,
				array( 'S', 'L' ),
				array( 'X' ),
				array( 'Q1#L', 'Q1#S' )
			),
			'modifier: direct match' => array(
				$q1,
				array( 'L.de', 'L.en' ),
				array( 'L.en', 'L.ru' ),
				array( 'Q1#L.en' )
			),
			'modifier: mismatch' => array(
				$q1,
				array( 'L.ru' ),
				array( 'L.en' ),
				[]
			),
			'modifier: match unmodified relevant' => array(
				$q1,
				array( 'L', 'L.ru' ),
				array( 'L.en' ),
				array( 'Q1#L.en' )
			),
			'modifier: match unmodified aspect' => array(
				$q1,
				array( 'L.en' ),
				array( 'L', 'L.ru' ),
				array( 'Q1#L.en' )
			),
			'modifier: mixed' => array(
				$q1,
				array( 'L.en', 'L' ),
				array( 'L', 'L.ru' ),
				array( 'Q1#L', 'Q1#L.en', 'Q1#L.ru' )
			),
		);
	}

	/**
	 * @dataProvider provideGetFilteredUsages
	 */
	public function testGetFilteredUsages( $entityId, $relevant, $used, $expected ) {
		$transformer = new UsageAspectTransformer();
		$transformer->setRelevantAspects( $entityId, $relevant );

		$usages = $transformer->getFilteredUsages( $entityId, $used );
		$this->assertEquals( $expected, array_keys( $usages ) );
	}

	public function provideTransformPageEntityUsages() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$usages = new PageEntityUsages( 23, array(
			new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q1, EntityUsage::TITLE_USAGE ),
			new EntityUsage( $q2, EntityUsage::ALL_USAGE ),
		) );

		return array(
			'empty' => array(
				[],
				$usages,
				[]
			),
			'non relevant' => array(
				[],
				$usages,
				[]
			),
			'simple filter' => array(
				array(
					'Q1' => array( 'T' ),
				),
				$usages,
				array( 'Q1#T' )
			),
			'all filter' => array(
				array(
					'Q2' => array( 'T', 'L' ),
					'Q1' => array( 'X' ),
				),
				$usages,
				array( 'Q1#L', 'Q1#T', 'Q2#L', 'Q2#T' )
			),
		);
	}

	/**
	 * @dataProvider provideTransformPageEntityUsages
	 */
	public function testTransformPageEntityUsages( $relevant, PageEntityUsages $usages, $expected ) {
		$transformer = new UsageAspectTransformer();
		$idParser = new BasicEntityIdParser();

		foreach ( $relevant as $id => $aspects ) {
			$transformer->setRelevantAspects( $idParser->parse( $id ), $aspects );
		}

		$transformed = $transformer->transformPageEntityUsages( $usages );

		$this->assertEquals( $usages->getPageId(), $transformed->getPageId() );
		$this->assertEquals( $expected, array_keys( $transformed->getUsages() ) );
	}

}
