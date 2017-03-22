<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use IContextSource;
use MWException;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Class for generating views of EntityDiff objects.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel kinzler
 */
class BasicEntityDiffVisualizer implements EntityDiffVisualizer {

	/**
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @var ClaimDiffer|null
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer|null
	 */
	private $claimDiffVisualizer;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @param IContextSource $contextSource
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 * @param SiteLookup $siteLookup
	 * @param EntityIdFormatter $entityIdFormatter
	 */
	public function __construct(
		IContextSource $contextSource,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter
	) {
		$this->context = $contextSource;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->siteLookup = $siteLookup;
		$this->entityIdFormatter = $entityIdFormatter;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityContentDiff.
	 *
	 * @param EntityContentDiff $diff
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff, EntityId $entityId ) {
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

		$termDiffVisualizer = new DiffView(
			array(),
			new Diff(
				array(
					$this->context->msg( 'wikibase-diffview-label' )->text() => $diff->getLabelsDiff(),
					$this->context->msg( 'wikibase-diffview-alias' )->text() => $diff->getAliasesDiff(),
					$this->context->msg( 'wikibase-diffview-description' )->text() => $diff->getDescriptionsDiff(),
				),
				true
			),
			$this->siteLookup,
			$this->entityIdFormatter,
			$this->context
		);

		$html .= $termDiffVisualizer->getHtml();

		foreach ( $diff->getClaimsDiff() as $claimDiffOp ) {
			$html .= $this->getClaimDiffHtml( $claimDiffOp );
		}

		// FIXME: this does not belong here as it is specific to items
		if ( $diff instanceof ItemDiff ) {
			$linkDiffVisualizer = new DiffView(
				array(),
				new Diff(
					array(
						$this->context->msg( 'wikibase-diffview-link' )->text() => $diff->getSiteLinkDiff(),
					),
					true
				),
				$this->siteLookup,
				$this->entityIdFormatter,
				$this->context
			);

			$html .= $linkDiffVisualizer->getHtml();
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

		$linkDiffVisualizer = new DiffView(
			array(),
			$diff,
			$this->siteLookup,
			$this->entityIdFormatter,
			$this->context
		);

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
