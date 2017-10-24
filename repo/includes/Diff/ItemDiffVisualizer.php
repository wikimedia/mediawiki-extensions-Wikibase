<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use MessageLocalizer;
use SiteLookup;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Class for generating views of EntityDiff objects.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class ItemDiffVisualizer implements EntityDiffVisualizer {

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

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var BasicEntityDiffVisualizer
	 */
	private $basicEntityDiffVisualizer;

	public function __construct(
		MessageLocalizer $messageLocalizer,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter,
		EntityDiffVisualizer $basicEntityDiffVisualizer
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->siteLookup = $siteLookup;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->basicEntityDiffVisualizer = $basicEntityDiffVisualizer;
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityContentDiff.
	 *
	 * @param EntityContentDiff $diff
	 *
	 * @return string
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		if ( $diff->isEmpty() ) {
			return '';
		}

		$basicHtml = $this->basicEntityDiffVisualizer->visualizeEntityContentDiff( $diff );

		return $basicHtml . $this->visualizeEntityDiff( $diff->getEntityDiff() );
	}

	/**
	 * Generates and returns an HTML visualization of the provided EntityDiff.
	 *
	 * @param EntityDiff $diff
	 *
	 * @return string
	 */
	protected function visualizeEntityDiff( EntityDiff $diff ) {
		return ( new ItemDiffView(
			[],
			new Diff(
				[
					$this->messageLocalizer->msg( 'wikibase-diffview-link' )->text() => $diff->getSiteLinkDiff(),
				],
				true
			),
			$this->siteLookup,
			$this->entityIdFormatter,
			$this->messageLocalizer
		) )->getHtml();
	}

}
