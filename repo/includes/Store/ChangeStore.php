<?php

namespace Wikibase\Repo\Store;

use Wikibase\Change;

/**
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
interface ChangeStore {

	/**
	 * @param Change $change
	 */
	public function saveChange( Change $change );

}
