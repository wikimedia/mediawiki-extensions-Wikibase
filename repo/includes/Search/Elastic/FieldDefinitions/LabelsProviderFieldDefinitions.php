<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

class LabelsProviderFieldDefinitions implements FieldDefinitions {

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
	 * @return array
	 */
	public function getMappingProperties() {
		$properties = $this->getLabelProperties();
		$properties['label_count'] = [ 'type' => 'integer' ];

		return $properties;
	}

	/**
	 * @return array
	 */
	private function getLabelProperties() {
		$properties = [];

		foreach ( $this->languageCodes as $languageCode ) {
			$key = $this->getLabelPropertyKey( $languageCode );
			$properties[$key] = [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			];
		}

		return $properties;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getLabelPropertyKey( $languageCode ) {
		return 'label_' . $languageCode;
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

			$key = $this->getLabelPropertyKey( $languageCode );
			$document->set( $key, $label->getText() );
		}

		$document->set( 'label_count', $labels->count() );
	}

}
