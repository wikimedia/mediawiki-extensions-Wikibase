<?php

namespace Wikibase\Repo\Diff;

use Content;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use DifferenceEngine;
use Html;
use IContextSource;
use Language;
use Linker;
use MWException;
use ParserOptions;
use ParserOutput;
use Revision;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\EntityContent;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EscapingValueFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Difference view for Wikibase entities.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentDiffView extends DifferenceEngine {

	/**
	 * @var EntityDiffVisualizer
	 */
	protected $diffVisualizer;

	/**
	 * @var SnakFormatter
	 */
	protected $detailedSnakFormatter;

	/**
	 * @var SnakFormatter
	 */
	protected $terseSnakFormatter;

	/**
	 * @var EntityIdLabelFormatter
	 */
	protected $propertyNameFormatter;

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

		$langCode = $this->getLanguage()->getCode();

		//TODO: proper injection
		$options = new FormatterOptions( array(
			//TODO: fallback chain
			ValueFormatter::OPT_LANG => $langCode
		) );

		$labelFormatter = new EntityIdLabelFormatter( $options, WikibaseRepo::getDefaultInstance()->getEntityLookup() );
		$this->propertyNameFormatter = new EscapingValueFormatter( $labelFormatter, 'htmlspecialchars' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$formatterFactory = $wikibaseRepo->getSnakFormatterFactory();
		$this->detailedSnakFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_DIFF, $options );
		$this->terseSnakFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML, $options );

		// @fixme inject!
		$this->diffVisualizer = new EntityDiffVisualizer(
			$this->getContext(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			new ClaimDifferenceVisualizer( $this->propertyNameFormatter, $this->detailedSnakFormatter, $this->terseSnakFormatter, $langCode ),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityRevisionLookup()
		);
	}

	/**
	 * @see DifferenceEngine::addHeader
	 *
	 * @param string $diff
	 * @param string $otitle
	 * @param string $ntitle
	 * @param string $multi
	 * @param string $notice
	 *
	 * @return string
	 */
	public function addHeader( $diff, $otitle, $ntitle, $multi = '', $notice = '' ) {
		// if we don't want a two column table layout, we have to change this
		return parent::addHeader( $diff, $otitle, $ntitle, $multi, $notice );
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
	 * @param $rev Revision
	 * @param $complete String: 'complete' to get the header wrapped depending
	 *        the visibility of the revision and a link to edit the page.
	 *
	 * @return String HTML fragment
	 */
	protected function getRevisionHeader( Revision $rev, $complete = '' ) {
		//NOTE: This must be kept in sync with the parent implementation.
		//      Perhaps some parts could be factored out to reduce code duplication.

		$lang = $this->getLanguage();
		$user = $this->getUser();
		$revtimestamp = $rev->getTimestamp();
		$timestamp = $lang->userTimeAndDate( $revtimestamp, $user );
		$dateofrev = $lang->userDate( $revtimestamp, $user );
		$timeofrev = $lang->userTime( $revtimestamp, $user );

		$header = $this->msg(
			$rev->isCurrent() ? 'currentrev-asof' : 'revisionasof',
			$timestamp,
			$dateofrev,
			$timeofrev
		)->escaped();

		if ( $complete !== 'complete' ) {
			return $header;
		}

		$title = $rev->getTitle();

		$header = Linker::linkKnown( $title, $header, array(),
			array( 'oldid' => $rev->getID() ) );

		if ( $rev->userCan( Revision::DELETED_TEXT, $user ) ) {
			if ( $title->quickUserCan( 'edit', $user ) ) {
				if ( $rev->isCurrent() ) {
					$editQuery = array( 'action' => 'edit' );
					$msg = $this->msg( 'editold' )->escaped();
				} else {
					$editQuery = array( 'action' => 'edit' );
					$editQuery['restore'] = $rev->getID();
					$msg = $this->msg( 'wikibase-restoreold' )->escaped();
				}

				$header .= ' (' . Linker::linkKnown( $title, $msg, array(), $editQuery ) . ')';
			}

			if ( $rev->isDeleted( Revision::DELETED_TEXT ) ) {
				$header = Html::rawElement( 'span', array( 'class' => 'history-deleted' ), $header );
			}
		} else {
			$header = Html::rawElement( 'span', array( 'class' => 'history-deleted' ), $header );
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
			$diff = $old->getDiff( $new );
			return $this->diffVisualizer->visualizeEntityContentDiff( $diff );
		}

		return parent::generateContentDiffBody( $old, $new );
	}

	/**
	 * @param WikiPage $page
	 * @param Revision $rev
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutput( WikiPage $page, Revision $rev ) {
		$parserOptions = ParserOptions::newFromContext( $this->getContext() );
		$parserOptions->enableLimitReport();
		$parserOptions->setTidy( true );

		$parserOptions->setEditSection( false );
		// Do not poison parser cache with diff-specific stuff
		$parserOptions->addExtraKey( 'diff=1' );

		$parserOutput = $page->getParserOutput( $parserOptions, $rev->getId() );
		return $parserOutput;
	}

	/**
	 * Returns the cache key for diff body text or content.
	 *
	 * @return string
	 */
	protected function getDiffBodyCacheKey() {
		return wfMemcKey(
			'diff',
			'version', MW_DIFF_VERSION,
			'oldid', $this->getOldid(),
			'newid', $this->getNewid(),
			'lang', $this->getLanguage()->getCode()
		);
	}

}
