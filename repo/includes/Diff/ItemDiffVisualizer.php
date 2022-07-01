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
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class ItemDiffVisualizer implements EntityDiffVisualizer {

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

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
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter,
		EntityDiffVisualizer $basicEntityDiffVisualizer
	) {
		$this->messageLocalizer = $messageLocalizer;
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

		return $basicHtml . $this->visualizeSiteLinkDiff( $diff->getEntityDiff() );
	}

	/**
	 * Generates and returns an HTML visualization of the site link part
	 * of the provided EntityDiff (which must really be an ItemDiff).
	 *
	 * @param EntityDiff $diff
	 *
	 * @return string
	 */
	private function visualizeSiteLinkDiff( EntityDiff $diff ) {
		return ( new SiteLinkDiffView(
			[],
			new Diff(
				[
					// FIXME: getSiteLinkDiff only exists in ItemDiff
					// @phan-suppress-next-line PhanUndeclaredMethod
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
