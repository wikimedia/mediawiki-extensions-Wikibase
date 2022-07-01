<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MessageLocalizer;
use MWException;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Class for generating views of EntityDiff objects.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel kinzler
 */
class BasicEntityDiffVisualizer implements EntityDiffVisualizer {

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var ClaimDiffer|null
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer|null
	 */
	private $claimDiffVisualizer;

	public function __construct(
		MessageLocalizer $messageLocalizer,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityContentDiff.
	 *
	 * @param EntityContentDiff $diff
	 *
	 * @return string
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		$html = '';
		$html .= $this->visualizeRedirectDiff( $diff->getRedirectDiff() );
		$html .= $this->visualizeEntityDiff( $diff->getEntityDiff() );
		return $html;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityDiff.
	 *
	 * @param EntityDiff $diff
	 *
	 * @return string
	 */
	protected function visualizeEntityDiff( EntityDiff $diff ) {
		if ( $diff->isEmpty() ) {
			return '';
		}

		$html = '';

		$termDiffVisualizer = new BasicDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibase-diffview-label' )->text() => $diff->getLabelsDiff(),
					$this->messageLocalizer->msg( 'wikibase-diffview-alias' )->text() => $diff->getAliasesDiff(),
					$this->messageLocalizer->msg( 'wikibase-diffview-description' )->text() => $diff->getDescriptionsDiff(),
				],
				true
			)
		);

		$html .= $termDiffVisualizer->getHtml();

		foreach ( $diff->getClaimsDiff() as $claimDiffOp ) {
			$html .= $this->getClaimDiffHtml( $claimDiffOp );
		}

		return $html;
	}

	/**
	 * Generates and returns an HTML visualization of the provided redirect Diff.
	 *
	 * @param Diff $diff
	 *
	 * @return string
	 */
	protected function visualizeRedirectDiff( Diff $diff ) {
		if ( $diff->isEmpty() ) {
			return '';
		}

		//TODO: localize path (keys in the diff array)

		$linkDiffVisualizer = new BasicDiffView( [], $diff );

		$html = $linkDiffVisualizer->getHtml();
		return $html;
	}

	/**
	 * @param DiffOp $claimDiffOp
	 *
	 * @return string HTML
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
