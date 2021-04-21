<?php

namespace Wikibase\Repo\Diff;

use IContextSource;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Class for dynamic dispatching of EntityDiffVisualizer
 *
 * @license GPL-2.0-or-later
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

	public function __construct( EntityDiffVisualizerFactory $diffVisualizerFactory, IContextSource $context ) {
		$this->diffVisualizerFactory = $diffVisualizerFactory;
		$this->context = $context;
	}

	/**
	 * @param EntityContentDiff $diff
	 * @return string HTML
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff ) {
		$entityDiffVisualizer = $this->diffVisualizerFactory->newEntityDiffVisualizer(
			$diff->getEntityType(),
			$this->context
		);

		return $entityDiffVisualizer->visualizeEntityContentDiff( $diff );
	}

}
