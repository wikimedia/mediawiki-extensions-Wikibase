<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityFactory;
use Wikibase\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Search\EntitiesByTermSearcher;
use Wikibase\Term;
use Wikibase\Utils;

/**
 * API module to search for Wikibase entities.
 *
 * FIXME: this module is doing to much work. Ranking terms is not its job and should be delegated
 * FIXME: the continuation currently relies on the search order returned by the TermStore
 *
 * Note: Continuation only works for a rather small number of entities. It is assumed that a large
 * number of entities will not be searched through by human editors, and that bots cannot search
 * through them anyway.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig < thiemo.maettig@wikimedia.de >
 */
class SearchEntities extends ApiBase {

	/**
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		//TODO: provide a mechanism to override the services
		$this->titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();

		$entitySearcher = new EntitiesByTermSearcher( ApiBase::LIMIT_SML1 );

		list( $entries, $nextContinuation ) = $entitySearcher->search( $params );

		$this->buildResults( $params, $entries, $nextContinuation );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @param array $params
	 * @param array $entries
	 * @param mixed $nextContinuation
	 */
	private function buildResults( array $params, array $entries, $nextContinuation ) {
		$this->getResult()->addValue(
			null,
			'search',
			$entries
		);

		$this->getResult()->setIndexedTagName_internal( array( 'search' ), 'entity' );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'search' => $params['search']
			)
		);

		if ( $nextContinuation ) {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$nextContinuation
			);
		}

		// @todo use result builder?
		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		return array(
			'search' => array(
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
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 7,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_SML1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_SML2,
				ApiBase::PARAM_MIN => 0,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => null,
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		return array(
			'search' => 'Search for this text.',
			'language' => 'Search in this language.',
			'type' => 'Search for this type of entity.',
			'limit' => 'Maximal number of results',
			'continue' => 'Offset where to continue a search',
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module to search for entities.'
		);
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array() );
	}

	/**
	 * @see \ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsearchentities&search=abc&language=en' => 'Search for "abc" in English language, with defaults for type and limit',
			'api.php?action=wbsearchentities&search=abc&language=en&limit=50' => 'Search for "abc" in English language with a limit of 50',
			'api.php?action=wbsearchentities&search=alphabet&language=en&type=property' => 'Search for "alphabet" in English language for type property',
		);
	}

	/**
	 * @see \ApiBase::getVersion
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
