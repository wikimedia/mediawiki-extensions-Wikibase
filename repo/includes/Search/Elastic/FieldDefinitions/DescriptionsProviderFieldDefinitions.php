<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

use Elastica\Document;
use Wikibase\DataModel\Entity\EntityDocument;

class DescriptionsProviderFieldDefinitions implements FieldDefinitions {

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
		$properties = [];

		foreach ( $this->languageCodes as $languageCode ) {
			$key = $this->getDescriptionPropertyKey( $languageCode );
			$properties[$key] = [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			];
		}

		return $properties;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getDescriptionPropertyKey( $languageCode ) {
		return 'description_' . $languageCode;
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

			$key = $this->getDescriptionPropertyKey( $languageCode );
			$document->set( $key, $description->getText() );
		}
	}

}
