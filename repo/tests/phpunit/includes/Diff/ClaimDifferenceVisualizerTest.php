<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\SnakFormatter;
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
class ClaimDifferenceVisualizerTest extends \MediaWikiTestCase {

	public function newSnakFormatter( $format = SnakFormatter::FORMAT_HTML ){
		$instance = $this->getMock( 'Wikibase\Lib\SnakFormatter' );
		$instance->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $format ) );
		$instance->expects( $this->any() )
			->method( 'canFormatSnak' )
			->will( $this->returnValue( true ) );
		$instance->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( '<i>SNAK</i>' ) );
		return $instance;
	}

	public function newEntityIdLabelFormatter(){
		$instance = $this
			->getMockBuilder( 'Wikibase\Lib\EntityIdLabelFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$instance->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( '<a>PID</a>' ) );

		return $instance;
	}

	public function newClaimDifferenceVisualizer(){
		return new ClaimDifferenceVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter(),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstruction(){
		$instance = $this->newClaimDifferenceVisualizer();
		$this->assertInstanceOf( 'Wikibase\Repo\Diff\ClaimDifferenceVisualizer', $instance );
	}

	public function testConstructionWithBadDetailsFormatter(){
		$this->setExpectedException( 'InvalidArgumentException' );
		new ClaimDifferenceVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter( 'qwertyuiop' ),
			$this->newSnakFormatter(),
			'en'
		);
	}

	public function testConstructionWithBadTerseFormatter(){
		$this->setExpectedException( 'InvalidArgumentException' );
		new ClaimDifferenceVisualizer(
			$this->newEntityIdLabelFormatter(),
			$this->newSnakFormatter(),
			$this->newSnakFormatter( 'qwertyuiop' ),
			'en'
		);
	}

	//TODO come up with a better way of testing this.... EWW at all the html...
	public function provideDifferenceAndClaim(){
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
				'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a></td><td colspan="2" class="diff-lineno">property / <a>PID</a></td></tr>'.
				'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
				'<div><del class="diffchange diffchange-inline"><span><i>SNAK</i></span></del></div></td>'.
				'<td class="diff-marker">+</td><td class="diff-addedline">'.
				'<div><ins class="diffchange diffchange-inline"><span><i>SNAK</i></span></ins></div></td></tr>'
			),
			//2 +qualifiers
			array(
				new ClaimDifference(
					null,
					new Diff( array(
						new DiffOpAdd( new PropertySomeValueSnak( 44 ) ),
					) )
				),
				new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / qualifier</td></tr>'.
				'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
				'<div><ins class="diffchange diffchange-inline"><span><a>PID</a>: <i>SNAK</i></span></ins></div></td></tr>'
			),
			//3 +references
			array(
				new ClaimDifference(
					null,
					null,
					new Diff( array(
						new DiffOpRemove( new Reference( new SnakList( array( new PropertyNoValueSnak( 50 ) ) ) ) ),
					) )
				),
				new Claim( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / reference</td><td colspan="2" class="diff-lineno"></td></tr>'.
				'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
				'<div><del class="diffchange diffchange-inline"><span><a>PID</a>: <i>SNAK</i></span></del></div></td><td colspan="2">&nbsp;</td></tr>'
			),
			//4 ranks
			array(
				new ClaimDifference(
					null,
					null,
					null,
					new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
				),
				new Statement( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / rank</td><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / rank</td></tr>'.
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
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / <a>PID</a></td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span><i>SNAK</i></span></ins></div></td></tr>'.

			// rank
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / rank</td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span>Normal rank</span></ins></div></td></tr>'.

			// qualifier
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / qualifier</td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span><a>PID</a>: <i>SNAK</i></span></ins></div></td></tr>'.

			// reference
			'<tr><td colspan="2" class="diff-lineno"></td>'.
			'<td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / reference</td></tr>'.
			'<tr><td colspan="2">&nbsp;</td><td class="diff-marker">+</td><td class="diff-addedline">'.
			'<div><ins class="diffchange diffchange-inline"><span><a>PID</a>: <i>SNAK</i></span></ins></div></td></tr>';

		$visualizer = $this->newClaimDifferenceVisualizer();
		$claim = new Statement(
			new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'foo' ) ),
			new SnakList( array( new PropertyNoValueSnak( 50 ) ) ),
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
			'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a></td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span><i>SNAK</i></span></del></div>'.
			'</td><td colspan="2">&nbsp;</td></tr>'.

			// rank
			'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / rank</td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span>Normal rank</span></del></div>'
			.'</td><td colspan="2">&nbsp;</td></tr>'.

			// qualifier
			'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / qualifier</td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span><a>PID</a>: <i>SNAK</i></span></del></div>'.
			'</td><td colspan="2">&nbsp;</td></tr>'.

			// reference
			'<tr><td colspan="2" class="diff-lineno">property / <a>PID</a>: <i>SNAK</i> / reference</td>'.
			'<td colspan="2" class="diff-lineno"></td></tr>'.
			'<tr><td class="diff-marker">-</td><td class="diff-deletedline">'.
			'<div><del class="diffchange diffchange-inline"><span><a>PID</a>: <i>SNAK</i></span></del></div>'.
			'</td><td colspan="2">&nbsp;</td></tr>';

		$visualizer = $this->newClaimDifferenceVisualizer();
		$claim = new Statement(
			new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'foo' ) ),
			new SnakList( array( new PropertyNoValueSnak( 50 ) ) ),
			new ReferenceList( array(
				new Reference(
					new SnakList( array(
						new PropertyValueSnak( new PropertyId( 'P44' ), new StringValue( 'referencevalue' ) )
					) ) ) ) ) );
		$html = $visualizer->visualizeRemovedClaim( $claim );

		$this->assertHtmlEquals( $expect, $html );
	}

}
