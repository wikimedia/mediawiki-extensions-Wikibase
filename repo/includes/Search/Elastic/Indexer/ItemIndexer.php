<?php

namespace Wikibase\Repo\Search\Elastic\Indexer;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

class ItemIndexer implements Indexer {

	/**
	 * @var LabelsProviderIndexer
	 */
	private $labelsProviderIndexer;

	/**
	 * @var DescriptionsProviderIndexer
	 */
	private $descriptionsProviderIndexer;

	/**
	 * @param string[] $languageCodes
	 */
	public function __construct( array $languageCodes ) {
		$this->labelsProviderIndexer = new LabelsProviderIndexer( $languageCodes );
		$this->descriptionsProviderIndexer = new DescriptionsProviderIndexer( $languageCodes );
	}

	/**
	 * @param EntityDocument $entity
	 * @param Document $document
	 */
	public function indexEntity( EntityDocument $entity, Document $document ) {
		$this->labelsProviderIndexer->indexEntity( $entity, $document );
		$this->descriptionsProviderIndexer->indexEntity( $entity, $document );

		$document->set( 'sitelink_count', $entity->getSiteLinkList()->count() );
		$document->set( 'statement_count', $entity->getStatements()->count() );
	}

}
