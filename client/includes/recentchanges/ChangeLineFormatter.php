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

	/**
	 * @var string
	 */
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
	 * @param RecentChange $recentChange
	 *
	 * @return string|boolean
	 */
	public function format( RecentChange $recentChange ) {
		$changeUnserializer = new ExternalChangeUnserializer( $this->repoLinker );

		try {
			$externalChange = $changeUnserializer->unserialize( $recentChange );
		} catch ( Exception $e ) {
			echo $e->getMessage() . "\n";
			// skip formatting
			return false;
		}

		$changeType = $externalChange->getChangeType();

		$line = '';

		if ( in_array( $changeType, array( 'remove', 'restore' ) ) ) {
			$deletionLog = $this->repoLinker->repoLink(
				'Special:Log/delete',
				wfMessage( 'dellogpage' )->text()
			);

			$line .= wfMessage( 'parentheses' )->rawParams( $deletionLog );
		} else {
			$line .= $this->formatDiffHist(
				$externalChange,
				$recentChange->counter
			);
		}

		$line .= $this->changeSeparator();
		$line .= $this->changesList->recentChangesFlags( array( 'wikibase-edit' => true ), '' ) . ' ';
		$line .= Linker::link( $externalChange->getTitle() );

		if ( in_array( $changeType, array( 'add', 'restore', 'update' ) ) ) {
			$entityLink = $this->entityLink(
				$externalChange->getEntityId()
			);

			$line .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams( $entityLink )->text();
		}

		$line .= $this->formatTimestamp(
			$externalChange->getTimestamp(),
			$this->changesList->getUser()
		);

		$line .= $this->userLinks( $externalChange->getUserName() );
		$line .= $externalChange->getComment();

		return $line;
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function formatDiffHist( ExternalChange $externalChange, $counter ) {
		$entityId = $externalChange->getEntityId();
		$entityTitle = $this->formatEntityId( $entityId );

		$pageId = $externalChange->getPageId();
		$revId = $externalChange->getRevId();
		$parentId = $externalChange->getParentId();

		// build a diff link
		$diffLink = $this->diffLink( $entityTitle, $pageId, $revId, $parentId, $counter );

		// build history link
		$historyLink = $this->historyLink( $entityTitle, $pageId );

		return wfMessage( 'parentheses' )->rawParams(
			$this->changesList->getLanguage()->pipeList( array( $diffLink, $historyLink ) )
		)->text();
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
	 * @since 0.4
	 *
	 * @param string $timestamp
	 * @param User $user
	 */
	public function formatTimestamp( $timestamp, $user ) {
		return wfMessage( 'semicolon-separator' )->text()
			. '<span class="mw-changeslist-date">'
			. $this->changesList->getLanguage()->userTime( $timestamp, $user )
			. '</span> <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @since 0.3
	 *
	 * @param string $titleText
	 * @param int $pageId
	 * @param int $revId
	 * @param int $parentId
	 * @param int $counter
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
	 * @param int $pageId
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
	 * @param string $page
	 * @param string $siteLang
	 *
	 * @return string
	 */
	protected function wikiLink( $page, $siteLang ) {
		if ( $siteLang !== null && $siteLang !== $this->siteLocalId ) {
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
	 * @param string $userName
	 *
	 * @return string
	 */
	public function userLinks( $userName ) {
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
					$this->changesList->getLanguage()->pipeList( $usertools )
				)->text()
				. '</span>';
		}
		return $userlinks;
	}

	/**
	 * @since 0.2
	 *
	 * @param string $entityId
	 *
	 * @return string
	 */
	protected function entityLink( $entityId ) {
		$entityText = $this->formatEntityId( $entityId, false );
		$prefixedId = $this->formatEntityId( $entityId );

		return $this->repoLinker->repoLink(
			$entityText,
			$prefixedId,
			array( 'class' => 'wb-entity-link' )
		);
	}

	/**
	 * @since 0.2
	 *
	 * @param EntityId $entityId
	 * @param bool $includeNamespace include namespace in title, such as Item:Q1
	 *
	 * @return string|bool
	 */
	protected function formatEntityId( $entityId, $includeNamespace = true ) {
		$idFormatter = WikibaseClient::getDefaultInstance()->getEntityIdFormatter();
		$titleText = strtoupper( $idFormatter->format( $entityId ) );

		if ( $includeNamespace ) {
			$ns = $this->repoLinker->getNamespace( $entityId->getEntityType() );
			if ( !empty( $ns ) ) {
				$titleText = $ns . ':' . $titleText;
			}
		}

		return $titleText;
	}

}
