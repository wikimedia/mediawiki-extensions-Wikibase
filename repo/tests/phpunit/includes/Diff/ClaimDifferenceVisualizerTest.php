<?php

namespace Wikibase\Repo\Tests\Diff;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDifference;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;

/**
 * @covers \Wikibase\Repo\Diff\ClaimDifferenceVisualizer
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ClaimDifferenceVisualizerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @return DifferencesSnakVisualizer
	 */
	public function newDifferencesSnakVisualizer() {
		$instance = $this->createMock( DifferencesSnakVisualizer::class );

		$instance->method( 'getPropertyAndDetailedValue' )
			->willReturnCallback( function( PropertyValueSnak $snak ) {
				return $snak->getPropertyId()->getSerialization() . ': ' . $snak->getDataValue()->getValue()
					. ' (DETAILED)';
			} );

		$instance->method( 'getDetailedValue' )
			->willReturnCallback( function( PropertyValueSnak $snak = null ) {
				return $snak === null ? null : $snak->getDataValue()->getValue() . ' (DETAILED)';
			} );

		$instance->method( 'getPropertyHeader' )
			->willReturnCallback( function( Snak $snak ) {
				return 'property / ' . $snak->getPropertyId()->getSerialization();
			} );

		$instance->method( 'getPropertyAndValueHeader' )
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
		$this->assertInstanceOf( ClaimDifferenceVisualizer::class, $instance );
	}

	/**
	 * @todo Come up with a better way of testing this.... EWW at all the html...
	 */
	public function provideDifferenceAndClaim() {
		return [
			'no change' => [
				new ClaimDifference(),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'',
			],
			'mainsnak' => [
				new ClaimDifference(
					new DiffOpChange(
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'bar' ) ),
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'foo' ) )
					)
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / P1</td>' .
				'<td colspan="2" class="diff-lineno">property / P1</td></tr>' .
				'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
				'<div><del class="diffchange diffchange-inline"><span>bar (DETAILED)</span></del></div></td>' .
				'<td class="diff-marker" data-marker="+"></td><td class="diff-addedline">' .
				'<div><ins class="diffchange diffchange-inline"><span>foo (DETAILED)</span></ins></div></td></tr>',
			],
			'+qualifiers' => [
				new ClaimDifference(
					null,
					new Diff( [
						new DiffOpAdd( new PropertyValueSnak( 44, new StringValue( 'v' ) ) ),
					] )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno"></td>' .
				'<td colspan="2" class="diff-lineno">property / P1: foo / qualifier</td></tr>' .
				"<tr><td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>" .
				'<td class="diff-addedline">' .
				'<div><ins class="diffchange diffchange-inline"><span>P44: v (DETAILED)</span></ins></div></td></tr>',
			],
			'+references' =>
			[
				new ClaimDifference(
					null,
					null,
					new Diff( [
						new DiffOpRemove( new Reference( new SnakList( [
							new PropertyValueSnak( 50, new StringValue( 'v' ) ),
						] ) ) ),
					] )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr><td colspan="2" class="diff-lineno">property / P1: foo / reference</td>' .
				'<td colspan="2" class="diff-lineno"></td></tr>' .
				'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
				'<div><del class="diffchange diffchange-inline"><span>P50: v (DETAILED)</span>' .
				"</del></div></td><td colspan=\"2\">\u{00A0}</td></tr>",
			],
			'ranks' => [
				new ClaimDifference(
					null,
					null,
					null,
					new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'foo' ) ) ),
				'<tr>'
				. '<td colspan="2" class="diff-lineno">property / P1: foo / rank</td>'
				. '<td colspan="2" class="diff-lineno">property / P1: foo / rank</td>'
				. '</tr>'
				. '<tr>'
				. '<td class="diff-marker" data-marker="−"></td><td class="diff-deletedline"><div>'
				. '<del class="diffchange diffchange-inline"><span>Normal rank</span></del>'
				. '</div></td>'
				. '<td class="diff-marker" data-marker="+"></td><td class="diff-addedline"><div>'
				. '<ins class="diffchange diffchange-inline"><span>Preferred rank</span></ins>'
				. '</div></td>'
				. '</tr>',
			],
			'mainsnak and qualifiers' => [
				new ClaimDifference(
					new DiffOpChange(
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'oldmainsnakvalue' ) ),
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) )
					),
					new Diff( [
						new DiffOpAdd(
							new PropertyValueSnak( 44, new StringValue( 'newqualifiervalue' ) )
						),
						new DiffOpRemove(
							new PropertyValueSnak( 44, new StringValue( 'oldqualifiervalue' ) )
						),
					] )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) ) ),
				// mainsnak change
				'<tr>'
				. '<td colspan="2" class="diff-lineno">property / P1</td>'
				. '<td colspan="2" class="diff-lineno">property / P1</td>'
				. '</tr>'
				. '<tr>'
				. '<td class="diff-marker" data-marker="−"></td>'
				. '<td class="diff-deletedline"><div><del class="diffchange diffchange-inline">'
				. '<span>oldmainsnakvalue (DETAILED)</span></del></div></td>'
				. '<td class="diff-marker" data-marker="+"></td>'
				. '<td class="diff-addedline"><div><ins class="diffchange diffchange-inline">'
				. '<span>newmainsnakvalue (DETAILED)</span></ins></div></td>'
				. '</tr>'
				// added qualifier
				. '<tr>'
				. '<td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">'
				. 'property / P1: newmainsnakvalue / qualifier</td>'
				. '</tr>'
				. '<tr>'
				. "<td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>"
				. '<td class="diff-addedline"><div><ins class="diffchange diffchange-inline"><span>'
				. 'P44: newqualifiervalue (DETAILED)</span></ins></div></td>'
				. '</tr>'
				// removed qualifier
				. '<tr>'
				. '<td colspan="2" class="diff-lineno">property / P1: oldmainsnakvalue / qualifier'
				. '</td><td colspan="2" class="diff-lineno"></td>'
				. '</tr>'
				. '<tr>'
				. '<td class="diff-marker" data-marker="−"></td>'
				. '<td class="diff-deletedline"><div><del class="diffchange diffchange-inline">'
				. '<span>P44: oldqualifiervalue (DETAILED)</span></del></div></td>'
				. "<td colspan=\"2\">\u{00A0}</td>"
				. '</tr>',
			],
			'mainsnak and references' => [
				new ClaimDifference(
					new DiffOpChange(
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'oldmainsnakvalue' ) ),
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) )
					),
					null,
					new Diff( [
						new DiffOpAdd( new Reference( new SnakList( [
							new PropertyValueSnak( 44, new StringValue( 'newreferencevalue' ) ),
						] ) ) ),
						new DiffOpRemove( new Reference( new SnakList( [
							new PropertyValueSnak( 44, new StringValue( 'oldreferencevalue' ) ),
						] ) ) ),
					] )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) ) ),
				// mainsnak change
				'<tr>'
				. '<td colspan="2" class="diff-lineno">property / P1</td>'
				. '<td colspan="2" class="diff-lineno">property / P1</td>'
				. '</tr>'
				. '<tr>'
				. '<td class="diff-marker" data-marker="−"></td><td class="diff-deletedline"><div><del class="'
				. 'diffchange diffchange-inline"><span>oldmainsnakvalue (DETAILED)</span></del>'
				. '</div></td>'
				. '<td class="diff-marker" data-marker="+"></td><td class="diff-addedline"><div><ins class="'
				. 'diffchange diffchange-inline"><span>newmainsnakvalue (DETAILED)</span></ins>'
				. '</div></td>'
				. '</tr>'
				// added qualifier
				. '<tr>'
				. '<td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">'
				. 'property / P1: newmainsnakvalue / reference</td>'
				. '</tr>'
				. '<tr>'
				. "<td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>"
				. '<td class="diff-addedline"><div><ins class="diffchange diffchange-inline">'
				. '<span>P44: newreferencevalue (DETAILED)</span></ins></div></td>'
				. '</tr>'
				// removed qualifier
				. '<tr>'
				. '<td colspan="2" class="diff-lineno">property / P1: oldmainsnakvalue / reference'
				. '</td><td colspan="2" class="diff-lineno"></td>'
				. '</tr>'
				. '<tr>'
				. '<td class="diff-marker" data-marker="−"></td>'
				. '<td class="diff-deletedline"><div><del class="diffchange diffchange-inline">'
				. '<span>P44: oldreferencevalue (DETAILED)</span></del></div></td>'
				. "<td colspan=\"2\">\u{00A0}</td>"
				. '</tr>',
			],
			'mainsnak and rank' => [
				new ClaimDifference(
					new DiffOpChange(
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'oldmainsnakvalue' ) ),
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) )
					),
					null,
					null,
					new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) ) ),
				// mainsnak change
				'<tr><td colspan="2" class="diff-lineno">property / P1</td><td colspan="2" class="diff-lineno">property / P1</td></tr>' .
				'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
				'<div><del class="diffchange diffchange-inline"><span>oldmainsnakvalue (DETAILED)</span></del></div></td>' .
				'<td class="diff-marker" data-marker="+"></td><td class="diff-addedline">' .
				'<div><ins class="diffchange diffchange-inline"><span>newmainsnakvalue (DETAILED)</span></ins></div></td></tr>' .
				// rank change
				'<tr>' .
				'<td colspan="2" class="diff-lineno">property / P1: oldmainsnakvalue / rank</td>' .
				'<td colspan="2" class="diff-lineno">property / P1: newmainsnakvalue / rank</td>' .
				'</tr>' .
				'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
				'<div><del class="diffchange diffchange-inline"><span>Normal rank</span></del></div></td>' .
				'<td class="diff-marker" data-marker="+"></td><td class="diff-addedline">' .
				'<div><ins class="diffchange diffchange-inline"><span>Preferred rank</span></ins></div></td></tr>',
			],
			'mainsnak and rank with path' => [
				new ClaimDifference(
					new DiffOpChange(
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'oldmainsnakvalue' ) ),
						new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) )
					),
					null,
					null,
					new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
				),
				new Statement( new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'newmainsnakvalue' ) ) ),
				// mainsnak change
				'<tr><td colspan="2" class="diff-lineno">foo / bar / property / P1</td><td colspan="2" class="diff-lineno">' .
				'foo / bar / property / P1</td></tr><tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
				'<div><del class="diffchange diffchange-inline"><span>oldmainsnakvalue (DETAILED)</span></del></div></td>' .
				'<td class="diff-marker" data-marker="+"></td><td class="diff-addedline">' .
				'<div><ins class="diffchange diffchange-inline"><span>newmainsnakvalue (DETAILED)</span></ins></div></td></tr>' .
				// rank change
				'<tr>' .
				'<td colspan="2" class="diff-lineno">foo / bar / property / P1: oldmainsnakvalue / rank</td>' .
				'<td colspan="2" class="diff-lineno">foo / bar / property / P1: newmainsnakvalue / rank</td>' .
				'</tr>' .
				'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
				'<div><del class="diffchange diffchange-inline"><span>Normal rank</span></del></div></td>' .
				'<td class="diff-marker" data-marker="+"></td><td class="diff-addedline">' .
				'<div><ins class="diffchange diffchange-inline"><span>Preferred rank</span></ins></div></td></tr>',
				[ 'foo', 'bar' ],
			],
		];
	}

	/**
	 * @dataProvider provideDifferenceAndClaim
	 */
	public function testVisualizeClaimChange(
		ClaimDifference $difference,
		Statement $baseStatement,
		$expectedHtml,
		array $path = []
	) {
		$visualizer = $this->newClaimDifferenceVisualizer();
		$html = $visualizer->visualizeClaimChange( $difference, $baseStatement, $path );
		$this->assertHTMLEquals( $expectedHtml, $html );
	}

	public function testVisualizeNewClaim() {
		$expect =
			// main snak
			'<tr><td colspan="2" class="diff-lineno"></td>' .
			'<td colspan="2" class="diff-lineno">fooo / property / P12</td></tr>' .
			"<tr><td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>" .
			'<td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline"><span>foo (DETAILED)</span></ins></div></td></tr>' .

			// rank
			'<tr><td colspan="2" class="diff-lineno"></td>' .
			'<td colspan="2" class="diff-lineno">fooo / property / P12: foo / rank</td></tr>' .
			"<tr><td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>" .
			'<td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline"><span>Normal rank</span></ins></div></td></tr>' .

			// qualifier
			'<tr><td colspan="2" class="diff-lineno"></td>' .
			'<td colspan="2" class="diff-lineno">fooo / property / P12: foo / qualifier</td></tr>' .
			"<tr><td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>" .
			'<td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline"><span>P50: v (DETAILED)</span></ins></div></td></tr>' .

			// reference
			'<tr><td colspan="2" class="diff-lineno"></td>' .
			'<td colspan="2" class="diff-lineno">fooo / property / P12: foo / reference</td></tr>' .
			"<tr><td colspan=\"2\">\u{00A0}</td><td class=\"diff-marker\" data-marker=\"+\"></td>" .
			'<td class="diff-addedline">' .
			'<div><ins class="diffchange diffchange-inline"><span>P44: referencevalue (DETAILED)</span></ins></div></td></tr>';

		$visualizer = $this->newClaimDifferenceVisualizer();
		$claim = new Statement(
			new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'foo' ) ),
			new SnakList( [ new PropertyValueSnak( 50, new StringValue( 'v' ) ) ] ),
			new ReferenceList( [
				new Reference(
					new SnakList( [
						new PropertyValueSnak( new NumericPropertyId( 'P44' ), new StringValue( 'referencevalue' ) ),
					] ) ) ] ) );
		$html = $visualizer->visualizeNewClaim( $claim, [ 'fooo' ] );

		$this->assertHTMLEquals( $expect, $html );
	}

	public function testVisualizeRemovedClaim() {
		$expect =
			// main snak
			'<tr><td colspan="2" class="diff-lineno">bar / property / P12</td>' .
			'<td colspan="2" class="diff-lineno"></td></tr>' .
			'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
			'<div><del class="diffchange diffchange-inline"><span>foo (DETAILED)</span></del></div>' .
			"</td><td colspan=\"2\">\u{00A0}</td></tr>" .

			// rank
			'<tr><td colspan="2" class="diff-lineno">bar / property / P12: foo / rank</td>' .
			'<td colspan="2" class="diff-lineno"></td></tr>' .
			'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
			'<div><del class="diffchange diffchange-inline"><span>Normal rank</span></del></div>' .
			"</td><td colspan=\"2\">\u{00A0}</td></tr>" .

			// qualifier
			'<tr><td colspan="2" class="diff-lineno">bar / property / P12: foo / qualifier</td>' .
			'<td colspan="2" class="diff-lineno"></td></tr>' .
			'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
			'<div><del class="diffchange diffchange-inline"><span>P50: v (DETAILED)</span></del></div>' .
			"</td><td colspan=\"2\">\u{00A0}</td></tr>" .

			// reference
			'<tr><td colspan="2" class="diff-lineno">bar / property / P12: foo / reference</td>' .
			'<td colspan="2" class="diff-lineno"></td></tr>' .
			'<tr><td class="diff-marker" data-marker="−"></td><td class="diff-deletedline">' .
			'<div><del class="diffchange diffchange-inline"><span>P44: referencevalue (DETAILED)</span></del></div>' .
			"</td><td colspan=\"2\">\u{00A0}</td></tr>";

		$visualizer = $this->newClaimDifferenceVisualizer();
		$claim = new Statement(
			new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'foo' ) ),
			new SnakList( [ new PropertyValueSnak( 50, new StringValue( 'v' ) ) ] ),
			new ReferenceList( [
				new Reference(
					new SnakList( [
						new PropertyValueSnak( new NumericPropertyId( 'P44' ), new StringValue( 'referencevalue' ) ),
					] ) ) ] ) );
		$html = $visualizer->visualizeRemovedClaim( $claim, [ 'bar' ] );

		$this->assertHTMLEquals( $expect, $html );
	}

}
