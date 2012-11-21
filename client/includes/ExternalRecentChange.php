<?php

namespace Wikibase;

class ExternalRecentChange {

	public $mAttribs = array();

	/**
	 * @param array $attribs
	 * @param \Title $title
	 *
	 * @return ExternalRecentChange
	 */
	public static function newFromAttribs( $attribs, $title ) {
		$rc = new ExternalRecentChange;
		$rc->buildAttributes( $attribs, $title );
		return $rc;
	}

	/**
	 * @param array @attribs
	 * @param \Title $title
	 */
	protected function buildAttributes( $attribs, $title ) {
		$changeInfo = $attribs['wikibase-repo-change'];

		$isBot = false;
		if ( array_key_exists( 'bot', $changeInfo ) ) {
			$isBot = $changeInfo['bot'];
		}

		// compatibility
		if ( array_key_exists( 'user_text', $changeInfo ) ) {
			$userText = $changeInfo['user_text'];
		} else {
			$userText = $changeInfo['rc_user_text'];
		}

		if ( array_key_exists( 'page_id', $changeInfo ) ) {
			$pageId = $changeInfo['page_id'];
		} else {
			$pageId = $changeInfo['rc_curid'];
		}

		$this->mAttribs = array(
			'rc_namespace' => $title->getNamespace(),
			'rc_title' => $title->getDBkey(),
			'rc_user' => \User::newFromId( 0 ),
			'rc_user_text' => $userText,
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => $isBot,
			'rc_old_len' => $title->getLength(),
			'rc_new_len' => $title->getLength(),
			'rc_this_oldid' => $title->getLatestRevID(),
			'rc_last_oldid' => $title->getLatestRevID(),
			'rc_params' => serialize( $attribs ),
			'rc_cur_id' => $pageId,
			'rc_comment' => '',
			'rc_timestamp' => $changeInfo['time'],
			'rc_cur_time' => $changeInfo['time'],
			'rc_log_action' => ''
		);
	}

	/**
	 * @return bool
	 */
	public function save() {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->insert( 'recentchanges', $this->mAttribs, __METHOD__ );
		return $res;
	}

}
