<?php

namespace Wikibase\Repo\Search\Elastic\Indexer;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

interface Indexer {

	/**
	 * @param EntityDocument $entity
	 * @param Document $document
	 */
	public function indexEntity( EntityDocument $entity, Document $document );

}
