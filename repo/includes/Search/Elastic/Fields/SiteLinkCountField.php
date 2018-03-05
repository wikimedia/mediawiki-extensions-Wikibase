<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCountField extends WikibaseNumericField {

	/**
	 * Field name
	 * @var string
	 */
	const NAME = 'sitelink_count';

	/**
	 * @see SearchIndexField::getFieldData
	 *
	 * @param EntityDocument $entity
	 *
	 * @return int
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			return $entity->getSiteLinkList()->count();
		}

		return 0;
	}

}
