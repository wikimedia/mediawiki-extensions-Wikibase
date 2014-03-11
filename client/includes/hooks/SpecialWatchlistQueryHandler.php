<?php

namespace Wikibase\Client\Hooks;

use DatabaseBase;
use User;
use WebRequest;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialWatchlistQueryHandler {

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
	private $rcTypeLogCondition;

	/**
	 * @param User $user
	 * @param DatabaseBase $db
	 */
	public function __construct( User $user, DatabaseBase $db ) {
		$this->user = $user;
		$this->db = $db;
	}

	/**
	 * @param WebRequest $request
	 * @param array $conds
	 * @param FormOptions $opts
	 *
	 * @return array
	 */
	public function addWikibaseConditions( WebRequest $request, array $conds, $opts ) {
		$hideWikibase = $opts->getValue( 'hideWikibase');

		// do not include wikibase changes for activated enhanced watchlist
		// since we do not support that format yet
		if ( $this->isEnhancedChangesEnabled( $request ) === true || $hideWikibase === true ) {
			$newConds = $this->makeHideWikibaseConds( $conds );
		} else {
			$newConds = $this->makeShowWikibaseConds( $conds );
		}

		return $newConds;
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

		foreach( $conds as $key => $cond ) {
			if ( $this->isRcTypeLogCondition( $cond ) ) {
				$newConds[$key] = $this->makeShowLogAndWikibaseType();
			} else {
				$newConds[$key] = $cond;
			}
		}

		return $newConds;
	}

	/**
	 * @return boolean
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
	 * @param WebRequest $request
	 */
	private function isEnhancedChangesEnabled( WebRequest $request ) {
		return $request->getBool( 'enhanced', $this->user->getOption( 'usenewrc' ) ) === true;
	}

}
