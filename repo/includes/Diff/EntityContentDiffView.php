<?php

namespace Wikibase\Repo\Diff;

use DifferenceEngine;
use Html;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use ParserOutput;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesError;
use Wikibase\View\ToolbarEditSectionGenerator;
use WikiPage;

/**
 * Difference view for Wikibase entities.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentDiffView extends DifferenceEngine {
	/**
	 * @return Language
	 */
	public function getDefaultLanguage() {
		return $this->getLanguage();
	}

	/**
	 * Get a header for a specified revision.
	 *
	 * @param RevisionRecord $rev
	 * @param string $complete 'complete' to get the header wrapped depending
	 *        the visibility of the revision and a link to edit the page.
	 *
	 * @return string HTML fragment
	 */
	public function getRevisionHeader( RevisionRecord $rev, $complete = '' ) {
		//NOTE: This must be kept in sync with the parent implementation.
		//      Perhaps some parts could be factored out to reduce code duplication.

		$lang = $this->getLanguage();
		$user = $this->getUser();
		$revtimestamp = $rev->getTimestamp();
		$timestamp = $lang->userTimeAndDate( $revtimestamp, $user );
		$dateofrev = $lang->userDate( $revtimestamp, $user );
		$timeofrev = $lang->userTime( $revtimestamp, $user );

		$headerMsg = $this->msg(
			$rev->isCurrent() ? 'currentrev-asof' : 'revisionasof',
			$timestamp,
			$dateofrev,
			$timeofrev
		);

		if ( $complete !== 'complete' ) {
			return $headerMsg->escaped();
		}

		$title = $rev->getPageAsLinkTarget();

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		$header = $linkRenderer->makeKnownLink( $title, $headerMsg->text(), [],
			[ 'oldid' => $rev->getId() ] );

		if ( RevisionRecord::userCanBitfield(
			$rev->getVisibility(),
			RevisionRecord::DELETED_TEXT,
			$user
		) ) {
			if ( MediaWikiServices::getInstance()->getPermissionManager()
					->quickUserCan( 'edit', $user, $title ) && !$rev->isCurrent()
			) {
				$editQuery = [
					'action' => 'edit',
					'restore' => $rev->getId(),
				];
				$msg = $this->msg( 'wikibase-restoreold' )->text();
				$header .= ' ' . $this->msg( 'parentheses' )->rawParams(
					$linkRenderer->makeKnownLink( $title, $msg, [], $editQuery )
				)->escaped();
			}

			if ( $rev->isDeleted( RevisionRecord::DELETED_TEXT ) ) {
				$header = Html::rawElement( 'span', [ 'class' => 'history-deleted' ], $header );
			}
		} else {
			$header = Html::rawElement( 'span', [ 'class' => 'history-deleted' ], $header );
		}

		return $header;
	}

	/**
	 * @param WikiPage $page
	 * @param RevisionRecord $rev
	 *
	 * @return ParserOutput|bool False if the revision was not found
	 */
	protected function getParserOutput( WikiPage $page, RevisionRecord $rev ) {
		$parserOptions = $page->makeParserOptions( $this->getContext() );
		$parserOptions->setRenderReason( 'diff-page' );

		try {
			$parserOutput = $page->getParserOutput( $parserOptions, $rev->getId() );
		} catch ( FederatedPropertiesError $ex ) {
			$parserOutput = false;
		}

		if ( $parserOutput ) {
			$parserOutput->setText( ToolbarEditSectionGenerator::enableSectionEditLinks(
				$parserOutput->getRawText(),
				false
			) );
		}

		return $parserOutput;
	}

}
