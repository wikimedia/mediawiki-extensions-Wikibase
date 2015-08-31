<?php


namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use DataValues\Serializers\DataValueSerializer;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\Store\BadgeLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * A module to query badges by site and page.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class QueryBadges extends ApiBase {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var BadgeLookup
	 */
	private $badgeLookup;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->setServices(
			$wikibaseRepo->getApiHelperFactory( $this->getContext() )->getErrorReporter( $this ),
			$wikibaseRepo->getStore()->newBadgeStore(),
			new SiteLinkTargetProvider(
				$wikibaseRepo->getSiteStore(),
				$wikibaseRepo->getSettings()->getSetting( 'specialSiteLinkGroups' )
			),
			new SerializerFactory( new DataValueSerializer() ),
			$wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' ),
			$wikibaseRepo->getSettings()->getSetting( 'badgeItems' )
		);
	}

	public function setServices(
		ApiErrorReporter $errorReporter,
		BadgeLookup $badgeLookup,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		SerializerFactory $serializerFactory,
		array $siteLinkGroups,
		array $badgeItems
	) {
		$this->errorReporter = $errorReporter;
		$this->badgeLookup = $badgeLookup;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->serializerFactory = $serializerFactory;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->badgeItems = $badgeItems;
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.5
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$badgeId = $this->getBadgeId( $params );
		$site = isset( $params['site'] ) ? $params['site'] : null;

		$siteLinks = $this->badgeLookup->getSiteLinksForBadge( $badgeId, $site );
		$this->showResult( $siteLinks );
	}

	/**
	 * @param array $params
	 * @return ItemId
	 */
	private function getBadgeId( array $params ) {
		if ( !isset( $params['badge'] ) ) {
			$this->errorReporter->dieError( 'Missing parameter: badge', 'param-missing' );
		}

		try {
			return new ItemId( $params['badge'] );
		} catch ( InvalidArgumentException $e ) {
			$this->errorReporter->dieError( "Invalid id: {$params['badge']}", 'no-such-entity' );
		}
	}

	/**
	 * @param SiteLink[] $siteLinks
	 */
	private function showResult( array $siteLinks ) {
		$result = $this->getResult();
		$serializer = $this->serializerFactory->newSiteLinkSerializer();

		foreach ( $siteLinks as $siteLink ) {
			$result->addValue( 'pages', null, $serializer->serialize( $siteLink ) );
		}

		$result->addIndexedTagName( 'pages', 'page' );
		$result->addValue( null, 'success', 1 );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		return array_merge( parent::getAllowedParams(), array(
			'badge' => array(
				self::PARAM_TYPE => array_keys( $this->badgeItems ),
				self::PARAM_REQUIRED
			),
			'site' => array(
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			),
		) );
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			"action=wbquerybadges&badge=Q17437798"
			=> "apihelp-wbgetentities-example-1",
			"action=wbquerybadges&badge=Q17437798&site=enwiki"
			=> "apihelp-wbgetentities-example-2",
		);
	}

}
