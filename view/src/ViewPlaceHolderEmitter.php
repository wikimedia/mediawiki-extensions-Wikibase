<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
interface ViewPlaceHolderEmitter {

	public function getPlaceholderMapping(
		EntityDocument $entity,
		$languageCode
	);

}
