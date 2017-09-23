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
		// @fixme: deprecated method, use \LinkRenderer
		$link = Linker::link( $title );
		$line .= "<span class=\"mw-title\">$link</span>";

		if ( $changeType !== 'remove' ) {
			$line .= $this->formatEntityLink( $entityId );
		}

		$line .= $this->formatTimestamp( $rev->getTimestamp() );
		$line .= implode( $this->formatUserLinks( $rev->getUserName() ) );

		$line .= $this->getFormattedComment( $rev, $title, $externalChange->getSiteId() );

		return $line;
	}

	/**
	 * @param array &$data
	 * @param ExternalChange $externalChange
	 * @param Title $title
	 */
	private function formatCommonDataForEnhancedLine( array &$data, ExternalChange $externalChange, Title $title ) {
		$entityId = $externalChange->getEntityId();
		$rev = $externalChange->getRev();

		$data['recentChangesFlags']['wikibase-edit'] = true;
		$data['timestampLink'] = $this->buildPermanentLink( $entityId, $rev );

		list( $data['userLink'], $data['userTalkLink'] ) = $this->formatUserLinks( $rev->getUserName() );

		$data['comment'] = $this->getFormattedComment( $rev, $title, $externalChange->getSiteId() );
	}

	/**
	 * @param array &$data
	 * @param ExternalChange $externalChange
	 * @param Title $title
	 * @param int $count
	 */
	public function formatDataForEnhancedLine( array &$data, ExternalChange $externalChange, Title $title, $count ) {
		$this->formatCommonDataForEnhancedLine( $data, $externalChange, $title );

		$entityId = $externalChange->getEntityId();
		$rev = $externalChange->getRev();
		$changeType = $externalChange->getChangeType();

		$data['currentAndLastLinks'] = $this->repoLinker->buildEntityLink( $entityId )
			. wfMessage( 'word-separator' )->escaped();
		$data['currentAndLastLinks'] .= ( $changeType === 'restore' || $changeType === 'remove' )
			? $this->formatDeletionLogLink()
			: $this->formatDiffHist( $entityId, $rev, $count );

		$data['separatorAfterCurrentAndLastLinks'] = $this->changeSeparator();

		unset( $data['characterDiff'] );
		// @fixme: this has different case than in formatDataForEnhancedBlockLine
		unset( $data['separatorAfterCharacterDiff'] );
	}

	/**
	 * @param array &$data
	 * @param ExternalChange $externalChange
	 * @param Title $title
	 * @param int $count
	 */
	public function formatDataForEnhancedBlockLine( array &$data, ExternalChange $externalChange, Title $title, $count ) {
		$this->formatCommonDataForEnhancedLine( $data, $externalChange, $title );

		$entityId = $externalChange->getEntityId();
		$rev = $externalChange->getRev();
		$changeType = $externalChange->getChangeType();

		if ( $changeType === 'restore' || $changeType === 'remove' ) {
			$data['articleLink'] = ''
				. $this->formatDeletionLogLink()
				. $data['articleLink']
				. $this->formatEntityLink( $entityId );
			unset( $data['historyLink'] );
		} else {
			$data['articleLink'] .= $this->formatEntityLink( $entityId );
			$data['historyLink'] = ''
				. wfMessage( 'word-separator' )->escaped()
				. $this->formatDiffHist( $entityId, $rev, $count );
		}

		$data['separatorAfterLinks'] = $this->changeSeparator();

		unset( $data['characterDiff'] );
		// @fixme: this has different case than in formatDataForEnhancedLine
		unset( $data['separatorAftercharacterDiff'] );
	}

	/**
	 * @param RevisionData $rev
	 * @param Title $title
	 * @param string $siteId
	 *
	 * @return string HTML
	 */
	private function getFormattedComment( RevisionData $rev, Title $title, $siteId ) {
		$commentHtml = $rev->getCommentHtml();
		if ( $commentHtml === null || $commentHtml === '' ) {
			$commentHtml = Linker::formatComment( $rev->getComment(), $title, false, $siteId );
		}
		return $this->wrapCommentBlock( $commentHtml );
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
	 * @return string[] array of HTML
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
	 * @return string[] array of HTML
	 */
	private function formatIpUserLinks( array $links ) {
		$ret = [];

		$ret[] = $links['contribs'];
		$ret[] = wfMessage( 'word-separator' )->plain()
			. wfMessage( 'parentheses' )->rawParams(
				$links['usertalk']
			)->text();

		return $ret;
	}

	/**
	 * @fixme use shared bits of formatting from ChangesList and OldChangesList
	 *
	 * @param string[] $links
	 *
	 * @return string[] array of HTML
	 */
	private function formatRegisteredUserLinks( array $links ) {
		$ret = [];

		$ret[] = $links['user'];

		$usertools = [
			$links['usertalk'],
			$links['contribs']
		];

		$ret[] = wfMessage( 'word-separator' )->plain()
			. '<span class="mw-usertoollinks">'
			. wfMessage( 'parentheses' )->rawParams(
				$this->lang->pipeList( $usertools )
			)->text()
			. '</span>';

		return $ret;
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
			$this->lang->pipeList( [ $diffLink, $historyLink ] )
		)->text();
	}

	/**
	 * @param EntityId $entityId
	 * @param RevisionData $rev
	 *
	 * @return string HTML
	 */
	private function buildPermanentLink( EntityId $entityId, RevisionData $rev ) {
		$params = [
			'title' => $this->repoLinker->getEntityTitle( $entityId ),
			'curid' => $rev->getPageId(),
			'oldid' => $rev->getRevId()
		];

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			$this->lang->userTime( $rev->getTimestamp(), $this->user )
		);
	}

	/**
	 * @param EntityId $entityId
	 * @param RevisionData $rev
	 * @param int $count
	 *
	 * @return string HTML
	 */
	private function buildDiffLink( EntityId $entityId, RevisionData $rev, $count ) {
		$params = [
			'title' => $this->repoLinker->getEntityTitle( $entityId ),
			'curid' => $rev->getPageId(),
			'diff' => $rev->getRevId(),
			'oldid' => $rev->getParentId()
		];

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			wfMessage( 'diff' )->text(),
			[
				'tabindex' => $count
			]
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

		$params = [
			'title' => $titleText,
			'curid' => $rev->getPageId(),
			'action' => 'history'
		];

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			wfMessage( 'hist' )->text()
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
			[
				'class' => 'mw-userlink'
			]
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
