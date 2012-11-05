<?php

namespace Wikibase;
use RecentChange;

class ExternalRecentChange {

	/**
	 * @param $timestamp
	 * @param $title
	 * @param $user
	 * @param $actionComment
	 * @param $ip
	 * @param $type
	 * @param $action
	 * @param $target
	 * @param $logComment
	 * @param $params
	 * @param int $newId
	 * @param string $actionCommentIRC
	 *
	 * TODO: simplify creating entries for external changes
	 * @return \RecentChange
	 */
	public static function newExternalLogEntry( $timestamp, &$title, &$user, $actionComment, $ip,
		$type, $action, $target, $logComment, $params, $newId=0, $actionCommentIRC='' ) {

		$rc = RecentChange::newLogEntry( $timestamp, &$title, &$user, $actionComment, $ip,
			$type, $action, $target, $logComment, $params, $newId=0, $actionCommentIRC='' );

		return $rc;
	}

}
