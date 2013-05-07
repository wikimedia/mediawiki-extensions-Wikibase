<?php

namespace Wikibase;

use IContextSource;
use Html;
use MWException;
use Diff\Diff;
use Diff\DiffOp;
use Diff\DiffOpChange;
use Diff\DiffOpAdd;
use Diff\DiffOpRemove;

/**
 * Class for generating views of EntityDiff objects.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class EntityDiffVisualizer {

	/**
	 * @since 0.4
	 *
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @since 0.4
	 *
	 * @var ClaimDiffer|null
	 */
	private $claimDiffer;

	/**
	 * @since 0.4
	 *
	 * @var ClaimDifferenceVisualizer|null
	 */
	private $claimDiffVisualizer;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param IContextSource $contextSource
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 */
	public function __construct( IContextSource $contextSource, ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView ) {
		$this->context = $contextSource;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityDiff.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $diff
	 *
	 * @return string
	 */
	public function visualizeDiff( EntityDiff $diff ) {
		$html = '';

		$termDiffVisualizer = new DiffView(
			array(),
			new Diff( array(
				$this->context->getLanguage()->getMessage( 'wikibase-diffview-label' ) => $diff->getLabelsDiff(),
				$this->context->getLanguage()->getMessage( 'wikibase-diffview-alias' ) => $diff->getAliasesDiff(),
				$this->context->getLanguage()->getMessage( 'wikibase-diffview-description' ) => $diff->getDescriptionsDiff(),
			), true ),
			$this->context
		);

		$html .= $termDiffVisualizer->getHtml();

		foreach ( $diff->getClaimsDiff() as $claimDiffOp ) {
			$html .= $this->getClaimDiffHtml( $claimDiffOp );
		}

		// FIXME: this does not belong here as it is specific to items
		if ( $diff instanceof ItemDiff ) {
			$termDiffVisualizer = new DiffView(
				array(),
				new Diff( array(
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-link' ) => $diff->getSiteLinkDiff(),
				), true ),
				$this->context
			);

			$html .= $termDiffVisualizer->getHtml();
		}

		return $html;
	}

	/**
	 * Returns the HTML for a single claim DiffOp.
	 *
	 * @since 0.4
	 *
	 * @param DiffOp $claimDiffOp
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function getClaimDiffHtml( DiffOp $claimDiffOp ) {
		if ( $claimDiffOp instanceof DiffOpChange ) {
			$claimDifference = $this->claimDiffer->diffClaims(
				$claimDiffOp->getOldValue(),
				$claimDiffOp->getNewValue()
			);
			return $this->claimDiffVisualizer->visualizeClaimChange(
				$claimDifference,
				$claimDiffOp->getNewValue()
			);
		}

		if ( $claimDiffOp instanceof DiffOpAdd ) {
			return $this->claimDiffVisualizer->visualizeNewClaim( $claimDiffOp->getNewValue() );
		} elseif ( $claimDiffOp instanceof DiffOpRemove ) {
			return $this->claimDiffVisualizer->visualizeRemovedClaim( $claimDiffOp->getOldValue() );
		} else {
			throw new MWException( 'Encountered an unexpected diff operation type for a claim' );
		}
	}

}
