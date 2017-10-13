<?php

namespace Wikibase\Repo\Diff;

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
	 * @param EntityDiffVisualizerFactory $diffVisualizerFactory
	 */
	public function __construct( EntityDiffVisualizerFactory $diffVisualizerFactory ) {
		$this->diffVisualizerFactory = $diffVisualizerFactory;
	}

	/**
	 * @param EntityContentDiff $diff
	 * @return string HTML
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		$entityDiffVisualizer = $this->diffVisualizerFactory->newEntityDiffVisualizer(
			$diff->getEntityType()
		);

		return $entityDiffVisualizer->visualizeEntityContentDiff( $diff );
	}

}
