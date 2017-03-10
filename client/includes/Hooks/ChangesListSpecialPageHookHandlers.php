<?php

namespace Wikibase\Client\Hooks;

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
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageFilters
	 *
	 * @param ChangesListSpecialPage $specialPage
	 * @param array &$filters
	 *
	 * @return bool
	 */
	public static function onChangesListSpecialPageFilters(
		ChangesListSpecialPage $specialPage,
		array &$filters
	) {
		$hookHandler = self::getInstance(
			$specialPage->getContext(),
			$specialPage->getName()
		);

		$hookHandler->addFilterIfEnabled( $filters );

		return true;
	}

	/**
	 * Modifies watchlist and recent changes query to include external changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageQuery
	 *
	 * @param string $specialPageName
	 * @param array &$tables
	 * @param array &$fields
	 * @param array &$conds
	 * @param array &$query_options
	 * @param array &$join_conds
	 * @param FormOptions $opts
	 *
	 * @return bool
	 */
	public static function onChangesListSpecialPageQuery(
		$specialPageName,
		array &$tables,
		array &$fields,
		array &$conds,
		array &$query_options,
		array &$join_conds,
		FormOptions $opts
	) {
		$hookHandler = self::getInstance(
			RequestContext::getMain(),
			$specialPageName
		);

		$conds = $hookHandler->addWikibaseConditions( $conds, $opts );

		return true;
	}

	/**
	 * @param array &$filters
	 */
	public function addFilterIfEnabled( array &$filters ) {
		if ( $this->hasWikibaseChangesEnabled() ) {
			$filterName = $this->getFilterName();

			// the toggle needs to be the inverse to invoke the inverse display status.
			// e.g. if Wikibase changes currently hidden, then when the user
			// clicks the toggle, then Wikibase changes are displayed.
			$filters[$filterName] = array(
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => !$this->hasWikibaseChangesDisplayed()
			);
		}
	}

	/**
	 * @param array &$conds
	 * @param FormOptions $opts
	 *
	 * @return array
	 */
	public function addWikibaseConditions( array &$conds, FormOptions $opts ) {
		if ( $this->shouldHideWikibaseChanges( $opts ) ) {
			$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
			$conds[] = 'rc_source != ' . $dbr->addQuotes( RecentChangeFactory::SRC_WIKIBASE );
			$this->loadBalancer->reuseConnection( $dbr );
		}

		return $conds;
	}

	/**
	 * @param FormOptions $opts
	 *
	 * @return boolean
	 */
	private function shouldHideWikibaseChanges( FormOptions $opts ) {
		if ( !$this->hasWikibaseChangesEnabled() ) {
			return true;
		}

		$filterName = $this->getFilterName();

		if ( !$opts->offsetExists( $filterName ) ) {
			return true;
		}

		return $opts->getValue( $filterName ) === true;
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
	private function hasWikibaseChangesDisplayed() {
		if ( $this->request->getVal( 'action' ) === 'submit' ) {
			return !$this->request->getBool( $this->getFilterName() );
		}

		// if preference enabled, then Wikibase edits are included by default and
		// the toggle default value needs to be the inverse to hide them, and vice versa.
		return $this->hasShowWikibaseEditsPrefEnabled();
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
