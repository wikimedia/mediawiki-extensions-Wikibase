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

		$entityLink = self::entityLink( $entityData );
		if ( $entityLink !== false ) {
			$line .= ' (' . self::entityLink( $entityData )  . ')';
		}

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

		$parts = explode( '~', $entityData['type'] );
		$changeType = $parts[1];
		$line .= self::autoComment( $changeType );

		$line .= "</li>";

		return $line;
	}

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	protected static function changeSeparator() {
		return ' <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @since 0.2
	 *
	 * @param string $url
	 * @param string $text
	 * @param array $attribs
	 *
	 * @return string
	 */
	protected static function diffLink( $url, $text, $attribs = array() ) {
		// build a diff link from an RC
		$attribs['href'] = $url;
		return \Html::rawElement( 'a', $attribs, $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $url
	 * @param string $text
	 * @param array $attribs
	 *
	 * @return string
	 */
	protected static function historyLink( $url, $text, $attribs = array() ) {
		$attribs['href'] = $url;
		return \Html::rawElement( 'a', $attribs, $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $target
	 * @param string $text
	 * @param array $attribs
	 *
	 * @return string
	 */
	protected static function repoLink( $target, $text, $attribs = array() ) {
		$url = ClientUtils::baseUrl() . $target;
		//htmlspecialchars( $target );
		$class = 'plainlinks';
		if ( array_key_exists( 'class', $attribs ) ) {
			$class .= ' ' . $attribs['class'];
		}

		$attribs['class'] = $class;
		$attribs['href'] = $url;

		return \Html::rawElement( 'a', $attribs, $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	protected static function userLink( $userName ) {
		$link = "User:$userName";
		return self::repoLink( $link, $userName );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 * @param string $text
	 *
	 * return string
	 */
	protected static function userContribsLink( $userName, $text ) {
		$link = "Special:Contributions/$userName";
		return self::repoLink( $link, $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	protected static function userTalkLink( $userName ) {
		$link = "User_talk:$userName";
		return self::repoLink( $link, 'talk' );
	}

	/**
	 * @since 0.2
	 *
	 * @param \RecentChange $rc
	 * @param array $entityData
	 *
	 * return string
	 */
	protected static function entityLink( $entityData ) {
		$entityText = self::titleTextFromEntityData( $entityData );
		$entityId = self::titleTextFromEntityData( $entityData, false );

		if ( $entityText === false ) {
			return false;
		}

		return self::repoLink( $entityText, $entityId, array( 'class' => 'wb-entity-link' ) );
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 *
	 * @return string
	 */
	protected static function getNamespace( $entityData ) {
		$nsList = Settings::get('repoNamespaces');
		$ns = null;

		switch( $entityData['entity_type'] ) {
			case 'wikibase-item':
				$ns = $nsList['wikibase-item'];
				break;
			case 'wikibase-property':
				$ns = $nsList['wikibase-property'];
				break;
			default:
				// invalid entity type
				// todo: query data type
				return false;
		}
		if ( ! empty( $ns ) ) {
			$ns = $ns . ':';
		}
		return $ns;
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 * @param bool $namespace include namespace in title, such as Item:Q1
	 * return string
	 */
	protected static function titleTextFromEntityData( $entityData, $namespace = true ) {
		$prefix = null;
		$titleText = '';

		$id = $entityData['object_id'];
		if ( $entityData['entity_type'] == 'wikibase-item' ) {
			// TODO: work for all types of entities, etc.
			$prefix = strtoupper( Settings::get( 'itemPrefix' ) );
		}

		// TODO: $id is valid? what do do with links to deleted items?
		if ( ( $prefix !== null ) && ( isset( $id ) ) ) {
			$titleText = $prefix . $id;
		}

		if ( $namespace ) {
			$ns = self::getNamespace( $entityData );
			$titleText = $ns . $titleText;
		}

		return $titleText;
	}

	protected static function autoComment( $changeType ) {
		// todo i18n
		$comment = '';
		switch( $changeType ) {
			case 'update':
				$comment = wfMessage( 'wbc-comment-langlinks-update' )->text();
				break;
			// todo: make change types clearer
			// case 'remove':
			//	$comment = wfMessage( 'wbc-comment-langlinks-remove' )->text();
			//	break;
			case 'remove':
				$comment = wfMessage( 'wbc-comment-langlinks-delete' )->text();
				break;
			case 'restore':
				$comment = wfMessage( 'wbc-comment-langlinks-restore' )->text();
				break;
			case 'default':
				break;
		}
		return  \Linker::commentBlock( $comment );
	}
}
