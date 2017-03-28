<?php

namespace Wikibase\Repo\Diff;

use IContextSource;
use SiteLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Class for dynamic dispatching of EntityDiffVisualizer
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class DispatchingEntityDiffVisualizer implements EntityDiffVisualizer {

	/**
	 * @var EntityDiffVisualizerFactory
	 */
	private $diffVisualizerFactory;

	/**
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer
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
	 * @var BasicEntityDiffVisualizer|null
	 */
	private $basicEntityDiffVisualizer;

	/**
	 * @param EntityDiffVisualizerFactory $diffVisualizerFactory
	 * @param IContextSource $contextSource
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffView
	 * @param SiteLookup $siteLookup
	 * @param EntityIdFormatter $entityIdFormatter
	 */
	public function __construct(
		EntityDiffVisualizerFactory $diffVisualizerFactory,
		IContextSource $contextSource,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffView,
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter
	) {
		$this->diffVisualizerFactory = $diffVisualizerFactory;
		$this->context = $contextSource;
		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffView;
		$this->siteLookup = $siteLookup;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->basicEntityDiffVisualizer = null;
	}

	/**
	 * @param EntityContentDiff $diff
	 * @return string HTML
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		if ( $this->basicEntityDiffVisualizer === null ) {
			$this->basicEntityDiffVisualizer = new BasicEntityDiffVisualizer(
				$this->context,
				$this->claimDiffer,
				$this->claimDiffVisualizer,
				$this->siteLookup,
				$this->entityIdFormatter
			);
		}

		$entityDiffVisualizer = $this->diffVisualizerFactory->newEntityDiffVisualizer(
			$diff->getEntityType(),
			$this->context,
			$this->claimDiffer,
			$this->claimDiffVisualizer,
			$this->siteLookup,
			$this->entityIdFormatter
		);

		$html = '';
		if ( $entityDiffVisualizer !== null ) {
			$html .= $entityDiffVisualizer->visualizeEntityContentDiff( $diff );
		}

		$html .= $this->basicEntityDiffVisualizer->visualizeEntityContentDiff( $diff );

		return $html;
	}

}
