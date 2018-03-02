<?php

namespace Wikibase\Repo\Diff;

use Wikibase\Repo\Content\EntityContentDiff;

/**
 * Service interface for rendering EntityContentDiffs as HTML.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface EntityDiffVisualizer {

	/**
	 * Renders a EntityContentDiffs as HTML.
	 *
	 * @param EntityContentDiff $diff
	 * @return string HTML
	 */
	public function visualizeEntityContentDiff( EntityContentDiff $diff );

}
