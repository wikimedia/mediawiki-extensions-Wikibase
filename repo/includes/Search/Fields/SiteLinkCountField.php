<?php

namespace Wikibase\Repo\Search\Fields;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

class SiteLinkCountField implements Field {

	/**
	 * @return array
	 */
	public function getMapping() {
		return array(
			'type' => 'long'
		);
	}

	/**
	 * @see Field::buildData
	 */
	public function buildData( EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			return $entity->getSiteLinkList()->count();
		}

		return 0;
	}

}
