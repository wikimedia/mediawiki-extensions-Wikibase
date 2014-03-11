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
	 * @param array|FormOptions $opts
	 *
	 * @return array
	 */
	public function handleAddWikibaseConditions( WebRequest $request, array $conds, $opts ) {
		// Don't act on activated enhanced watchlist
		if ( !$this->isEnhancedChangesEnabled( $request ) && $opts['hideWikibase'] === false ) {
			$conds = $this->getShowWikibaseConditions( $conds );
		} else {
			$conds[] = $this->getHideRcExternalCond();
		}

		return $conds;
	}

	/**
	 * @param array $conds
	 */
	private function getShowWikibaseConditions( array $conds ) {
		$newConds = array();

		foreach( $conds as $key => $condition ) {
			$newConds[$key] = $this->buildNewCondition( $condition );
		}

		return $newConds;
	}

	/**
	 * @param array $types
	 *
	 * @return string
	 */
	private function getLatestOrTypesCond( array $types ) {
		$cond = $this->db->makeList(
			array(
				'rc_this_oldid=page_latest',
				'rc_type' => $types
			),
			LIST_OR
		);

		return $cond;
	}

	/**
	 * @param string $condition
	 *
	 * @return string
	 */
	private function buildNewCondition( $condition ) {
		$logOrLatestCond = $this->getLatestOrTypesCond( array( RC_LOG ) );

		if ( $condition === $logOrLatestCond ) {
			return $this->getLatestOrTypesCond( array( RC_LOG, RC_EXTERNAL ) );
		} else {
			return $condition;
		}
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
