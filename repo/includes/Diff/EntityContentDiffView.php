<?php

namespace Wikibase\Repo\Diff;

use Content;
use DifferenceEngine;
use Html;
use IContextSource;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MWException;
use ParserOutput;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesError;
use Wikibase\Repo\WikibaseRepo;
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
	 * @var BasicEntityDiffVisualizer
	 */
	private $diffVisualizer;

	/**
	 * @see DifferenceEngine::__construct
	 *
	 * @param IContextSource|null $context
	 * @param int $old
	 * @param int $new
	 * @param int $rcid
	 * @param bool $refreshCache
	 * @param bool $unhide
	 */
	public function __construct( $context = null, $old = 0, $new = 0, $rcid = 0, $refreshCache = false, $unhide = false ) {
		parent::__construct( $context, $old, $new, $rcid, $refreshCache, $unhide );

		$entityDiffVisualizerFactory = WikibaseRepo::getEntityDiffVisualizerFactory();
		$this->diffVisualizer = new DispatchingEntityDiffVisualizer( $entityDiffVisualizerFactory, $context );
	}

	/**
	 * @return Language
	 */
	public function getDiffLang() {
		if ( $this->mDiffLang === null ) {
			$this->mDiffLang = $this->getLanguage();
		}

		return parent::getDiffLang();
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
	 * @see DifferenceEngine::generateContentDiffBody
	 *
	 * @param Content $old
	 * @param Content $new
	 *
	 * @throws MWException If the two content objects are neither EntityContent nor TextContent.
	 * @return string
	 */
	public function generateContentDiffBody( Content $old, Content $new ) {
		if ( ( $old instanceof EntityContent ) && ( $new instanceof EntityContent ) ) {
			// add Wikibase styles, the diff may include entity links with labels, including fallback indicators
			$this->getOutput()->addModuleStyles( [ 'wikibase.alltargets' ] );

			$diff = $old->getDiff( $new );
			return $this->diffVisualizer->visualizeEntityContentDiff( $diff );
		} elseif ( ( $old instanceof EntityContent ) !== ( $new instanceof EntityContent ) ) {
			$this->getOutput()->showErrorPage( 'errorpagetitle', 'wikibase-non-entity-diff' );
			return '';
		}

		return parent::generateContentDiffBody( $old, $new );
	}

	/**
	 * @param WikiPage $page
	 * @param RevisionRecord $rev
	 *
	 * @return ParserOutput|bool False if the revision was not found
	 */
	protected function getParserOutput( WikiPage $page, RevisionRecord $rev ) {
		$parserOptions = $page->makeParserOptions( $this->getContext() );

		// Do not poison parser cache with diff-specific stuff
		$parserOptions->addExtraKey( 'diff=1' );

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

	/**
	 * @inheritDoc
	 */
	protected function getDiffBodyCacheKeyParams() {
		$parent = parent::getDiffBodyCacheKeyParams();
		$code = $this->getLanguage()->getCode();
		$parent[] = "lang-{$code}";

		return $parent;
	}

}
