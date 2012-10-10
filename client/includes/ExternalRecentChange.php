<?php

namespace Wikibase;
use RecentChange;

class ExternalRecentChange {

	/**
	 * @param $title
	 * @param $rcdata = array(
	 * 	'timestamp' => $timestamp,
	 * );
	 *
	 * TODO: simplify creating entries for external changes
	 */
	public static function newExternalLogEntry( $timestamp, &$title, &$user, $actionComment, $ip,
		$type, $action, $target, $logComment, $params, $newId=0, $actionCommentIRC='' ) {

		$rc = RecentChange::newLogEntry( $timestamp, &$title, &$user, $actionComment, $ip,
	         $type, $action, $target, $logComment, $params, $newId=0, $actionCommentIRC='' );

		return $rc;
	}

}
