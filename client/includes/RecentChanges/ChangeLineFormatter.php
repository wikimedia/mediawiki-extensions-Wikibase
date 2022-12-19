<?php

declare( strict_types = 1 );

namespace Wikibase\Client\RecentChanges;

use Language;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNameUtils;
use Title;
use User;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Formats a changes line for including changes from the Wikibase repo in
 * the client's recent changes, watchlist and related changes special pages.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ChangeLineFormatter {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var UserNameUtils
	 */
	private $userNameUtils;

	/**
	 * @var LinkRenderer
	 */
	private $linkRenderer;

	/**
	 * @var CommentFormatter
	 */
	private $commentFormatter;

	public function __construct(
		RepoLinker $repoLinker,
		UserNameUtils $userNameUtils,
		LinkRenderer $linkRenderer,
		CommentFormatter $commentFormatter
	) {
		$this->repoLinker = $repoLinker;
		$this->userNameUtils = $userNameUtils;
		$this->linkRenderer = $linkRenderer;
		$this->commentFormatter = $commentFormatter;
	}

	public function format(
		ExternalChange $externalChange,
		Title $title,
		int $count,
		string $flag,
		Language $lang,
		User $user
	): string {
		$changeType = $externalChange->getChangeType();
		$rev = $externalChange->getRev();
		$entityId = $externalChange->getEntityId();

		$formattedHTMLLine = ( $changeType === 'restore' || $changeType === 'remove' )
			? $this->formatDeletionLogLinkHTML()
			: $this->formatDiffHistHTML( $entityId, $rev, $count, $lang );

		$formattedHTMLLine .= $this->getChangeSeparatorHTML();
		$formattedHTMLLine .= $flag . ' ';
		$link = $this->linkRenderer->makeKnownLink( $title );

		$formattedHTMLLine .= "<span class=\"mw-title\">$link</span>";

		if ( $changeType !== 'remove' ) {
			$formattedHTMLLine .= $this->formatEntityLinkHTML( $entityId );
		}

		$formattedHTMLLine .= $this->formatTimestampHTML( $rev->getTimestamp(), $lang, $user );
		$formattedHTMLLine .= implode( $this->formatUserLinksHTML( $rev->getUserName(), $rev->getVisibility(), $lang, $user ) );

		$formattedHTMLLine .= $this->getFormattedCommentHTML( $rev, $title, $externalChange->getSiteId(), $user );

		return $formattedHTMLLine;
	}

	private function formatCommonDataForEnhancedLine(
		array &$data,
		ExternalChange $externalChange,
		Title $title,
		Language $lang,
		User $user
	): void {
		$entityId = $externalChange->getEntityId();
		$rev = $externalChange->getRev();

		$data['recentChangesFlags']['wikibase-edit'] = true;
		$data['timestampLink'] = $this->buildPermanentLinkHTML( $entityId, $rev, $lang, $user );

		$userLinks = $this->formatUserLinksHTML( $rev->getUserName(), $rev->getVisibility(), $lang, $user );
		if ( count( $userLinks ) > 1 ) {
			list( $data['userLink'], $data['userTalkLink'] ) = $userLinks;
		} else {
			$data['userLink'] = array_pop( $userLinks );
		}

		$data['comment'] = $this->getFormattedCommentHTML( $rev, $title, $externalChange->getSiteId(), $user );
	}

	public function formatDataForEnhancedLine(
		array &$data,
		ExternalChange $externalChange,
		Title $title,
		int $count,
		Language $lang,
		User $user
	): void {
		$this->formatCommonDataForEnhancedLine( $data, $externalChange, $title, $lang, $user );

		$entityId = $externalChange->getEntityId();
		$rev = $externalChange->getRev();
		$changeType = $externalChange->getChangeType();

		$wordSeparator = wfMessage( 'word-separator' )->escaped();
		$data['currentAndLastLinks'] =
			$wordSeparator . $this->repoLinker->buildEntityLink( $entityId ) . $wordSeparator;
		$data['currentAndLastLinks'] .= ( $changeType === 'restore' || $changeType === 'remove' )
			? $this->formatDeletionLogLinkHTML()
			: $this->formatDiffHistHTML( $entityId, $rev, $count, $lang );

		$data['separatorAfterCurrentAndLastLinks'] = $this->getChangeSeparatorHTML();

		unset( $data['characterDiff'] );
		// @fixme: this has different case than in formatDataForEnhancedBlockLine
		unset( $data['separatorAfterCharacterDiff'] );
	}

	public function formatDataForEnhancedBlockLine(
		array &$data,
		ExternalChange $externalChange,
		Title $title,
		int $count,
		Language $lang,
		User $user
	): void {
		$this->formatCommonDataForEnhancedLine( $data, $externalChange, $title, $lang, $user );

		$entityId = $externalChange->getEntityId();
		$rev = $externalChange->getRev();
		$changeType = $externalChange->getChangeType();

		if ( $changeType === 'restore' || $changeType === 'remove' ) {
			$data['articleLink'] = ''
				. $this->formatDeletionLogLinkHTML()
				. $data['articleLink']
				. $this->formatEntityLinkHTML( $entityId );
			unset( $data['historyLink'] );
		} else {
			$data['articleLink'] .= $this->formatEntityLinkHTML( $entityId );
			$data['historyLink'] = ''
				. wfMessage( 'word-separator' )->escaped()
				. $this->formatDiffHistHTML( $entityId, $rev, $count, $lang );
		}

		$data['separatorAfterLinks'] = $this->getChangeSeparatorHTML();

		unset( $data['characterDiff'] );
		// @fixme: this has different case than in formatDataForEnhancedLine
		unset( $data['separatorAftercharacterDiff'] );
	}

	private function getFormattedCommentHTML( RevisionData $rev, Title $title, string $siteId, Authority $user ): string {
		if ( !RevisionRecord::userCanBitfield(
			$rev->getVisibility(),
			RevisionRecord::DELETED_COMMENT,
			$user )
		) {
			return ' <span class="history-deleted comment">' .
				wfMessage( 'rev-deleted-comment' )->escaped() . '</span>';
		}

		$commentHtml = $rev->getCommentHtml();
		if ( $commentHtml === null || $commentHtml === '' ) {
			$commentHtml = $this->commentFormatter->format( $rev->getComment(), $title, false, $siteId );
		}
		return $this->wrapCommentBlockHTML( $commentHtml );
	}

	private function wrapCommentBlockHTML( string $commentHtml ): string {
		//NOTE: keep in sync with MediaWiki\CommentFormatter\CommentFormatter::formatBlock
		$formatted = wfMessage( 'parentheses' )->rawParams( $commentHtml )->escaped();
		return " <span class=\"comment\">$formatted</span>";
	}

	private function getChangeSeparatorHTML(): string {
		return ' <span class="mw-changeslist-separator">. .</span> ';
	}

	private function formatTimestampHTML( string $timestamp, Language $lang, UserIdentity $user ): string {
		return wfMessage( 'semicolon-separator' )->text()
			. '<span class="mw-changeslist-date">'
			. $lang->userTime( $timestamp, $user )
			. '</span> <span class="mw-changeslist-separator">. .</span> ';
	}

	/**
	 * @param string $userName
	 * @param int $visibility
	 * @param Language $lang
	 * @param Authority $user
	 * @return string[] array of HTML
	 */
	private function formatUserLinksHTML( string $userName, int $visibility, Language $lang, Authority $user ): array {
		if ( !RevisionRecord::userCanBitfield(
			$visibility,
			RevisionRecord::DELETED_USER,
			$user )
		) {
			return [
				' <span class="history-deleted">' .
				wfMessage( 'rev-deleted-user' )->escaped() . '</span>',
			];
		}

		$links = $this->buildUserLinksHTML( $userName );

		if ( $this->userNameUtils->isIP( $userName ) ) {
			return $this->formatIpUserLinksHTML( $links );
		} else {
			return $this->formatRegisteredUserLinksHTML( $links, $lang );
		}
	}

	/**
	 * @fixme use shared bits of formatting from ChangesList and OldChangesList
	 *
	 * @param string[] $links
	 *
	 * @return string[] array of HTML
	 */
	private function formatIpUserLinksHTML( array $links ): array {
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
	 * @param string[] $links
	 * @param Language $lang
	 * @return string[] array of HTML
	 */
	private function formatRegisteredUserLinksHTML( array $links, Language $lang ): array {
		$ret = [];

		$ret[] = $links['user'];

		$usertools = [
			$links['usertalk'],
			$links['contribs'],
		];

		$ret[] = wfMessage( 'word-separator' )->plain()
			. '<span class="mw-usertoollinks">'
			. wfMessage( 'parentheses' )->rawParams(
				$lang->pipeList( $usertools )
			)->text()
			. '</span>';

		return $ret;
	}

	private function formatEntityLinkHTML( EntityId $entityId ): string {
		$entityLink = $this->repoLinker->buildEntityLink( $entityId );

		return wfMessage( 'word-separator' )->plain()
			. wfMessage( 'parentheses' )->rawParams( $entityLink )->text();
	}

	private function formatDeletionLogLinkHTML(): string {
		$logLink = $this->repoLinker->formatLink(
			$this->repoLinker->getPageUrl( 'Special:Log/delete' ),
			wfMessage( 'dellogpage' )->text()
		);

		return wfMessage( 'parentheses' )->rawParams( $logLink )->text();
	}

	private function formatDiffHistHTML( EntityId $entityId, RevisionData $rev, int $count, Language $lang ): string {
		$diffLink = $this->buildDiffLinkHTML( $entityId, $rev, $count );
		$historyLink = $this->buildHistoryLinkHTML( $entityId, $rev );

		return wfMessage( 'parentheses' )->rawParams(
			$lang->pipeList( [ $diffLink, $historyLink ] )
		)->text();
	}

	private function buildPermanentLinkHTML( EntityId $entityId, RevisionData $rev, Language $lang, UserIdentity $user ): string {
		$params = [
			'title' => $this->repoLinker->getEntityTitle( $entityId ),
			'curid' => $rev->getPageId(),
			'oldid' => $rev->getRevId(),
		];

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			$lang->userTime( $rev->getTimestamp(), $user )
		);
	}

	private function buildDiffLinkHTML( EntityId $entityId, RevisionData $rev, $count ): string {
		$params = [
			'title' => $this->repoLinker->getEntityTitle( $entityId ),
			'curid' => $rev->getPageId(),
			'diff' => $rev->getRevId(),
			'oldid' => $rev->getParentId(),
		];

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			wfMessage( 'diff' )->text(),
			[
				'tabindex' => $count,
			]
		);
	}

	private function buildHistoryLinkHTML( EntityId $entityId, RevisionData $rev ): string {
		$titleText = $this->repoLinker->getEntityTitle( $entityId );

		$params = [
			'title' => $titleText,
			'curid' => $rev->getPageId(),
			'action' => 'history',
		];

		$url = $this->repoLinker->addQueryParams( $this->repoLinker->getIndexUrl(), $params );

		return $this->repoLinker->formatLink(
			$url,
			wfMessage( 'hist' )->text()
		);
	}

	private function buildUserLinkHTML( string $userName ): string {
		return $this->repoLinker->formatLink(
			// @todo: localise this once namespaces are localised on the repo
			$this->repoLinker->getPageUrl( "User:$userName" ),
			$userName,
			[
				'class' => 'mw-userlink',
			]
		);
	}

	private function buildUserContribsLinkHTML( string $userName, ?string $text = null ): string {
		// @todo: know how the repo is localised. it's english now
		// for namespaces and special pages
		$link = $this->repoLinker->getPageUrl( "Special:Contributions/$userName" );

		if ( $text === null ) {
			$text = wfMessage( 'contribslink' )->text();
		}

		return $this->repoLinker->formatLink( $link, $text );
	}

	private function buildUserTalkLinkHTML( string $userName ): string {
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
	private function buildUserLinksHTML( string $userName ): array {
		$links = [];

		$links['usertalk'] = $this->buildUserTalkLinkHTML( $userName );

		if ( $this->userNameUtils->isIP( $userName ) ) {
			$links['contribs'] = $this->buildUserContribsLinkHTML( $userName, $userName );
		} else {
			$links['user'] = $this->buildUserLinkHTML( $userName );
			$links['contribs'] = $this->buildUserContribsLinkHTML( $userName );
		}

		return $links;
	}

}
