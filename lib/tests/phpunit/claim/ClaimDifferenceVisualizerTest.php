<?php

namespace Wikibase\Test;

use Wikibase\ClaimDifference;

/**
 * Tests for the Wikibase\ClaimDifferenceVisualizer class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
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
			new \Diff\DiffOpChange(
				new \Wikibase\PropertyNoValueSnak( 42 ),
				new \Wikibase\PropertyNoValueSnak( 43 )
			),
			new \Diff\Diff( array(
				new \Diff\DiffOpAdd( new \Wikibase\PropertySomeValueSnak( 44 ) ),
				new \Diff\DiffOpRemove( new \Wikibase\PropertyValueSnak( 45, new \DataValues\StringValue( 'foo' ) ) ),
				new \Diff\DiffOpChange( new \Wikibase\PropertySomeValueSnak( 46 ), new \Wikibase\PropertySomeValueSnak( 47 ) ),
			) )
		);

		$differences[] = new ClaimDifference(
			new \Diff\DiffOpChange(
				new \Wikibase\PropertyNoValueSnak( 42 ),
				new \Wikibase\PropertyNoValueSnak( 43 )
			),
			null,
			new \Diff\Diff( array(
				new \Diff\DiffOpAdd( new \Wikibase\Reference() ),
				new \Diff\DiffOpRemove( new \Wikibase\Reference( new \Wikibase\SnakList( array( new \Wikibase\PropertyNoValueSnak( 50 ) ) ) ) ),
			) )
		);

		$differences[] = new ClaimDifference(
			null,
			null,
			null,
			new \Diff\DiffOpChange( \Wikibase\Statement::RANK_DEPRECATED, \Wikibase\Statement::RANK_PREFERRED )
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
