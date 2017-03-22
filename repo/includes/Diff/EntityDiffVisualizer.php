<?php

namespace Wikibase\Repo\Diff;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Interface for different visualizing diffs.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface EntityDiffVisualizer {

	/**
	 * This method should get an EntityContentDiff object and turn it into HTML
	 * Entity itself is going to be passed to give proper context (e.g. dynamic dispatching)
	 *
	 * @param EntityContentDiff $diff
	 * @param EntityDocument $entity
	 * @return string HTML
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff, EntityDocument $entity );

}
