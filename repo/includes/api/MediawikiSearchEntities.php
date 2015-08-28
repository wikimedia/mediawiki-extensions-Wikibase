<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to search for Wikibase entities using mediawiki's response format.
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class MediawikiSearchEntities extends ApiBase {

	/**
	 * @var EntitySearchHelper
	 */
	private $entitySearchHelper;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var string
	 */
	private $conceptBaseUri;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$repo = WikibaseRepo::getDefaultInstance();
		$entitySearchHelper = new EntitySearchHelper(
			$repo->getEntityTitleLookup(),
			$repo->getEntityIdParser(),
			$repo->newTermSearchInteractor( $this->getLanguage()->getCode() ),
			$repo->getStore()->getTermIndex(),
			new LanguageFallbackLabelDescriptionLookup(
				$repo->getTermLookup(),
				$repo->getLanguageFallbackChainFactory()
					->newFromLanguageCode( $this->getLanguage()->getCode() )
			)
		);

		$this->setServices(
			$entitySearchHelper,
			$repo->getEntityTitleLookup(),
			$repo->getEntityFactory()->getEntityTypes(),
			$repo->getSettings()->getSetting( 'conceptBaseUri' )
		);
	}

	/**
	 * Override services, for use for testing.
	 *
	 * @param EntitySearchHelper $entitySearchHelper
	 * @param EntityTitleLookup $titleLookup
	 * @param array $entityTypes
	 * @param string $conceptBaseUri
	 */
	public function setServices(
		EntitySearchHelper $entitySearchHelper,
		EntityTitleLookup $titleLookup,
		array $entityTypes,
		$conceptBaseUri
	) {
		$this->entitySearchHelper = $entitySearchHelper;
		$this->titleLookup = $titleLookup;
		$this->entityTypes = $entityTypes;
		$this->conceptBaseUri = $conceptBaseUri;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$entries = $this->getSearchEntries( $params );

		$this->getResult()->addValue(
			null,
			'query',
			$entries
		);
	}

	private function getSearchEntries( array $params ) {
		$searchResults = $this->entitySearchHelper->getRankedSearchResults(
			$params['search'],
			$params['type'],
			'',
			$params['continue'] + $params['limit'] + 1,
			false
		);

		$prefixsearch = array();
		$pages = array();

		foreach ( $searchResults as $match ) {
			$title = $this->titleLookup->getTitleForId( $match->getEntityId() );

			$pages[$title->getArticleID()] = array(
				'ns' => $title->getNamespace(),
				'title' => $match->getDisplayLabel()->getText(), // hack!!!
				'pageid' => $title->getArticleID(),
				'index' => count( $prefixsearch )
			);

			$prefixsearch[] = array(
				'ns' => $title->getNamespace(),
				'title' => $title->getPrefixedText(),
				'pageid' => $title->getArticleID()
			);
		}

		return array(
			'pages' => $pages,
			'prefixsearch' => $prefixsearch
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'search' => array(
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			),
			'type' => array(
				self::PARAM_TYPE => $this->entityTypes,
				self::PARAM_DFLT => 'item',
			),
			'limit' => array(
				self::PARAM_TYPE => 'limit',
				self::PARAM_DFLT => 7,
				self::PARAM_MAX => self::LIMIT_SML1,
				self::PARAM_MAX2 => self::LIMIT_SML2,
				self::PARAM_MIN => 0,
				self::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				self::PARAM_TYPE => 'integer',
				self::PARAM_REQUIRED => false,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbmwsearchentities&search=abc' => 'apihelp-wbmwsearchentities-example-1',
			'action=wbmwsearchentities&search=abc&limit=50' => 'apihelp-wbmwsearchentities-example-2',
			'action=wbmwsearchentities&search=alphabet&type=property' => 'apihelp-wbmwsearchentities-example-3',
		);
	}

}
