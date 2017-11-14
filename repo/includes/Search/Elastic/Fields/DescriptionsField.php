<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;

/**
 * Field which contains per-language specific descriptions.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class DescriptionsField implements WikibaseIndexField {

	/**
	 * List of available languages
	 * @var string[]
	 */
	private $languages;

	/**
	 * @param string[] $languages Available languages list.
	 */
	public function __construct( array $languages ) {
		$this->languages = $languages;
	}

	/**
	 * @param SearchEngine $engine
	 * @param string $name
	 * @return null|\SearchIndexField
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		// TODO: no mapping for now, since we're only storing it for retrieval
		// When we start indexing it, we'll need to figure out how to add proper analyzers
		return null;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string[] Array of descriptions in available languages.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			return [];
		}
		$data = [];
		foreach ( $entity->getDescriptions() as $language => $desc ) {
			$data[$language] = $desc->getText();
		}
		return $data;
	}

	public function getEngineHints( SearchEngine $engine ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}
		return [ \CirrusSearch\Search\CirrusIndexField::NOOP_HINT => "equals" ];
	}

}
