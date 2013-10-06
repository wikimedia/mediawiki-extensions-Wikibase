<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Wikibase\ClaimDifference;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\SnakList;
use Wikibase\Statement;

/**
 * @covers Wikibase\ClaimDifferenceVisualizer
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimDifferenceVisualizerTest extends \MediaWikiTestCase {

	public function visualizeDiffProvider() {
		$differences = array();

		$differences[] = new ClaimDifference();

		$differences[] = new ClaimDifference(
			new DiffOpChange(
				new PropertyNoValueSnak( 42 ),
				new PropertyNoValueSnak( 43 )
			),
			new Diff( array(
				new DiffOpAdd( new PropertySomeValueSnak( 44 ) ),
				new DiffOpRemove( new PropertyValueSnak( 45, new StringValue( 'foo' ) ) ),
				new DiffOpChange( new PropertySomeValueSnak( 46 ), new PropertySomeValueSnak( 47 ) ),
			) )
		);

		$differences[] = new ClaimDifference(
			new DiffOpChange(
				new PropertyNoValueSnak( 42 ),
				new PropertyNoValueSnak( 43 )
			),
			null,
			new Diff( array(
				new DiffOpAdd( new Reference() ),
				new DiffOpRemove( new Reference( new SnakList( array( new PropertyNoValueSnak( 50 ) ) ) ) ),
			) )
		);

		$differences[] = new ClaimDifference(
			null,
			null,
			null,
			new DiffOpChange( Statement::RANK_DEPRECATED, Statement::RANK_PREFERRED )
		);

		return $this->arrayWrap( $differences );
	}

	/**
	 * @dataProvider visualizeDiffProvider
	 *
	 * @param ClaimDifference $claimDifference
	 */
// @todo provide a second parameter for the function
/*	public function testVisualizeDiff( ClaimDifference $claimDifference ) {
		$differenceVisualizer = new ClaimDifferenceVisualizer( new \Wikibase\CachingEntityLoader(), 'en' );

		$visualization = $differenceVisualizer->visualizeDiff( $claimDifference );

		$this->assertInternalType( 'string', $visualization );
	}
*/
}
