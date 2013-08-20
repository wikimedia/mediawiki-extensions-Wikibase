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

	/**
	 * @since 0.4
	 *
	 * @param ChangesList $changesList
	 * @param string $siteLocalId
	 * @param RepoLinker $repoLinker
	 */
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
		$externalPage = $externalChange->getExternalPage();

		$linkBuilder = new ChangeLineLinkBuilder();
		$externalLinks = $linkBuilder->buildLinks(
			$externalChange,
			$externalPage,
			$recentChange->counter
		);

		$line = '';

		if ( in_array( $changeType, array( 'remove', 'restore' ) ) ) {
			$formattedLink = $this->formatLink( $externalLinks['logdelete'] );
			$line .= wfMessage( 'parentheses' )->rawParams( $formattedLink )->text();
		} else {
			$line .= $this->formatDiffHist( $externalLinks );
		}

		$line .= $this->changeSeparator();
		$line .= $this->changesList->recentChangesFlags( array( 'wikibase-edit' => true ), '' ) . ' ';
		$line .= Linker::link( $externalChange->getTitle() );

		if ( in_array( $changeType, array( 'add', 'restore', 'update' ) ) ) {
			$entityLink = $this->formatLink( $externalLinks['entity'] );

			$line .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams( $entityLink )->text();
		}

		$line .= $this->formatTimestamp(
			$externalChange->getTimestamp(),
			$this->changesList->getUser()
		);

		$line .= $this->formatUserLinks( $externalLinks, $externalChange->getUserName() );
		$line .= $externalChange->getComment();

		return $line;
	}

	protected function formatLink( ExternalLink $link ) {
		return $this->repoLinker->repoLink(
			$link->getTarget(),
			$link->getLinkText(),
			$link->getLinkParams()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function formatDiffHist( $externalLinks ) {
		$diffLink = $this->formatLink( $externalLinks['diff'] );
		$historyLink = $this->formatLink( $externalLinks['history'] );

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
	public function formatTimestamp( $timestamp, User $user ) {
		return wfMessage( 'semicolon-separator' )->text()
			. '<span class="mw-changeslist-date">'
			. $this->changesList->getLanguage()->userTime( $timestamp, $user )
			. '</span> <span class="mw-changeslist-separator">. .</span> ';
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
	protected function formatWikiLink( $page, $siteLang ) {
		if ( $siteLang !== null && $siteLang !== $this->siteLocalId ) {
			return "[[:$siteLang:$page|$siteLang:$page]]";
		} else {
			return "[[$page]]";
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param string $userName
	 *
	 * @return string
	 */
	public function formatUserLinks( $externalLinks, $userName ) {
		if ( User::isIP( $userName ) ) {
			$userlinks = $this->formatLink( $externalLinks['contribs'] );
			$userlinks .= wfMessage( 'word-separator' )->plain()
				. wfMessage( 'parentheses' )->rawParams(
					$this->formatLink( $externalLinks['usertalk'] )
				)->text();
		} else {
			$userlinks = $this->formatLink( $externalLinks['user'] );
			$usertools = array(
				$this->formatLink( $externalLinks['usertalk'] ),
				$this->formatLink( $externalLinks['contribs'] )
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

}
