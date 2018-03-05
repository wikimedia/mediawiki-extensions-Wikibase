<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use CirrusSearch\Search\TextIndexField;
use CirrusSearch\SearchConfig;
use SearchEngine;
use SearchIndexField;
use SearchIndexFieldDefinition;

/**
 * Generic class for fields that index terms such as labels.
 * This class applies only to ElasticSearch fields currently.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
abstract class TermIndexField extends SearchIndexFieldDefinition implements WikibaseIndexField {

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
	 *
	 * @param string $analyzer
	 * @param string $search_analyzer
	 *
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
	 * Create a tokenized string field config with specific analyzer fields.
	 *
	 * @param SearchConfig $config
	 * @param string $analyzer
	 * @param string $search_analyzer
	 * @return array
	 */
	protected function getTokenizedSubfield( SearchConfig $config, $analyzer, $search_analyzer = null ) {
		$field = [
			'type' => 'text',
			'analyzer' => $analyzer,
			'position_increment_gap' => TextIndexField::POSITION_INCREMENT_GAP,
			'similarity' => TextIndexField::getSimilarity( $config, $this->name, $analyzer ),
		];

		if ( $search_analyzer ) {
			$field['search_analyzer'] = $search_analyzer;
		}

		return $field;
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
	 *
	 * @param SearchEngine $engine
	 * @param string $name
	 *
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
