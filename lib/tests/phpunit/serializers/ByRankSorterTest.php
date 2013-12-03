<?php

namespace Wikibase\Test;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ByRankSorter;
use Wikibase\PropertyNoValueSnak;
use Wikibase\Statement;

/**
 * @covers Wikibase\Lib\Serializers\ByRankSorter
 *
 * @since 0.5
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ByRankSorterTest extends \MediaWikiTestCase {

	public function provideSort() {
		$pref1 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P11' ) ) );
		$pref1->setRank( Claim::RANK_PREFERRED );

		$pref2 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P12' ) ) );
		$pref2->setRank( Claim::RANK_PREFERRED );

		$norm1 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P21' ) ) );
		$norm1->setRank( Claim::RANK_NORMAL );

		$norm2 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P22' ) ) );
		$norm2->setRank( Claim::RANK_NORMAL );

		$depr1 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P31' ) ) );
		$depr1->setRank( Claim::RANK_DEPRECATED );

		$depr2 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P32' ) ) );
		$depr2->setRank( Claim::RANK_DEPRECATED );

		$claim1 = new Claim( new PropertyNoValueSnak( new PropertyId( 'P41' ) ) );
		$claim2 = new Claim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) );

		return array(
			'empty' => array(
				array(),
				array(),
			),
			'singleton' => array(
				array( 'x' => $pref1 ),
				array( 'x' => $pref1 ),
			),
			'same' => array(
				array( $norm1, $norm2 ),
				array( $norm1, $norm2 ),
			),
			'sorted' => array(
				array( $claim1, $claim2, $pref1, $pref2, $norm1, $norm2, $depr1, $depr2 ),
				array( $claim1, $claim2, $pref1, $pref2, $norm1, $norm2, $depr1, $depr2 ),
			),
			'one off, tagged' => array(
				array( $claim1, 'x' => $pref1, $pref2, $norm1, $norm2, 'y' => $claim2, $depr1, $depr2 ),
				array( $claim1, 'y' => $claim2, 'x' => $pref1, $pref2, $norm1, $norm2, $depr1, $depr2 ),
			),
			'mixed' => array(
				array( 'd1' => $depr1, 'n1' => $norm1, 'p1' => $pref1, 'c1' => $claim1, 'n2' => $norm2, 'c2' => $claim2, 'd2' => $depr2, 'p2' => $pref2 ),
				array( 'c1' => $claim1, 'c2' => $claim2, 'p1' => $pref1, 'p2' => $pref2, 'n1' => $norm1, 'n2' => $norm2, 'd1' => $depr1, 'd2' => $depr2 ),
			),
		);
	}

	/**
	 * @dataProvider provideSort
	 *
	 * @param Claim[] $input
	 * @param Claim[] $expected
	 */
	public function testSort( $input, $expected ) {
		$sorter = new ByRankSorter();
		$actual = $sorter->sort( $input );

		$this->assertArrayEquals( $this->getIdMap( $expected ), $this->getIdMap( $actual ), true, true );
	}

	protected function getIdMap( $claims ) {
		$ids = array_map( function( Claim $claim ) {
			return $claim->getMainSnak()->getPropertyId()->getSerialization();
		}, $claims );

		return $ids;
	}
}
