<?php

namespace Wikibase;

// TODO: watched pages should be bold in RC
// TODO: Pages which have been changed since you last visited them are shown in bold

class ExternalChangesList {

	/**
	 * Generates a recent change line
	 *
	 * @since 0.2
	 *
	 * @param \OldChangesList $cl
	 * @param \RecentChange $rc
	 *
	 * @return string
	 */
	public static function changesLine( &$cl, $rc ) {
		$repoBase = Settings::get( 'repoBase' );
		$userName = $rc->getAttribute( 'rc_user_text' );

		$params = unserialize( $rc->getAttribute( 'rc_params' ) );
		$rcInfo = $params['rc-external-data'];
		$entityData = $params['wikibase-repo-change'];
		$entityTitle = self::titleTextFromEntityData( $entityData );

		$repoIndex = str_replace( 'api.php', 'index.php', Settings::get( 'repoApi' ) );

		// build a diff link from an RC
		$diffParams = array(
			'title' => $entityTitle,
			'curid' => $rcInfo['rc_curid'],
			'diff' => $rcInfo['rc_this_oldid'],
			'oldid' => $rcInfo['rc_last_oldid']
		);

		$diffQuery = wfArrayToCgi( $diffParams );
		$diffUrl = $repoIndex . '?' . $diffQuery;
		$diffLink = self::diffLink(
			$diffUrl,
			$cl->msg( 'diff' )->escaped(),
			array(
				'class' => 'plainlinks',
				'tabindex' => $rc->counter
			)
		);

		$line = '';
		$line .= '(' . $diffLink . ' | ';

		$historyQuery = wfArrayToCgi( array(
			'title' => $entityTitle,
			'curid' => $rcInfo['rc_curid'],
			'action' => 'history'
		) );
		$historyUrl = $repoIndex . '?' . $historyQuery;

		$line .= self::historyLink(
			$historyUrl,
			$cl->msg( 'hist' )->escaped(),
			array(
				'class' => 'plainlinks'
			)
		);

		$line .= ')';
		$line .= self::changeSeparator();

		$line .= \Linker::link( \Title::newFromText( $rc->getAttribute( 'rc_title' ) ) );
		$line .= ' (' . self::entityLink( $entityData )  . ')';
		$line .= $cl->insertTimestamp( $line, $rc );

		if ( \User::isIP( $userName ) ) {
			$userlinks = self::userContribsLink( $userName, $userName );
			$userlinks .= " (";
			$userlinks .= self::userTalkLink( $userName );
			$userlinks .= ")";
		} else {
			$userlinks = self::userLink( $userName );
			$userlinks .= " (";
			$userlinks .= self::userTalkLink( $userName );
			$userlinks .= " | ";
			// TODO: localize
			$userlinks .= self::userContribsLink( $userName, 'contribs' );
			$userlinks .= ")";
		}

		$line .= $userlinks;

		$comment = " (" . $rc->getAttribute( 'rc_comment' ) . ")";

		$line .= $comment;
		$line .= "</li>";

		return $line;
	}

	public static function changeSeparator() {
		return ' <span class="mw-changeslist-separator">. .</span> ';
	}

	public static function diffLink( $url, $text, $attribs = array() ) {
		// build a diff link from an RC
		$attribs['href'] = $url;
		return \Html::rawElement( 'a', $attribs, $text );
	}

	public static function historyLink( $url, $text, $attribs = array() ) {
		$attribs['href'] = $url;
		return \Html::rawElement( 'a', $attribs, $text );
	}

	public static function repoLink( $target, $text, $attribs = array() ) {
		$baseUrl = Settings::get( 'repoBase' );
		$baseUrl = rtrim( $baseUrl, '/' );
		$url = $baseUrl . '/' . htmlspecialchars( $target );
		$class = 'plainlinks';
		if ( array_key_exists( 'class', $attribs ) ) {
			$class .= ' ' . $attribs['class'];
		}

		$attribs['class'] = $class;
		$attribs['href'] = $url;

		return \Html::rawElement( 'a', $attribs, $text );
	}

	public static function userLink( $userName ) {
		$link = "User:$userName";
		return self::repoLink( $link, $userName );
	}

	public static function userContribsLink( $userName, $text ) {
		$link = "Special:Contributions/$userName";
		return self::repoLink( $link, $text );
	}

	public static function userTalkLink( $userName ) {
		$link = "User_talk:$userName";
		return self::repoLink( $link, 'talk' );
	}

	public static function entityLink( $entityData ) {
		$entityText = self::titleTextFromEntityData( $entityData );
		return self::repoLink( $entityText, $entityText, array( 'class' => 'wb-entity-link' ) );
	}

	public static function titleTextFromEntityData( $entityData ) {
		$prefix = null;
		$id = $entityData['object_id'];
		if ( $entityData['entity_type'] == 'wikibase-item' ) {
			// TODO: work for all types of entities, etc.
			// TODO: do not hardcode the prefix!
			$prefix = 'Q';
		}

		// TODO: $id is valid? what do do with links to deleted items?
		if ( ( $prefix !== null ) && ( isset( $id ) ) ) {
			return $prefix . $id;
		}

		return false;
	}
}
