<?php

namespace Wikibase\Repo\Search\Elastic\Indexer;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

class LabelsProviderIndexer implements Indexer {

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
		$labels = $entity->getLabels();

		foreach ( $labels as $languageCode => $label ) {
			if ( !in_array( $languageCode, $this->languageCodes ) ) {
				// unknown language, @todo log warning
				continue;
			}

			$key = 'label_' . $languageCode;
			$document->set( $key, $label->getText() );
		}

		$document->set( 'label_count', $labels->count() );
	}

}
