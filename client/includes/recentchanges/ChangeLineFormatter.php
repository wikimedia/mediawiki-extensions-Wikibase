<?php

namespace Wikibase;

use Exception;
use ChangesList;
use Linker;
use RecentChange;
use User;
use \Wikibase\Client\WikibaseClient;

/**
 * Formats a changes line for including changes from the Wikibase repo in
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeLineFormatter {

	/**
	 * @var ChangesList
	 */
	protected $changesList;

	/**
	 * @var RepoLinker
	 */
	protected $repoLinker;

	protected $siteLocalId;

	public function __construct( ChangesList $changesList, $siteLocalId,
		RepoLinker $repoLinker = null ) {

		$this->changesList = $changesList;

		$this->repoLinker = ( $repoLinker !== null )
			? $repoLinker : WikibaseClient::getDefaultInstance()->newRepoLinker();

		$this->siteLocalId = $siteLocalId;
	}

	/**
	 * Generates a recent change line
	 *
	 * @since 0.2
	 *
	 * @param RecentChange $rc
	 *
	 * @return string
	 */
	public function format( RecentChange $rc ) {
		$changeUnserializer = new ExternalChangeUnserializer( $this->repoLinker );

		try {
			$externalChange = $changeUnserializer->unserialize( $rc );
		} catch ( Exception $e ) {
			// skip formatting
			return false;
		}

		$changeType = $externalChange->getChangeType();
		$entityTitle = $externalChange->getEntityTitle();

		$line = '';

		if ( in_array( $changeType, array( 'remove', 'restore' ) ) ) {
			$deletionLog = $this->repoLinker->repoLink(
				'Special:Log/delete',
				wfMessage( 'dellogpage' )->text()
			);

			$line .= wfMessage( 'parentheses' )->rawParams( $deletionLog );
		} else {
			$pageId = $externalChange->getPageId();
			$revId = $externalChange->getRevId();
			$parentId = $externalChange->getParentId();

			// build a diff link
			$diffLink = $this->diffLink( $entityTitle, $pageId, $revId, $parentId, $rc->counter );

			// build history link
			$historyLink = $this->historyLink( $entityTitle, $pageId );

			$line .= wfMessage( 'parentheses' )->rawParams(
				$this->changesList->getLanguage()->pipeList( array( $diffLink, $historyLink ) )
			)->text();
		}

		$line .= $this->changeSeparator();
		$line .= $this->changesList->recentChangesFlags( array( 'wikibase-edit' => true ), '' ) . ' ';
		$line .= Linker::link( $externalChange->getTitle() );

		if ( in_array( $changeType, array( 'add', 'restore', 'update' ) ) ) {
			$entityLink = $this->entityLink(
				$externalChange->getEntityTitle(),
				$externalChange->getEntityId()
			);

			$line .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams( $entityLink )->text();
		}

		$line .= $this->getTimestamp( $this->changesList, $rc );
		$line .= $this->userLinks( $this->changesList, $externalChange->getUserName() );
		$line .= $externalChange->getComment();

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
	 * @param $rc RecentChange
	 */
	public function getTimestamp( $cl, $rc ) {
		return wfMessage( 'semicolon-separator' )->text() . '<span class="mw-changeslist-date">' .
			$cl->getLanguage()->userTime( $rc->mAttribs['rc_timestamp'], $cl->getUser() )
			. '</span> <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @since 0.3
	 *
	 * @param string $titleText
	 * @param RecentChange $rc
	 *
	 * @return string
	 */
	protected function diffLink( $titleText, $pageId, $revId, $parentId, $counter ) {
		return $this->repoLinker->repoLink(
			null,
			wfMessage( 'diff' )->text(),
			array(
				'class' => 'plainlinks',
				'tabindex' => $counter,
				'query' => array(
					'type' => 'index',
					'params' => array(
						'title' => $titleText,
						'curid' => $pageId,
						'diff' => $revId,
						'oldid' => $parentId
					)
				)
			)
		);
	}

	/**
	 * @since 0.2
	 *
	 * @param string $titleText
	 *
	 * @return string
	 */
	protected function historyLink( $titleText, $pageId ) {
		$link = $this->repoLinker->repoLink(
			null,
			wfMessage( 'hist' )->text(),
			array(
				'class' => 'plainlinks',
				'query' => array(
					'type' => 'index',
					'params' => array(
						'title' => $titleText,
						'curid' => $pageId,
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
	protected function wikiLink( $page, $siteLang ) {
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
	protected function userLink( $userName ) {
		// @todo: localise this once namespaces are localised on the repo
		$link = "User:$userName";
		$attribs = array(
			'class' => 'mw-userlink'
		);
		return $this->repoLinker->repoLink( $link, $userName, $attribs );
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

		return $this->repoLinker->repoLink( $link, $text );
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

		return $this->repoLinker->repoLink( $link, $text );
	}

	/**
	 * @since 0.3
	 *
	 * @param ChangesList $cl
	 * @param string $userName
	 *
	 * @return string
	 */
	public function userLinks( $cl, $userName ) {
		if ( User::isIP( $userName ) ) {
			$userlinks = $this->userContribsLink( $userName, $userName );
			$userlinks .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams(
					$this->userTalkLink( $userName )
				)->text();
		} else {
			$userlinks = $this->userLink( $userName );
			$usertools = array(
				$this->userTalkLink( $userName ),
				$this->userContribsLink( $userName )
			);

			$userlinks .= wfMessage( 'word-separator' )->plain()
				. '<span class="mw-usertoollinks">'
				. wfMessage( 'parentheses' )->rawParams(
					$cl->getLanguage()->pipeList( $usertools )
				)->text()
				. '</span>';
		}
		return $userlinks;
	}

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function entityLink( $entityText, $entityId ) {
		return $this->repoLinker->repoLink(
			$entityText,
			$entityId,
			array( 'class' => 'wb-entity-link' )
		);
	}

}
