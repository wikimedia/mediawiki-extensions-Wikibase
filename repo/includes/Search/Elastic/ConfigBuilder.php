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
	public function __construct( array $languageList, $searchSettings, AnalysisConfigBuilder $builder ) {
		$this->builder = $builder;
		$this->languageList = $languageList;
		$this->searchSettings = $searchSettings;
	}

	/**
	 * Replace certain filter name in all configs with different name.
	 * @param array[] $config Configuration being processed
	 * @param string $oldName
	 * @param string $newName
	 */
	private function replaceFilter( &$config, $oldName, $newName ) {
		foreach ( $config['analyzer'] as &$analyzer ) {
			if ( !isset( $analyzer['filter'] ) ) {
				continue;
			}
			$analyzer['filter'] = array_map( function ( $filter ) use ( $oldName, $newName ) {
				if ( $filter === $oldName ) {
					return $newName;
				}
				return $filter;
			}, $analyzer['filter'] );
		}
	}

	/**
	 * Check every filter in the config - if it's the same as in old config,
	 * ignore it. If it has the same name, but different content - create new filter
	 * with different name by prefixing it with language name.
	 *
	 * @param array[] $config Configuration being processed
	 * @param array[] $standardFilters Existing filters list
	 * @param array[] $defaultFilters List of default filters already mentioned in the config
	 * @param string $prefix Prefix for disambiguation
	 * @return array[] The list of filters not in the old config.
	 */
	private function resolveFilters( &$config, $standardFilters, $defaultFilters, $prefix ) {
		$resultFilters = [];
		foreach ( $config['filter'] as $name => $filter ) {
			$existingFilter = null;
			if ( isset( $standardFilters[$name] ) ) {
				$existingFilter = $standardFilters[$name];
			} elseif ( isset( $defaultFilters[$name] ) ) {
				$existingFilter = $defaultFilters[$name];
			}

			if ( $existingFilter ) { // Filter with this name already exists
				if ( $existingFilter != $filter ) {
					// filter with the same name but different config - need to
					// rename by adding prefix
					$newName = $prefix . '_' . $name;
					$this->replaceFilter( $config, $name, $newName );
					$resultFilters[$newName] = $filter;
				}
			} else {
				$resultFilters[$name] = $filter;
			}
		}
		return $resultFilters;
	}

	/**
	 * Merge per-language config into the main config.
	 * It will copy specific analyzer and all dependant filters and char_filters.
	 * @param array $config Main config
	 * @param array $langConfig Per-language config
	 * @param string $name Name for analyzer whose config we're merging
	 * @param string $prefix Prefix for this configuration
	 */
	private function mergeConfig( &$config, $langConfig, $name, $prefix ) {
		$analyzer = $langConfig['analyzer'][$name];
		$config['analyzer'][$prefix . '_' . $name] = $analyzer;
		if ( !empty( $analyzer['filter'] ) ) {
			// Add private filters for this analyzer
			foreach ( $analyzer['filter'] as $filter ) {
				// Copy filters that are in language config but not in the main config.
				// We would not copy the same filter into the main config since due to
				// the resolution step we know they are the same (otherwise we would have
				// renamed it).
				if ( isset( $langConfig['filter'][$filter] ) &&
					!isset( $config['filter'][$filter] ) ) {
					$config['filter'][$filter] = $langConfig['filter'][$filter];
				}
			}
		}
		if ( !empty( $analyzer['char_filter'] ) ) {
			// Add private char_filters for this analyzer
			foreach ( $analyzer['char_filter'] as $filter ) {
				// Here unlike above we do not check for $langConfig since we assume
				// language config is not broken and all char filters are namespaced
				// nicely, so if the filter is mentioned in analyzer it is also defined.
				if ( !isset( $config['char_filter'][$filter] ) ) {
					$config['char_filter'][$filter] = $langConfig['char_filter'][$filter];
				}
			}
		}
	}

	/**
	 * Get list of filters that are mentioned in analyzers but not defined
	 * explicitly.
	 * @param $config
	 * @return array
	 */
	private function getDefaultFilters( &$config ) {
		$defaultFilters = [];
		foreach ( [ 'plain', 'plain_search', 'text', 'text_search' ] as $analyzer ) {
			if ( empty( $config['analyzer'][$analyzer]['filter'] ) ) {
				continue;
			}
			foreach ( $config['analyzer'][$analyzer]['filter'] as $filterName ) {
				if ( !isset( $config['filter'][$filterName] ) ) {
					// This is default definition for the built-in filter
					$defaultFilters[$filterName] = [ 'type' => $filterName ];
				}
			}
		}
		return $defaultFilters;
	}

	/**
	 * Build a new
	 * @param $config
	 */
	public function buildConfig( &$config ) {
		$defaultFilters = $this->getDefaultFilters( $config );
		foreach ( $this->languageList as $lang ) {
			$langConfig = $this->builder->buildConfig( $lang );
			$defaultFilters += $this->getDefaultFilters( $langConfig );
		}
		foreach ( $this->languageList as $lang ) {
			$langConfig = $this->builder->buildConfig( $lang );
			// Analyzer is: tokenizer + filter + char_filter
			// Tokenizers don't seem to be subject to customization now
			// Char filters are nicely namespaced
			// Filters are NOT - e.g. lowercase & icu_folding filters are different for different
			// languages! So we need to do some disambiguation here.
			$langConfig['filter'] = $this->resolveFilters( $langConfig, $config['filter'], $defaultFilters, $lang );
			// Merge configs
			// Used analyzers: plain, plain_search, text, text_search,
			foreach ( [ 'plain', 'plain_search' ] as $analyzer ) {
				$this->mergeConfig( $config, $langConfig, $analyzer, $lang );
			}
			if ( !empty( $this->searchSettings['useStemming'][$lang]['index'] ) ) {
				// only set up custom analyzers for "text" ones for stemmed languages
				foreach ( [ 'text', 'text_search' ] as $analyzer ) {
					$this->mergeConfig( $config, $langConfig, $analyzer, $lang );
				}
			}
		}
	}

}
