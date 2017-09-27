<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;

/**
 * Field which contains per-language specific descriptions.
 */
class DescriptionsField extends TermIndexField {

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
		parent::__construct( "", \SearchIndexField::INDEX_TYPE_NESTED );
	}

	/**
	 * @param SearchEngine $engine
	 * @return null|array
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

			// FIXME: now this config copies labels, but should have proper analyzer handling
			$langConfig['fields']['near_match_folded'] =
				$this->getSubfield( 'near_match_asciifolding' );
			$langConfig['fields']['near_match'] = $this->getSubfield( 'near_match' );
			// TODO: should we also have *_all field? Or add descriptions to "all"?
			$config['properties'][$language] = $langConfig;
		}

		return $config;
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

}
