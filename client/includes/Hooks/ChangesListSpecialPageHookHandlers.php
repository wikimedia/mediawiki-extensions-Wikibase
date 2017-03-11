<?php

namespace Wikibase\Client\Hooks;

use ChangesListBooleanFilter;
use ChangesListSpecialPage;
use FormOptions;
use IContextSource;
use MediaWiki\MediaWikiServices;
use RequestContext;
use User;
use WebRequest;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\WikibaseClient;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangesListSpecialPageHookHandlers {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var string
	 */
	private $pageName;

	/**
	 * @var bool
	 */
	private $showExternalChanges;

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @param WebRequest $request
	 * @param User $user
	 * @param LoadBalancer $loadBalancer
	 * @param string $pageName
	 * @param bool $showExternalChanges
	 */
	public function __construct(
		WebRequest $request,
		User $user,
		LoadBalancer $loadBalancer,
		$pageName,
		$showExternalChanges
	) {
		$this->request = $request;
		$this->user = $user;
		$this->loadBalancer = $loadBalancer;
		$this->pageName = $pageName;
		$this->showExternalChanges = $showExternalChanges;
	}

	/**
	 * @param IContextSource $context
	 * @param string $specialPageName
	 *
	 * @return self
	 */
	private static function newFromGlobalState(
		IContextSource $context,
		$specialPageName
	) {
		Assert::parameterType( 'string', $specialPageName, '$specialPageName' );

		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		return new self(
			$context->getRequest(),
			$context->getUser(),
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			$specialPageName,
			$settings->getSetting( 'showExternalRecentChanges' )
		);
	}

	/**
	 * @param IContextSource $context
	 * @param string $specialPageName
	 *
	 * @return self
	 */
	private static function getInstance(
		IContextSource $context,
		$specialPageName
	) {
		if ( self::$instance === null ) {
			self::$instance = self::newFromGlobalState( $context, $specialPageName );
		}

		return self::$instance;
	}

	/**
	 * Modifies recent changes and watchlist options to show a toggle for Wikibase changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageStructuredFilters
	 *
	 * @param ChangesListSpecialPage $specialPage
	 *
	 * @return bool
	 */
	public static function onChangesListSpecialPageStructuredFilters(
		ChangesListSpecialPage $specialPage
	) {
		$hookHandler = self::getInstance(
			$specialPage->getContext(),
			$specialPage->getName()
		);

		$hookHandler->addFilterIfEnabled( $specialPage );

		return true;
	}

	/**
	 * @param ChangesListSpecialPage $specialPage
	 */
	public function addFilterIfEnabled( ChangesListSpecialPage $specialPage ) {
		if ( $this->hasWikibaseChangesEnabled() ) {
			$filterName = $this->getFilterName();
			$changeTypeGroup = $specialPage->getFilterGroup( 'changeType' );
			$wikidataFilter = new ChangesListBooleanFilter( [
				'name' => $filterName,
				'group' => $changeTypeGroup,
				'priority' => -4,
				'label' => 'wikibase-rcfilters-hideWikibase-label',
				'description' => 'wikibase-rcfilters-hideWikibase-description',
				'showHide' => 'wikibase-rc-hide-wikidata',
				// If the preference is enabled, then don't hide Wikidata edits
				'default' => !$this->hasShowWikibaseEditsPrefEnabled(),
				'queryCallable' => function ( $specialClassName, $ctx, $dbr, &$tables, &$fields,
						&$conds, &$query_options, &$join_conds ) {
					$this->addWikibaseConditions( $conds );
				},
				'cssClassSuffix' => 'src-mw-wikibase',
				'isRowApplicableCallable' => function ( $ctx, $rc ) {
					return $rc->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE;
				}
			] );
			// TODO add conflict with ORES groups, somehow
			$changeTypeGroup->registerFilter( $wikidataFilter );
		}
	}

	/**
	 * @param array &$conds
	 */
	public function addWikibaseConditions( array &$conds ) {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$conds[] = 'rc_source != ' . $dbr->addQuotes( RecentChangeFactory::SRC_WIKIBASE );
		$this->loadBalancer->reuseConnection( $dbr );
	}

	/**
	 * @return bool
	 */
	private function hasWikibaseChangesEnabled() {
		// do not include wikibase changes for activated enhanced watchlist
		// since we do not support that format yet (T46222)
		return $this->showExternalChanges && !$this->isEnhancedChangesEnabled();
	}

	/**
	 * @return bool
	 */
	private function hasShowWikibaseEditsPrefEnabled() {
		return (bool)$this->user->getOption( $this->getOptionName() );
	}

	/**
	 * @return bool
	 */
	private function isEnhancedChangesEnabled() {
		$enhancedChangesUserOption = $this->user->getOption( 'usenewrc' );

		return $this->request->getBool( 'enhanced', $enhancedChangesUserOption );
	}

	/**
	 * @return string
	 */
	private function getFilterName() {
		return 'hideWikibase';
	}

	/**
	 * @return string
	 */
	private function getOptionName() {
		if ( $this->pageName === 'Watchlist' ) {
			return 'wlshowwikibase';
		}

		return 'rcshowwikidata';
	}

}
