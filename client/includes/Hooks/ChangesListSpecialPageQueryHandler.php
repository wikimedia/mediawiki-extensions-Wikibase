<?php

namespace Wikibase\Client\Hooks;

use ChangesListSpecialPage;
use DatabaseBase;
use FormOptions;
use RequestContext;
use User;
use WebRequest;
use Wikibase\Client\WikibaseClient;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangesListSpecialPageQueryHandler {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var DatabaseBase
	 */
	private $db;

	/**
	 * @var string
	 */
	private $specialPageName;

	/**
	 * @var boolean
	 */
	private $showExternalChanges;

	/**
	 * @param WebRequest $request
	 * @param User $user
	 * @param DatabaseBase $db
	 * @param string $specialPageName
	 * @param boolean $showExternalChanges
	 */
	public function __construct(
		WebRequest $request,
		User $user,
		DatabaseBase $db,
		$specialPageName,
		$showExternalChanges
	) {
		$this->request = $request;
		$this->user = $user;
		$this->db = $db;
		$this->specialPageName = $specialPageName;
		$this->showExternalChanges = $showExternalChanges;
	}

	/**
	 * @param ChangesListSpecialPage $specialPage
	 *
	 * @return ChangesListSpecialPageFilterHandler
	 */
	public static function newFromGlobalState( $specialPageName ) {
		$context = RequestContext::getMain();
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		return new self(
			$context->getRequest(),
			$context->getUser(),
			wfGetDB( DB_SLAVE ),
			$specialPageName,
			$settings->getSetting( 'showExternalRecentChanges' )
		);
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
	public function onChangesListSpecialPageQuery(
		$specialPageName,
		array &$tables,
		array &$fields,
		array &$conds,
		array &$query_options,
		array &$join_conds,
		FormOptions $opts
	) {
		$hookHandler = self::newFromGlobalState( $specialPageName );
		$conds = $hookHandler->addWikibaseConditions( $conds, $opts );

		return true;
	}

	/**
	 * @param array $conds
	 * @param FormOptions|array|null $opts MediaWiki 1.22 used an array and MobileFrontend still does.
	 *
	 * @return array
	 */
	public function addWikibaseConditions( array $conds, FormOptions $opts ) {
		// do not include wikibase changes for activated enhanced watchlist
		// since we do not support that format yet
		if ( $this->shouldHideWikibaseChanges( $opts ) ) {
			return $this->makeHideWikibaseConds( $conds );
		}

		return $this->makeShowWikibaseConds( $conds );
	}

	/**
	 * @param FormOptions|array|null $opts MediaWiki 1.22 used an array and MobileFrontend still does.
	 *
	 * @return boolean
	 */
	private function shouldHideWikibaseChanges( FormOptions $opts ) {
		if ( !$this->showExternalChanges || $this->isEnhancedChangesEnabled() === true ) {
			return true;
		}

		if ( !$opts || $opts->getValue( $this->getFilterName() ) === true ) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $conds
	 *
	 * @return array
	 */
	private function makeHideWikibaseConds( array $conds ) {
		$conds[] = $this->getHideRcExternalCond();

		return $conds;
	}

	/**
	 * @param array $conds
	 *
	 * @return array
	 */
	private function makeShowWikibaseConds( array $conds ) {
		$newConds = array();

		foreach ( $conds as $key => $cond ) {
			if ( $this->isRcTypeLogCondition( $cond ) ) {
				$newConds[$key] = $this->makeShowLogAndWikibaseType();
			} else {
				$newConds[$key] = $cond;
			}
		}

		return $newConds;
	}

	/**
	 * @param string $cond
	 *
	 * @return bool
	 */
	private function isRcTypeLogCondition( $cond ) {
		return $cond === $this->getRcTypeLogCondition();
	}

	/**
	 * @return string
	 */
	private function getRcTypeLogCondition() {
		if ( !isset( $this->rcTypeLogCondition ) ) {
			$this->rcTypeLogCondition = $this->makeLatestOrTypesCond( array( RC_LOG ) );
		}

		return $this->rcTypeLogCondition;
	}

	/**
	 * @return string
	 */
	private function makeShowLogAndWikibaseType() {
		return $this->makeLatestOrTypesCond( array( RC_LOG, RC_EXTERNAL ) );
	}

	/**
	 * @param array $types
	 *
	 * @return string
	 */
	private function makeLatestOrTypesCond( array $types ) {
		$where = array(
			'rc_this_oldid=page_latest',
			'rc_type' => $types
		);

		$cond = $this->db->makeList( $where, LIST_OR );

		return $cond;
	}

	/**
	 * @return string
	 */
	private function getHideRcExternalCond() {
		return 'rc_type != ' . RC_EXTERNAL;
	}

	/**
	 * @return string
	 */
	private function getFilterName() {
		if ( $this->specialPageName === 'Watchlist' ) {
			return 'hideWikibase';
		}

		return 'hidewikidata';
	}

	/**
	 * @return boolean
	 */
	private function isEnhancedChangesEnabled() {
		$enhancedChangesUserOption = $this->user->getOption( 'usenewrc' );

		return $this->request->getBool( 'enhanced', $enhancedChangesUserOption );
	}

}
