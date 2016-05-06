<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

interface FieldDefinitions {

	/**
	 * @return array
	 */
	public function getMappingProperties();

	/**
	 * @param EntityDocument $entity
	 * @param Document $document
	 */
	public function indexEntity( EntityDocument $entity, Document $document );

}
