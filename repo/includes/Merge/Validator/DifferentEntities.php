<?php

namespace Wikibase\Repo\Merge\Validator;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
class DifferentEntities {

	/**
	 * @param EntityDocument $source
	 * @param EntityDocument $target
	 * @return bool
	 */
	public function validate( EntityDocument $source, EntityDocument $target ) {
		if ( $source === $target ) {
			return false;
		}

		$sourceId = $source->getId();

		if ( $sourceId === null ) {
			return false;
		}

		if ( $sourceId->equals( $target->getId() ) ) {
			return false;
		}

		return true;
	}

}
