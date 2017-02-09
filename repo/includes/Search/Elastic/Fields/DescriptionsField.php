<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use SearchEngine;
use SearchIndexFieldDefinition;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;

/**
 * Field which contains per-language specific descriptions.
 */
class DescriptionsField implements WikibaseIndexField {

	/**
	 * List of available languages
	 * @var string[]
	 */
	private $languages;

	public function __construct( $languages ) {
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
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
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

}
