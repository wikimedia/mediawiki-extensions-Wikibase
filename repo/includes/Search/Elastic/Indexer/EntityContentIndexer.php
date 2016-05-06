<?php

namespace Wikibase\Repo\Search\Elastic\Indexer;

use Elastica\Document;
use UnexpectedValueException;
use Wikibase\EntityContent;

class EntityContentIndexer {

	/**
	 * @var Indexer[]
	 */
	private $entityIndexers;

	/**
	 * @param Indexer[] $entityIndexers
	 */
	public function __construct( array $entityIndexers ) {
		$this->entityIndexers = $entityIndexers;
	}

	/**
	 * @param EntityContent $entityContent
	 * @param Document $document
	 */
	public function indexContent( EntityContent $entityContent, Document $document ) {
		$entity = $entityContent->getEntity();
		$entityType = $entity->getType();

		if ( !array_key_exists( $entityType, $this->entityIndexers ) ) {
			throw new UnexpectedValueException( 'Unexpected entity type: ' . $entityType );
		}

		$this->entityIndexers[$entityType]->indexEntity( $entity, $document );
	}

}
