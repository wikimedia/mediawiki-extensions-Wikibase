<?php

namespace Wikibase;

use \Wikibase\Client\WikibaseClient;

/**
 * @todo remove static stuff and refactor
 *
 * Generates a changes line for including changes from the Wikibase repo in
 * the client's recent changes, watchlist and related changes special pages.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChangesLine {

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
		$entityData = $params['wikibase-repo-change'];

		if ( !is_array( $entityData ) ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Wikibase data missing in recent changes.' );
			return false;
		}

		if ( array_key_exists( 'type', $entityData ) ) {
			$parts = explode( '~', $entityData['type'] );
			$changeType = $parts[1];
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Wikibase change type missing.' );
			return false;
		}

		$entityTitle = self::titleTextFromEntityData( $entityData );

		if ( $entityTitle === false ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Invalid entity data in external change.' );
			return false;
		}

		$line = '';

		if ( in_array( $changeType, array( 'remove', 'restore' ) ) ) {
			$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

			$deletionLog = $repoLinker->repoLink( 'Special:Log/delete', wfMessage( 'dellogpage' )->text() );
			$line .= wfMessage( 'parentheses' )->rawParams( $deletionLog );
		} else if ( in_array( $changeType, array( 'add', 'update' ) ) ) {

			if ( !array_key_exists( 'page_id', $entityData ) || !array_key_exists( 'rev_id', $entityData ) ||
				!array_key_exists( 'parent_id', $entityData ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': Missing Wikibase recent change parameters, page_id, parent_id and/or rev_id.' );
				return false;
			}

			// build a diff link
			$diffLink = self::diffLink( $entityTitle, $entityData, $rc );

			// build history link
			$historyLink = self::historyLink( $entityTitle, $entityData );

			$line .= wfMessage( 'parentheses' )->rawParams(
				$cl->getLanguage()->pipeList( array( $diffLink, $historyLink ) ) )->text();
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Invalid Wikibase change type.' );
			return false;
		}

		$line .= self::changeSeparator();

		$title = $rc->getTitle();
		$line .= \Linker::link( $title );

		if ( in_array( $changeType, array( 'add', 'restore', 'update' ) ) ) {
			$entityLink = self::entityLink( $entityData );
			if ( $entityLink !== false ) {
				$line .= wfMessage( 'word-separator' )->plain()
				 . wfMessage( 'parentheses' )->rawParams( self::entityLink( $entityData ) )->text();
			}
		}

		$line .= self::getTimestamp( $cl, $rc );
		$line .= self::userLinks( $cl, $userName );
		$line .= self::getComment( $entityData );

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
	 * @since 0.3
	 *
	 * @todo incorporate this logic in the change processing; store the
	 * message key and param in rc_params instead of here
	 *
	 * @param string $comment
	 *
	 * @return string
	 */
	public static function parseComment( $entityData ) {
		$comment = $entityData['comment'];
		$message = null;
		$param = null;

		if ( is_array( $comment ) ) {
			if ( $entityData['type'] === 'wikibase-item~add' ) {
				// @todo: provide a link to the entity
				$message = wfMessage( 'wikibase-comment-linked' )->text();
			} else if ( array_key_exists( 'sitelink', $comment ) ) {
				$sitelinks = $comment['sitelink'];
				if ( array_key_exists( 'oldlink', $sitelinks ) && array_key_exists( 'newlink', $sitelinks ) ) {
					$oldLink = self::wikiLink( $sitelinks['oldlink']['page'], $sitelinks['oldlink']['lang'] );
					$newLink = self::wikiLink( $sitelinks['newlink']['page'], $sitelinks['newlink']['lang'] );
					$param = array( $oldLink, $newLink );
				} else if ( array_key_exists( 'oldlink', $sitelinks ) ) {
					$param = self::wikiLink( $sitelinks['oldlink']['page'], $sitelinks['oldlink']['lang'] );
				} else if ( array_key_exists( 'newlink', $sitelinks ) ) {
					$param = self::wikiLink( $sitelinks['newlink']['page'], $sitelinks['newlink']['lang'] );
				}

				if ( $param !== null ) {
					if ( is_array( $param ) ) {
						$message = wfMessage( $comment['message'] )->rawParams( $param[0], $param[1] )->parse();
					} else {
						$message = wfMessage( $comment['message'] )->rawParams( $param )->parse();
					}
				}
			}

			if ( $message === null ) {
				$message = wfMessage( $comment['message'] )->text();
			}
		} else {
			$msg = wfMessage( $comment );
			$message = $msg->exists() ? $msg->text() : $comment;
		}

		return $message;
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 *
	 * @return string
	 */
	public static function getComment( $entityData ) {
		//TODO: If $entityData['changes'] is set, this is a coalesced change.
		//      Combine all the comments! Up to some max length?
		if ( array_key_exists( 'composite-comment', $entityData ) ) {
			$commentText = wfMessage( 'wikibase-comment-multi' )->numParams( count( $entityData['composite-comment'] ) )->text();
		} else if ( array_key_exists( 'comment', $entityData  ) ) {
			$commentText = self::parseComment( $entityData );
		} else {
			$commentText = '';
		}

		return \Linker::commentBlock( $commentText );
	}

	/**
	 * @param $rc RecentChange
	 */
	public static function getTimestamp( $cl, $rc ) {
		return wfMessage( 'semicolon-separator' )->text() . '<span class="mw-changeslist-date">' .
			$cl->getLanguage()->userTime( $rc->mAttribs['rc_timestamp'], $cl->getUser() )
			. '</span> <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @since 0.3
	 *
	 * @param string $titleText
	 * @param array $entityData
	 * @param \RecentChange $rc
	 *
	 * @return string
	 */
	protected static function diffLink( $titleText, $entityData, $rc ) {
		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

		return $repoLinker->repoLink(
			null,
			wfMessage( 'diff' )->text(),
			array(
				'class' => 'plainlinks',
				'tabindex' => $rc->counter,
				'query' => array(
					'type' => 'index',
					'params' => array(
						'title' => $titleText,
						'curid' => $entityData['page_id'],
						'diff' => $entityData['rev_id'],
						'oldid' => $entityData['parent_id']
					)
				)
			)
		);
	}

	/**
	 * @since 0.2
	 *
	 * @param string $titleText
	 * @param array $entityData
	 *
	 * @return string
	 */
	protected static function historyLink( $titleText, $entityData ) {
		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

		$link = $repoLinker->repoLink(
			null,
			wfMessage( 'hist' )->text(),
			array(
				'class' => 'plainlinks',
				'query' => array(
					'type' => 'index',
					'params' =>  array(
						'title' => $titleText,
						'curid' => $entityData['page_id'],
						'action' => 'history'
					)
				)
			)
		);
		return $link;
	}

	/**
	 * @todo use the title object here
	 *
	 * @since 0.3
	 *
	 * @param string $siteLang
	 * @param string $page
	 *
	 * @return string
	 */
	protected static function wikiLink( $page, $siteLang ) {
		$localId = Settings::get( 'siteLocalID' );
		if ( $siteLang !== null && $siteLang !== $localId ) {
			return "[[:$siteLang:$page|$siteLang:$page]]";
		} else {
			return "[[$page]]";
		}
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	protected static function userLink( $userName ) {
		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

		// @todo: localise this once namespaces are localised on the repo
		$link = "User:$userName";
		$attribs = array(
			 'class' => 'mw-userlink'
		);
		return $repoLinker->repoLink( $link, $userName, $attribs );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function userContribsLink( $userName, $text = null ) {
		// @todo: know how the repo is localised. it's english now
		// for namespaces and special pages
		$link = "Special:Contributions/$userName";
		if ( $text === null ) {
			$text = wfMessage( 'contribslink' );
		}

		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

		return $repoLinker->repoLink( $link, $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	protected static function userTalkLink( $userName ) {
		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

		// @todo: localize this once we can localize namespaces on the repo
		$link = "User_talk:$userName";
		$text = wfMessage( 'talkpagelinktext' )->text();
		return $repoLinker->repoLink( $link, $text );
	}

	/**
	 * @since 0.3
	 *
	 * @param \ChangesList $cl
	 * @param string $userName
	 *
	 * @return string
	 */
	public static function userLinks( $cl, $userName ) {
		if ( \User::isIP( $userName ) ) {
			$userlinks = self::userContribsLink( $userName, $userName );
			$userlinks .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams( self::userTalkLink( $userName ) )->text();
		} else {
			$userlinks = self::userLink( $userName );
			$usertools = array(
				self::userTalkLink( $userName ),
				self::userContribsLink( $userName )
			);

			$userlinks .= wfMessage( 'word-separator' )->plain()
				. '<span class="mw-usertoollinks">'
				. wfMessage( 'parentheses' )->rawParams( $cl->getLanguage()->pipeList( $usertools ) )->text()
				. '</span>';
		}
		return $userlinks;
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 *
	 * @return string
	 */
	protected static function entityLink( $entityData ) {
		$entityText = self::titleTextFromEntityData( $entityData );
		$entityId = self::titleTextFromEntityData( $entityData, false );

		if ( $entityText === false ) {
			return false;
		}

		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

		return $repoLinker->repoLink( $entityText, $entityId, array( 'class' => 'wb-entity-link' ) );
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 * @param bool $includeNamespace include namespace in title, such as Item:Q1
	 *
	 * @return string|bool
	 */
	protected static function titleTextFromEntityData( $entityData, $includeNamespace = true ) {
		if ( isset( $entityData['object_id'] ) ) {
			$id = $entityData['object_id'];

			if ( ctype_digit( $id ) || is_numeric( $id ) ) {
				// @deprecated
				// FIXME: this is evil; we seem to have lost all encapsulation at this point,
				// so some refactoring is needed to have sane access to the info here.
				$entityType = explode( '-', $entityData['entity_type'], 2 );

				$entityId = new EntityId( $entityType, (int)$id );
			}
			else {
				$entityId = EntityId::newFromPrefixedId( $id );
			}

			$titleText = strtoupper( $entityId->getPrefixedId() );

			if ( $includeNamespace ) {
				$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

				$ns = $repoLinker->getNamespace( $entityId->getEntityType() );
				if ( !empty( $ns ) ) {
					$titleText = $ns . ':' . $titleText;
				}
			}

			return $titleText;
		}

		return false;
	}
}
