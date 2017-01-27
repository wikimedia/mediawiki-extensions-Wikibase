<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelCountField implements SearchIndexField {

	/**
	 * @see SearchIndexField::getMapping
	 *
	 * @return array
	 */
	public function getMapping() {
		return array(
			'type' => 'integer'
		);
	}

	/**
	 * @see SearchIndexField::getFieldData
	 *
	 * @param EntityDocument $entity
	 *
	 * @return int
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( $entity instanceof LabelsProvider ) {
			return $entity->getLabels()->count();
		}

		return 0;
	}

}
