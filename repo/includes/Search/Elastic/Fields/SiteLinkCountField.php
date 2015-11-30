<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
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
