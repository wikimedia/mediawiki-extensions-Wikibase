<?php

namespace Wikibase;

/**
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

	protected $cl;

	protected $rc;

	/**
	 * Construct a new ExternalChangesLine object
	 *
	 * @since 0.4
	 *
	 * @param \IContextSource
	 * @param \RecentChange
	 */
	public function __construct( $context, $rc ) {
		$this->cl = \ChangesList::newFromContext( $context );
		$this->rc = $rc;
	}

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
	public function generate() {
		$userName = $this->rc->getAttribute( 'rc_user_text' );

		$params = unserialize( $this->rc->getAttribute( 'rc_params' ) );
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

		$entityTitle = $this->titleTextFromEntityData( $entityData );

		if ( $entityTitle === false ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Invalid entity data in external change.' );
			return false;
		}

		$line = '';

		if ( in_array( $changeType, array( 'remove', 'restore' ) ) ) {
			$deletionLog = ClientUtils::repoLink( 'Special:Log/delete', wfMessage( 'dellogpage' )->text() );
			$line .= wfMessage( 'parentheses' )->rawParams( $deletionLog );
		} else if ( in_array( $changeType, array( 'add', 'update' ) ) ) {

			if ( !array_key_exists( 'page_id', $entityData ) || !array_key_exists( 'rev_id', $entityData ) ||
				!array_key_exists( 'parent_id', $entityData ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': Missing Wikibase recent change parameters, page_id, parent_id and/or rev_id.' );
				return false;
			}

			// build a diff link
			$diffLink = $this->diffLink( $entityTitle, $entityData, $this->rc );

			// build history link
			$historyLink = $this->historyLink( $entityTitle, $entityData );

			$line .= wfMessage( 'parentheses' )->rawParams(
				$this->cl->getLanguage()->pipeList( array( $diffLink, $historyLink ) ) )->text();
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Invalid Wikibase change type.' );
			return false;
		}

		$line .= $this->changeSeparator();

		$line .= \Linker::link( \Title::newFromText( $this->rc->getAttribute( 'rc_title' ) ) );

		if ( in_array( $changeType, array( 'add', 'restore', 'update' ) ) ) {
			$entityLink = $this->entityLink( $entityData );
			if ( $entityLink !== false ) {
				$line .= wfMessage( 'word-separator' )->plain()
				 . wfMessage( 'parentheses' )->rawParams( $this->entityLink( $entityData ) )->text();
			}
		}

		$line .= $this->cl->getTimestamp( $this->rc );
		$line .= $this->userLinks( $this->cl, $userName );
		$line .= $this->getComment( $entityData );

		return $line;
	}

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function changeSeparator() {
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
	public function parseComment( $entityData ) {
		$comment = $entityData['comment'];
		$message = null;
		$param = null;

		if ( is_array( $comment ) ) {
			if ( $entityData['type'] === 'wikibase-item~add' ) {
				$message = wfMessage( 'wbc-comment-linked' )->text();
			} else if ( array_key_exists( 'sitelink', $comment ) ) {
				$sitelinks = $comment['sitelink'];
				if ( array_key_exists( 'oldlink', $sitelinks ) && array_key_exists( 'newlink', $sitelinks ) ) {
					$oldLink = $this->wikiLink( $sitelinks['oldlink']['lang'], $sitelinks['oldlink']['page'] );
					$newLink = $this->wikiLink( $sitelinks['newlink']['lang'], $sitelinks['newlink']['page'] );
					$param = array( $oldLink, $newLink );
				} else if ( array_key_exists( 'oldlink', $sitelinks ) ) {
					$param = $this->wikiLink( $sitelinks['oldlink']['lang'], $sitelinks['oldlink']['page'] );
				} else if ( array_key_exists( 'newlink', $sitelinks ) ) {
					$param = $this->wikiLink( $sitelinks['newlink']['lang'], $sitelinks['newlink']['page'] );
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
	public function getComment( $entityData ) {
		if ( array_key_exists( 'comment', $entityData  ) ) {
			$commentText = $this->parseComment( $entityData );
		} else {
			$commentText = '';
		}
		return \Linker::commentBlock( $commentText );
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
	protected function diffLink( $titleText, $entityData, $rc ) {
		return ClientUtils::repoLink(
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
	protected function historyLink( $titleText, $entityData ) {
		$link = ClientUtils::repoLink(
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
	 * @since 0.3
	 *
	 * @param string $siteLang
	 * @param string $page
	 *
	 * @return string
	 */
	protected function wikiLink( $siteLang, $page ) {
		return "[[:$siteLang:$page|$siteLang:$page]]";
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	protected function userLink( $userName ) {
		// @todo: localise this once namespaces are localised on the repo
		$link = "User:$userName";
		$attribs = array(
			 'class' => 'mw-userlink'
		);
		return ClientUtils::repoLink( $link, $userName, $attribs );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 * @param string $text
	 *
	 * @return string
	 */
	protected function userContribsLink( $userName, $text = null ) {
		// @todo: know how the repo is localised. it's english now
		// for namespaces and special pages
		$link = "Special:Contributions/$userName";
		if ( $text === null ) {
			$text = wfMessage( 'contribslink' );
		}
		return ClientUtils::repoLink( $link, $text );
	}

	/**
	 * @since 0.2
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	protected function userTalkLink( $userName ) {
		// @todo: localize this once we can localize namespaces on the repo
		$link = "User_talk:$userName";
		$text = wfMessage( 'talkpagelinktext' )->text();
		return ClientUtils::repoLink( $link, $text );
	}

	/**
	 * @since 0.3
	 *
	 * @param \ChangesList $cl
	 * @param string $userName
	 *
	 * @return string
	 */
	public function userLinks( $cl, $userName ) {
		if ( \User::isIP( $userName ) ) {
			$userlinks = $this->userContribsLink( $userName, $userName );
			$userlinks .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams( $this->userTalkLink( $userName ) )->text();
		} else {
			$userlinks = $this->userLink( $userName );
			$usertools = array(
				$this->userTalkLink( $userName ),
				$this->userContribsLink( $userName )
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
	protected function entityLink( $entityData ) {
		$entityText = $this->titleTextFromEntityData( $entityData );
		$entityId = $this->titleTextFromEntityData( $entityData, false );

		if ( $entityText === false ) {
			return false;
		}

		return ClientUtils::repoLink( $entityText, $entityId, array( 'class' => 'wb-entity-link' ) );
	}

	/**
	 * TODO: returning a string as namespace like this is odd.
	 * Returning the namespace ID would make more sense.
	 * If the result of this is not handled to a Title object
	 * we miss out on proper localization and stuff.
	 *
	 * @since 0.2
	 *
	 * @param array $entityData
	 *
	 * @return string
	 */
	protected function getNamespace( $entityData ) {
		$nsList = Settings::get( 'repoNamespaces' );
		$ns = null;

		switch( $entityData['entity_type'] ) {
			case 'item':
				$ns = $nsList['wikibase-item'];
				break;
			case 'property':
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
	 * @param bool $includeNamespace include namespace in title, such as Item:Q1
	 *
	 * @return string|bool
	 */
	protected function titleTextFromEntityData( $entityData, $includeNamespace = true ) {
		if ( isset( $entityData['object_id'] ) ) {
			$id = $entityData['object_id'];

			if ( ctype_digit( $id ) || is_numeric( $id ) ) {
				// FIXME: this is evil; we seem to have lost all encapsulation at this point,
				// so some refactoring is needed to have sane access to the info here.
				$entityType = explode( '-', $entityData['entity_type'], 2 );

				$entityId = new EntityId( $entityType, (int)$id );
			}
			else {
				$entityId = EntityId::newFromPrefixedId( $id );
			}

			// TODO: ideally the uppercasing would be handled by a Title object
			$titleText = $entityId ? strtoupper( $entityId->getPrefixedId() ) : $id;

			if ( $includeNamespace ) {
				$ns = $this->getNamespace( $entityData );
				$titleText = $ns . $titleText;
			}

			return $titleText;
		}

		return false;
	}
}
