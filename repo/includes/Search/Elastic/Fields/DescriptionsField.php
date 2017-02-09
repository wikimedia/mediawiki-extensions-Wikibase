<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use Flow\Import\SourceStore\Exception;
use SearchEngine;
use SearchIndexFieldDefinition;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;

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
		$field = $engine->makeSearchFieldMapping( $name, \SearchIndexField::INDEX_TYPE_NESTED );
		if ( is_callable( [ $field, 'setMergeCallback' ] ) ) {
			$field->setMergeCallback( function ( $that ) use ( $field ) {
				return $field;
			} );

		}
		/**
		 * @var SearchIndexFieldDefinition
		 */
		foreach ( $this->languages as $language ) {
			$langConfig = $engine->makeSearchFieldMapping( "$name.$language",
				\SearchIndexField::INDEX_TYPE_TEXT );

			$field->addSubfield( $language, $langConfig );
		}

		return $field;
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
