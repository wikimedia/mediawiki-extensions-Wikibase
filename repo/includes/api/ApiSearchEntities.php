<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to search for Wikibase entities.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiSearchEntities extends ApiBase {

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		// TODO: implement access to search
		// this should be something similar to
		// Change I6ea1f848: New function getFromTerm that searches over both aliases and labels
		// https://gerrit.wikimedia.org/r/#/c/25006/
		$hits = array(
			array(
				1,
				array( 'en' => array( 'language' => 'en', 'value' => 'Foo' ) ),
				array( 'en' => array( 'language' => 'en', 'value' => 'Some description about Foo' ) ),
				null,
				0.9
			),
			array(
				2,
				array( 'en' => array( 'language' => 'en', 'value' => 'Bar' ) ),
				array( 'en' => array( 'language' => 'en', 'value' => 'Some description about Bar' ) ),
				null,
				0.8
			),
		);

		// TODO: after we get the data we need to process the hits and calculate a score
		// It is pretty easy to calculate a score for a prefixsearch, but if we do a fuzzy search
		// we need a better way to calculate a score. Counting similar triplets of characters (trigrams)
		// is a possibillity. This kind of functions should not go in here, but be put in a separate library.

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'totalhits' => count( $hits )
			)
		);

		$this->getResult()->addValue(
			null,
			'search',
			array()
		);

		$entries = array();
		foreach ( $hits as $hit ) {
			$entry = array();

			$entry['score'] = $hit[4];
			$entry['id'] = $hit[0];

			if ( isset( $hit[1] ) ) {
				$entry['labels'] = $hit[1];
			}

			if ( isset( $hit[2] ) ) {
				$entry['descriptions'] = $hit[2];
			}

			if ( isset( $hit[3] ) ) {
				$entry['aliases'] = $hit[3];
			}

			$entries[] = $entry;
		}

		$this->getResult()->addValue(
			null,
			'search',
			$entries
		);

		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		// TODO: We probably need a flag for fuzzy searches. This is
		// only a boolean flag.
		// TODO: We need paging, and this can be done at least
		// in two different ways. Initially we make the implementation
		// without paging.
		return array(
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'type' => array(
				ApiBase::PARAM_TYPE => EntityFactory::singleton()->getEntityTypes(),
				ApiBase::PARAM_DFLT => 'item',
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
			),
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		// we probably need a flag for fuzzy searches
		return array(
			'text' => 'Search for this initial text.',
			'language' => 'Search within this language.',
			'type' => 'Search for this type of entity.',
			'limit' => array( 'Limit to this number of non-exact matches',
				"The value '0' will return all found matches." ),
		);
	}

	/**
	 * @see ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module to search for entities.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	/**
	 * @see ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsearchentities&text=abc&language=en'
			=> 'Search for "abc" in English language, with defaults for type and limit.',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsearchentity';
	}

	/**
	 * @see ApiBase::getVersion
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
