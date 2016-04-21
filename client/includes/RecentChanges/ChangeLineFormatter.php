<?php

namespace Wikibase\Client\RecentChanges;

use Language;
use Linker;
use Title;
use User;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Formats a changes line for including changes from the Wikibase repo in
 * the client's recent changes, watchlist and related changes special pages.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ChangeLineFormatter {

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Language
	 */
	private $lang;

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @param User $user
	 * @param Language $lang
	 * @param RepoLinker $repoLinker
	 */
	public function __construct( User $user, Language $lang, RepoLinker $repoLinker ) {
		$this->user = $user;
		$this->lang = $lang;
		$this->repoLinker = $repoLinker;
	}

	/**
	 * @param ExternalChange $externalChange
	 * @param Title $title
	 * @param int $count
	 * @param string $flag - flag formatted by ChangesList::recentChangesFlags()
	 *
	 * @return string HTML
	 */
	public function format( ExternalChange $externalChange, Title $title, $count, $flag ) {
		$changeType = $externalChange->getChangeType();
		$rev = $externalChange->getRev();
		$entityId = $externalChange->getEntityId();

		$line = ( $changeType === 'restore' || $changeType === 'remove' )
			? $this->formatDeletionLogLink()
			: $this->formatDiffHist( $entityId, $rev, $count );

		$line .= $this->changeSeparator();
		$line .= $flag . ' ';
		$line .= Linker::link( $title );

		if ( $changeType !== 'remove' ) {
			$line .= $this->formatEntityLink( $entityId );
		}

		$line .= $this->formatTimestamp( $rev->getTimestamp() );
		$line .= $this->formatUserLinks( $rev->getUserName() );

		$commentHtml = $rev->getCommentHtml();

		if ( $commentHtml === null || $commentHtml === '' ) {
			$commentHtml = Linker::formatComment( $rev->getComment(), $title, false, $externalChange->getSiteId() );
		}

		$line .= $this->wrapCommentBlock( $commentHtml );

		return $line;
	}

	/**
	 * @param string $commentHtml Formatted comment HTML
	 *
	 * @return string Formatted comment HTML wrapped as comment block
	 */
	private function wrapCommentBlock( $commentHtml ) {
		//NOTE: keep in sync with Linker::commentBlock
		$formatted = wfMessage( 'parentheses' )->rawParams( $commentHtml )->escaped();
		return " <span class=\"comment\">$formatted</span>";
	}

	/**
	 * @return string HTML
	 */
	private function changeSeparator() {
		return ' <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @param string $timestamp
	 *
	 * @return string HTML
	 */
	private function formatTimestamp( $timestamp ) {
		return wfMessage( 'semicolon-separator' )->text()
			. '<span class="mw-changeslist-date">'
			. $this->lang->userTime( $timestamp, $this->user )
			. '</span> <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @param string $userName
	 *
	 * @return string HTML
	 */
	private function formatUserLinks( $userName ) {
		$links = $this->buildUserLinks( $userName );

		if ( User::isIP( $userName ) ) {
			return $this->formatIpUserLinks( $links );
		} else {
			return $this->formatRegisteredUserLinks( $links );
		}
	}

	/**
	 * @fixme use shared bits of formatting from ChangesList and OldChangesList
	 *
	 * @param string[] $links
	 *
	 * @return string HTML
	 */
	private function formatIpUserLinks( array $links ) {
		$userlinks = $links['contribs'];

		$userlinks .= wfMessage( 'word-separator' )->plain()
			. wfMessage( 'parentheses' )->rawParams(
				$links['usertalk']
			)->text();

		return $userlinks;
	}

	/**
	 * @fixme use shared bits of formatting from ChangesList and OldChangesList
	 *
	 * @param string[] $links
	 *
	 * @return string HTML
	 */
	private function formatRegisteredUserLinks( array $links ) {
		$userlinks = $links['user'];

		$usertools = array(
			$links['usertalk'],
			$links['contribs']
		);

		$userlinks .= wfMessage( 'word-separator' )->plain()
			. '<span class="mw-usertoollinks">'
			. wfMessage( 'parentheses' )->rawParams(
				$this->lang->pipeList( $usertools )
			)->text()
			. '</span>';

		return $userlinks;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function formatEntityLink( EntityId $entityId ) {
		$entityLink = $this->repoLinker->buildEntityLink( $entityId );

		return wfMessage( 'word-separator' )->plain()
			. wfMessage( 'parentheses' )->rawParams( $entityLink )->text();
	}

	/**
	 * @return string HTML
	 */
	private function formatDeletionLogLink() {
		$logLink = $this->repoLinker->formatLink(
			$this->repoLinker->getPageUrl( 'Special:Log/delete' ),
			wfMessage( 'dellogpage' )->text()
		);

		return wfMessage( 'parentheses' )->rawParams( $logLink )->text();
	}

	/**
	 * @param EntityId $entityId
	 * @param RevisionData $rev
	 * @param int $count
	 *
	 * @return string HTML
	 */
	private function formatDiffHist( EntityId $entityId, RevisionData $rev, $count ) {
		$diffLink = $this->buildDiffLink( $entityId, $rev, $count );
		$historyLink = $this->buildHistoryLink( $entityId, $rev );

		return wfMessage( 'parentheses' )->rawParams(
			$this->lang->pipeList( array( $diffLink, $historyLink ) )
		)->text();
	}

	/**
	 * @param EntityId $entityId
	 * @param RevisionData $rev
	 * @param int $count
	 *
	 * @return string HTML
	 */
	private function buildDiffLink( EntityId $entityId, RevisionData $rev, $count ) {
		$params = array(
			'title' => $this->repoLinker->getEntityTitle( $entityId ),
			'curid' => $rev->getPageId(),
			'diff' => $rev->getRevId(),
			'oldid' => $rev->getParentId()
		);

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			wfMessage( 'diff' )->text(),
			array(
				'class' => 'plainlinks',
				'tabindex' => $count
			)
		);
	}

	/**
	 * @param EntityId $entityId
	 * @param RevisionData $rev
	 *
	 * @return string HTML
	 */
	private function buildHistoryLink( EntityId $entityId, RevisionData $rev ) {
		$titleText = $this->repoLinker->getEntityTitle( $entityId );

		$params = array(
			'title' => $titleText,
			'curid' => $rev->getPageId(),
			'action' => 'history'
		);

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			wfMessage( 'hist' )->text(),
			array(
				'class' => 'plainlinks'
			)
		);
	}

	/**
	 * @param string $userName
	 *
	 * @return string HTML
	 */
	private function buildUserLink( $userName ) {
		return $this->repoLinker->formatLink(
			// @todo: localise this once namespaces are localised on the repo
			$this->repoLinker->getPageUrl( "User:$userName" ),
			$userName,
			array(
				'class' => 'mw-userlink'
			)
		);
	}

	/**
	 * @param string $userName
	 * @param string|null $text
	 *
	 * @return string HTML
	 */
	private function buildUserContribsLink( $userName, $text = null ) {
		// @todo: know how the repo is localised. it's english now
		// for namespaces and special pages
		$link = $this->repoLinker->getPageUrl( "Special:Contributions/$userName" );

		if ( $text === null ) {
			$text = wfMessage( 'contribslink' )->text();
		}

		return $this->repoLinker->formatLink( $link, $text );
	}

	/**
	 * @param string $userName
	 *
	 * @return string HTML
	 */
	private function buildUserTalkLink( $userName ) {
		// @todo: localize this once we can localize namespaces on the repo
		$link = $this->repoLinker->getPageUrl( "User_talk:$userName" );
		$text = wfMessage( 'talkpagelinktext' )->text();

		return $this->repoLinker->formatLink( $link, $text );
	}

	/**
	 * @param string $userName
	 *
	 * @return string[] List of HTML links
	 */
	private function buildUserLinks( $userName ) {
		$links = [];

		$links['usertalk'] = $this->buildUserTalkLink( $userName );

		if ( User::isIP( $userName ) ) {
			$links['contribs'] = $this->buildUserContribsLink( $userName, $userName );
		} else {
			$links['user'] = $this->buildUserLink( $userName );
			$links['contribs'] = $this->buildUserContribsLink( $userName );
		}

		return $links;
	}

}
