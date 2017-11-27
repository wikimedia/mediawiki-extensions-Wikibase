<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Maintenance\AnalysisConfigBuilder;

/**
 * Utility class to build analyzer configs for ElasticSearch
 * @package Wikibase\Repo\Search\Elastic
 */
class ConfigBuilder {

	/**
	 * @var AnalysisConfigBuilder
	 */
	private $builder;
	/**
	 * @var string[]
	 */
	private $languageList;
	/**
	 * @var array
	 */
	private $searchSettings;

	/**
	 * @param string[] $languageList
	 * @param array $searchSettings
	 * @param AnalysisConfigBuilder $builder
	 */
	public function __construct( array $languageList, array $searchSettings, AnalysisConfigBuilder $builder ) {
		$this->builder = $builder;
		$this->languageList = $languageList;
		$this->searchSettings = $searchSettings;
	}

	/**
	 * Build a new all-language analyzer configuration.
	 * This adds analyzers, filters, etc. which are required for language-specific
	 * indexing of Wikidata fields.
	 * @param array[] $config Existing config which will be modified with new analyzers
	 */
	public function buildConfig( array &$config ) {
		$stemmedLanguages = array_filter( $this->languageList, function ( $lang ) {
			return !empty( $this->searchSettings['useStemming'][$lang]['index'] );
		} );
		$nonStemmedLanguages = array_diff( $this->languageList, $stemmedLanguages );
		$this->builder->buildLanguageConfigs( $config, $stemmedLanguages,
			[ 'plain', 'plain_search', 'text', 'text_search' ] );
		$this->builder->buildLanguageConfigs( $config, $nonStemmedLanguages,
			[ 'plain', 'plain_search' ] );
	}

}
