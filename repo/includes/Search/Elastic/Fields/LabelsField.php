<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\LabelsProvider;

/**
 * Field which contains per-language specific labels.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class LabelsField extends TermIndexField {

	/**
	 * Field name
	 */
	const NAME = "labels";

	/**
	 * List of available languages
	 * @var string[]
	 */
	private $languages;

	/**
	 * LabelsField constructor.
	 * @param string[] $languages
	 */
	public function __construct( $languages ) {
		$this->languages = $languages;
		parent::__construct( self::NAME, \SearchIndexField::INDEX_TYPE_NESTED );
	}

	/**
	 * @param SearchEngine $engine
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}

		$config = [
			'type' => 'object',
			'properties' => []
		];
		foreach ( $this->languages as $language ) {
			$langConfig = $this->getUnindexedField();

			$langConfig['fields']['prefix'] =
				$this->getSubfield( 'prefix_asciifolding', 'near_match_asciifolding' );
			$langConfig['fields']['near_match_folded'] =
				$this->getSubfield( 'near_match_asciifolding' );
			$langConfig['fields']['near_match'] = $this->getSubfield( 'near_match' );
			// This one is for full-text search, will tokenize
			// TODO: here we probably will need better language-specific analyzers
			$langConfig['fields']['plain'] = $this->getTokenizedSubfield( $engine->getConfig(),
				$language . '_plain', $language . '_plain_search' );
			// All labels are copies to labels_all
			$langConfig['copy_to'] = 'labels_all';

			$config['properties'][$language] = $langConfig;
		}

		return $config;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof LabelsProvider ) ) {
			return [];
		}
		$data = [];
		foreach ( $entity->getLabels() as $language => $label ) {
			$data[$language][] = $label->getText();
		}
		if ( $entity instanceof AliasesProvider ) {
			foreach ( $entity->getAliasGroups() as $aliases ) {
				$language = $aliases->getLanguageCode();
				if ( !isset( $data[$language] ) ) {
					$data[$language][] = '';
				}
				$data[$language] = array_merge( $data[$language], $aliases->getAliases() );
			}
		}
		return $data;
	}

	/**
	 * Set engine hints.
	 * Specifically, sets noop hint so that labels would be compared
	 * as arrays and removal of labels would be processed correctly.
	 * @param SearchEngine $engine
	 * @return array
	 */
	public function getEngineHints( SearchEngine $engine ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}
		return [ \CirrusSearch\Search\CirrusIndexField::NOOP_HINT => "equals" ];
	}

}
