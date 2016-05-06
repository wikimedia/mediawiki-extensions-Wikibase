<?php

namespace Wikibase\Repo\Search\Elastic\Indexer;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

class DescriptionsProviderIndexer implements Indexer {

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @param string[] $languageCodes
	 */
	public function __construct( array $languageCodes ) {
		$this->languageCodes = $languageCodes;
	}

	/**
	 * @param EntityDocument $entity
	 * @param Document $document
	 */
	public function indexEntity( EntityDocument $entity, Document $document ) {
		$descriptions = $entity->getDescriptions();

		foreach ( $descriptions as $languageCode => $description ) {
			if ( !in_array( $languageCode, $this->languageCodes ) ) {
				// unknown language, @todo log warning
				continue;
			}

			$key = 'description_' . $languageCode;
			$document->set( $key, $description->getText() );
		}
	}

}
