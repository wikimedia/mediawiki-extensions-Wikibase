<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use SearchEngine;

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
			'type' => 'string',
			'index' => 'no',
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
			'type' => 'string',
			'index_options' => 'docs',
			'analyzer' => $analyzer,
			'norms' => [ 'enabled' => false ]
		];
		if ( $search_analyzer ) {
			$config['search_analyzer'] = $search_analyzer;
		}
		return $config;
	}

	/**
	 * Produce specific field mapping
	 * @param SearchEngine $engine
	 * @param string $name
	 * @return \SearchIndexField
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		if ( !( $engine instanceof \CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return null;
		}
		return $this;
	}
}
