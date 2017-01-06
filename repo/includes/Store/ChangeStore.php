<?php

namespace Wikibase\Repo\Store;

use Wikibase\Change;

/**
 * @license GPL-2.0+
 * @author Marius Hoch
 */
interface ChangeStore {

	/**
	 * @param Change $change
	 */
	public function saveChange( Change $change );

}
