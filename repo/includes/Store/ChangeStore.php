<?php

namespace Wikibase\Repo\Store;

use Wikibase\Change;

/**
 * Service interface for recording changes.
 *
 * @see docs/change-propagation.wiki for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
interface ChangeStore {

	public function saveChange( Change $change );

}
