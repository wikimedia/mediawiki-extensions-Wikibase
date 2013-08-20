<?php

namespace Wikibase;

use Exception;
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
class ChangeLineLinkBuilder {

	/**
	 * @since 0.4
	 *
	 * @param ExternalChange $externalChange
	 * @param ExternalPage $externalPage
	 * @param int $counter
	 *
	 * @return ExternalLink[]
	 */
	public function buildLinks(
		ExternalChange $externalChange,
		ExternalPage $externalPage,
		$counter
	) {
		$changeType = $externalChange->getChangeType();
		$userName = $externalChange->getUserName();

		$links = $this->buildUserLinks( $userName );

		switch( $changeType ) {
			case 'add':
				$links['entity'] = $this->buildEntityLink( $externalPage );
				$links['diff'] = $this->buildDiffLink( $externalChange, $externalPage, $counter );
				$links['history'] = $this->buildHistoryLink( $externalPage );
				break;
			case 'update':
				$links['entity'] = $this->buildEntityLink( $externalPage );
				$links['diff'] = $this->buildDiffLink( $externalChange, $externalPage, $counter );
				$links['history'] = $this->buildHistoryLink( $externalPage );
				break;
			case 'restore':
				$links['entity'] = $this->buildEntityLink( $externalPage );
				$links['logdelete'] = $this->buildDeletionLogLink();
				break;
			case 'remove':
				$links['logdelete'] = $this->buildDeletionLogLink();
				break;
			default:
				break;
		}

		return $links;
	}

	/**
	 * @since 0.4
	 *
	 * @return ExternalLink
	 */
	protected function buildDeletionLogLink() {
		return new ExternalLink(
			'Special:Log/delete',
			wfMessage( 'dellogpage' )->text()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param ExternalChange $externalChange
	 * @param ExternalPage $externalPage
	 * @param int $counter
	 *
	 * @return ExternalLink
	 */
	protected function buildDiffLink( $externalChange, $externalPage, $counter ) {
		$titleText = $this->formatFullPageTitle( $externalPage );
		$pageId = $externalPage->getPageId();
		$revId = $externalChange->getRevId();
		$parentId = $externalChange->getParentId();

		return new ExternalLink(
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
	 * @since 0.4
	 *
	 * @param ExternalPage $externalPage
	 *
	 * @return ExternalLink
	 */
	protected function buildHistoryLink( $externalPage ) {
		$titleText = $this->formatFullPageTitle( $externalPage );
		$pageId = $externalPage->getPageId();

		return new ExternalLink(
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
	}

	/**
	 * @since 0.4
	 *
	 * @param string $userName
	 *
	 * @return ExternalLink
	 */
	protected function buildUserLink( $userName ) {
		return new ExternalLink(
			// @todo: localise this once namespaces are localised on the repo
			"User:$userName",
			$userName,
			array(
				'class' => 'mw-userlink'
			)
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param string $userName
	 * @param string $text
	 *
	 * @return ExternalLink
	 */
	protected function buildUserContribsLink( $userName, $text = null ) {
		// @todo: know how the repo is localised. it's english now
		// for namespaces and special pages
		$link = "Special:Contributions/$userName";
		if ( $text === null ) {
			$text = wfMessage( 'contribslink' );
		}

		return new ExternalLink( $link, $text );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $userName
	 *
	 * @return ExternalLink
	 */
	protected function buildUserTalkLink( $userName ) {
		// @todo: localize this once we can localize namespaces on the repo
		$link = "User_talk:$userName";
		$text = wfMessage( 'talkpagelinktext' )->text();

		return new ExternalLink( $link, $text );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $userName
	 *
	 * @return ExternalLink[]
	 */
	public function buildUserLinks( $userName ) {
		$links = array();

		if ( User::isIP( $userName ) ) {
			$links['contribs'] = $this->buildUserContribsLink( $userName, $userName );
		} else {
			$links['user'] = $this->buildUserLink( $userName );
			$links['usertalk'] = $this->buildUserTalkLink( $userName );
			$links['contribs'] = $this->buildUserContribsLink( $userName );
		}

		return $links;
	}

	/**
	 * @since 0.4
	 *
	 * @param ExternalPage $externalPage
	 *
	 * @return ExternalLink
	 */
	protected function buildEntityLink( $externalPage ) {
		$entityText = $this->formatFullPageTitle( $externalPage );
		$prefixedId = $externalPage->getPageTitle();

		return new ExternalLink(
			$entityText,
			$prefixedId,
			array( 'class' => 'wb-entity-link' )
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param ExternalPage
	 *
	 * @return string
	 */
	protected function formatFullPageTitle( ExternalPage $externalPage ) {
		$pageTitle = $externalPage->getPageTitle();
		$namespace = $externalPage->getNamespace();

		if ( !empty( $namespace ) ) {
			return "$namespace:$pageTitle";
		}

		return $pageTitle;
	}

}
