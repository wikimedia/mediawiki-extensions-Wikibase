<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MediaWikiTestCase;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDifference;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;

/**
 * @covers Wikibase\Repo\Diff\ClaimDifferenceVisualizer
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ClaimDifferenceVisualizerTest extends MediaWikiTestCase {

	public function newDifferencesSnakVisualizer() {
		$instance = $this->getMockBuilder( 'Wikibase\Repo\Diff\DifferencesSnakVisualizer' )
			->disableOriginalConstructor()
			->getMock();

		$instance->expects( $this->any() )
			->method( 'formatSnak' )
			->willReturnCallback( function( PropertyValueSnak $snak ) {
				return $snak->getPropertyId()->getSerialization() . ': ' . $snak->getDataValue()->getValue()
					. ' (DETAILED)';
			} );

		$instance->expects( $this->any() )
			->method( 'formatSnakDetails' )
			->willReturnCallback( function( PropertyValueSnak $snak = null ) {
				return $snak === null ? '' : $snak->getDataValue()->getValue() . ' (DETAILED)';
			} );

		$instance->expects( $this->any() )
			->method( 'getSnakLabelHeader' )
			->willReturnCallback( function( Snak $snak ) {
				return 'property / ' . $snak->getPropertyId()->getSerialization();
			} );


		$instance->expects( $this->any() )
			->method( 'getSnakValueHeader' )
			->willReturnCallback( function( PropertyValueSnak $snak ) {
				return 'property / ' . $snak->getPropertyId()->getSerialization() . ': ' .
					$snak->getDataValue()->getValue();
			} );

		return $instance;
	}

	public function newClaimDifferenceVisualizer() {
		return new ClaimDifferenceVisualizer(
			$this->newDifferencesSnakVisualizer(),
			'en'
		);
	}

	public function testConstruction() {
		$instance = $this->newClaimDifferenceVisualizer();
		$this->assertInstanceOf( 'Wikibase\Repo\Diff\ClaimDifferenceVisualizer', $instance );
	}

	//TODO come up with a better way of testing this.... EWW at all the html...
	public function provideDifferenceAndClaim() {
		return array(
			//0 no change
			array(
				new ClaimDifference(),
				new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				''
			),
			//1 mainsnak
			array(
				new ClaimDifference(
					new DiffOpChange(
						new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'bar' ) ),
						new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) )
					)
				),
				new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / P1</td><td colspan="2" class="diff-lineno">property / P1</td></tr>'.
				'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
				'<div><del class="diffchange diffchange-inline"><span>bar (DETAILED)</span></del></div></td>'.
				'<td class="diff-marker">+</td><td class="diff-addedline">'.
				'<div><ins class="diffchange diffchange-inline"><span>foo (DETAILED)</span></ins></div></td></tr>'
			),
			//2 +qualifiers
			array(
				new ClaimDifference(
					null,
					new Diff( array(
						new DiffOpAdd( new PropertyValueSnak( 44, new StringValue( 'v' ) ) ),
					) )
				),
				new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">property / P1: foo / qualifier</td></tr>'.
				'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
				'<div><ins class="diffchange diffchange-inline"><span>P44: v (DETAILED)</span></ins></div></td></tr>'
			),
			//3 +references
			array(
				new ClaimDifference(
					null,
					null,
					new Diff( array(
						new DiffOpRemove( new Reference( new SnakList( array( new PropertyValueSnak( 50, new StringValue( 'v' ) ) ) ) ) ),
					) )
				),
				new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / P1: foo / reference</td><td colspan="2" class="diff-lineno"></td></tr>'.
				'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
				'<div><del class="diffchange diffchange-inline"><span>P50: v (DETAILED)</span></del></div></td><td colspan="2">&nbsp;</td></tr>'
			),
			//4 ranks
			array(
				new ClaimDifference(
					null,
					null,
					null,
					new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
				),
				new Statement( new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / P1: foo / rank</td><td colspan="2" class="diff-lineno">property / P1: foo / rank</td></tr>'.
				'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
				'<div><del class="diffchange diffchange-inline"><span>Normal rank</span></del></div></td>'.
				'<td class="diff-marker">+</td><td class="diff-addedline">'.
				'<div><ins class="diffchange diffchange-inline"><span>Preferred rank</span></ins></div></td></tr>'
			),
		);
	}

	/**
	 * @dataProvider provideDifferenceAndClaim
	 */
	public function testVisualizeClaimChange( $difference, $baseClaim, $expectedHtml ){
		$visualizer = $this->newClaimDifferenceVisualizer();
		$html = $visualizer->visualizeClaimChange( $difference, $baseClaim );
		$this->assertHtmlEquals( $expectedHtml, $html );
	}

	public function testVisualizeNewClaim(){
		$expect =
			// main snak
			'<tr><td colspan="2" class="diff-lineno">property / P12</td>'.
			'<td colspan="2" class="diff-lineno">property / P12</td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline"><div>'.
			'<del class="diffchange diffchange-inline"><span></span></del></div></td>'.
			'<td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span>foo (DETAILED)</span></ins></div></td></tr>'.

			// rank
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / P12: foo / rank</td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span>Normal rank</span></ins></div></td></tr>'.

			// qualifier
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / P12: foo / qualifier</td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span>P50: v (DETAILED)</span></ins></div></td></tr>'.

			// reference
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / P12: foo / reference</td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span>P44: referencevalue (DETAILED)</span></ins></div></td></tr>';

		$visualizer = $this->newClaimDifferenceVisualizer();
		$claim = new Statement(
			new Claim(
				new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'foo' ) ),
				new SnakList( array( new PropertyValueSnak( 50, new StringValue( 'v' ) ) ) )
			),
			new ReferenceList( array(
				new Reference(
					new SnakList( array(
						new PropertyValueSnak( new PropertyId( 'P44' ), new StringValue( 'referencevalue' ) )
					) ) ) ) ) );
		$html = $visualizer->visualizeNewClaim( $claim );

		$this->assertHtmlEquals( $expect, $html );
	}

	public function testVisualizeRemovedClaim(){
		$expect =
			// main snak
			'<tr><td colspan="2" class="diff-lineno">property / P12</td>'.
			'<td colspan="2" class="diff-lineno">property / P12</td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span>foo (DETAILED)</span></del></div>'.
			'</td><td class="diff-marker">+</td><td class="diff-addedline"><div>'.
			'<ins class="diffchange diffchange-inline"><span></span></ins></div></td></tr>'.

			// rank
			'<tr><td colspan="2" class="diff-lineno">property / P12: foo / rank</td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span>Normal rank</span></del></div>'
			.'</td><td colspan="2">&nbsp;</td></tr>'.

			// qualifier
			'<tr><td colspan="2" class="diff-lineno">property / P12: foo / qualifier</td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span>P50: v (DETAILED)</span></del></div>'.
			'</td><td colspan="2">&nbsp;</td></tr>'.

			// reference
			'<tr><td colspan="2" class="diff-lineno">property / P12: foo / reference</td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span>P44: referencevalue (DETAILED)</span></del></div>'.
			'</td><td colspan="2">&nbsp;</td></tr>';

		$visualizer = $this->newClaimDifferenceVisualizer();
		$claim = new Statement(
			new Claim(
				new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'foo' ) ),
				new SnakList( array( new PropertyValueSnak( 50, new StringValue( 'v' ) ) ) )
			),
			new ReferenceList( array(
				new Reference(
					new SnakList( array(
						new PropertyValueSnak( new PropertyId( 'P44' ), new StringValue( 'referencevalue' ) )
					) ) ) ) ) );
		$html = $visualizer->visualizeRemovedClaim( $claim );

		$this->assertHtmlEquals( $expect, $html );
	}

}
