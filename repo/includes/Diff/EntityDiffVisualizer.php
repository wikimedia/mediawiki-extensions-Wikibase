<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use IContextSource;
use MWException;
use SiteStore;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Class for generating views of EntityDiff objects.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel kinzler
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
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param IContextSource $contextSource
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 * @param SiteStore $siteStore
	 */
	public function __construct( IContextSource $contextSource,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteStore $siteStore,
		EntityTitleLookup $entityTitleLookup,
		EntityRevisionLookup $entityRevisionLookup
	) {
		$this->context = $contextSource;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->siteStore = $siteStore;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityContentDiff.
	 *
	 * @since 0.5
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
	 * @since 0.4
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
				array (
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-label' ) => $diff->getLabelsDiff(),
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-alias' ) => $diff->getAliasesDiff(),
					$this->context->getLanguage()->getMessage( 'wikibase-diffview-description' ) => $diff->getDescriptionsDiff(),
				),
				true
			),
			$this->siteStore,
			$this->entityTitleLookup,
			$this->entityRevisionLookup,
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
					array (
						$this->context->getLanguage()->getMessage( 'wikibase-diffview-link' ) => $diff->getSiteLinkDiff(),
					),
					true
				),
				$this->siteStore,
				$this->entityTitleLookup,
				$this->entityRevisionLookup,
				$this->context
			);

			$html .= $linkDiffVisualizer->getHtml();
		}

		return $html;
	}


	/**
	 * Generates and returns an HTML visualization of the provided redirect Diff.
	 *
	 * @since 0.5
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
			$this->siteStore,
			$this->entityTitleLookup,
			$this->entityRevisionLookup,
			$this->context
		);

		$html = $linkDiffVisualizer->getHtml();
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
