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

		$bot = false;
		if ( array_key_exists( 'rc_bot', $changeInfo ) ) {
			$bot = $changeInfo['rc_bot'];
		}

        $this->mAttribs = array(
			'rc_namespace'  => $title->getNamespace(),
			'rc_title'      => $title->getDBkey(),
			'rc_user' => \User::newFromId( 0 ),
			'rc_user_text' => $changeInfo['rc_user_text'],
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => $bot,
			'rc_old_len' => $title->getLength(),
			'rc_new_len' => $title->getLength(),
			'rc_this_oldid' => $title->getLatestRevID(),
			'rc_last_oldid' => $title->getLatestRevID(),
			'rc_params' => serialize( $attribs ),
			'rc_cur_id' => $changeInfo['rc_curid'],
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
