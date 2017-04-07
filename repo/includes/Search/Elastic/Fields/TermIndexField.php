<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use SearchEngine;
use SearchIndexField;

/**
 * Generic class for fields that index terms such as labels.
 * This class applies only to ElasticSearch fields currently.
 */
abstract class TermIndexField extends \SearchIndexFieldDefinition implements WikibaseIndexField  {

	/**
	 * Produce a plain unindexed string field.
	 * @return array
	 */
	protected function getUnindexedField() {
		return [
			'type' => 'text',
			'index' => 'false',
			'fields' => []
		];
	}

	/**
	 * Create a string field config with specific analyzer fields.
	 * @param string $analyzer
	 * @param string $search_analyzer
	 * @return array
	 */
	protected function getSubfield( $analyzer, $search_analyzer = null ) {
		$config = [
			'type' => 'text',
			'index_options' => 'docs',
			'analyzer' => $analyzer,
			'norms' => false,
		];
		if ( $search_analyzer ) {
			$config['search_analyzer'] = $search_analyzer;
		}
		return $config;
	}

	/**
	 * Merge two field definitions if possible.
	 *
	 * @param SearchIndexField $that
	 * @return SearchIndexField|false New definition or false if not mergeable.
	 */
	public function merge( SearchIndexField $that ) {
		// If it's the same class we're ok
		if ( ( $that instanceof self ) && $this->type === $that->type ) {
			return $that;
		}
		return false;
	}

	/**
	 * Produce specific field mapping
	 * @param SearchEngine $engine
	 * @param string $name
	 * @return SearchIndexField
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return null;
		}
		return $this;
	}

}
