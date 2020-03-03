<?php

namespace Wikibase\Repo\Store;

use Wikibase\Lib\Changes\Change;

/**
 * Service interface for recording changes.
 *
 * @see @ref md_docs_topics_change-propagation for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
interface ChangeStore {

	public function saveChange( Change $change );

}
