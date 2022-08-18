<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\AtomicDiffOp;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Html;
use InvalidArgumentException;
use LanguageCode;
use MessageLocalizer;
use MWException;
use Site;
use SiteLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use WordLevelDiff;

/**
 * Class for generating views of DiffOp objects
 * representing diffs of an Item’s site links (including badges).
 *
 * Diffing of other Item data is done by {@link BasicDiffView}.
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkDiffView implements DiffView {

	/**
	 * @var string[]
	 */
	private $path;

	/**
	 * @var Diff
	 */
	private $diff;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @param string[] $path
	 * @param Diff $diff
	 * @param SiteLookup $siteLookup
	 * @param EntityIdFormatter $entityIdFormatter that must return only HTML! otherwise injections might be possible
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct(
		array $path,
		Diff $diff,
		SiteLookup $siteLookup,
		EntityIdFormatter $entityIdFormatter,
		MessageLocalizer $messageLocalizer
	) {
		$this->path = $path;
		$this->diff = $diff;
		$this->siteLookup = $siteLookup;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * Builds and returns the HTML to represent the Diff.
	 */
	public function getHtml(): string {
		return $this->generateOpHtml( $this->path, $this->diff );
	}

	/**
	 * Does the actual work.
	 *
	 * @param string[] $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ): string {
		if ( $op instanceof AtomicDiffOp ) {
			$localizedPath = $path;

			$translatedLinkSubPath = $this->messageLocalizer->msg(
				'wikibase-diffview-link-' . $path[2]
			);

			if ( !$translatedLinkSubPath->isDisabled() ) {
				$localizedPath[2] = $translatedLinkSubPath->text();
			}

			$html = $this->generateDiffHeaderHtml( implode( ' / ', $localizedPath ) );

			$html .= $this->generateDiffOpHtml( $path, $op );
		} else {
			$html = '';
			// @phan-suppress-next-line PhanTypeNoPropertiesForeach
			foreach ( $op as $key => $subOp ) {
				$html .= $this->generateOpHtml(
					array_merge( $path, [ $key ] ),
					$subOp
				);
			}
		}

		return $html;
	}

	private function generateDiffOpHtml( array $path, AtomicDiffOp $op ): string {
		if ( $path[2] === 'badges' ) {
			return $this->generateBadgeDiffOpHtml( $op );
		} else {
			return $this->generateLinkDiffOpHtml( $path[1], $op );
		}
	}

	private function generateBadgeDiffOpHtml( AtomicDiffOp $op ): string {
		$oldHtml = null;
		$newHtml = null;

		if ( $op instanceof DiffOpAdd ) {
			$newHtml = $this->getAddedLine( $this->getBadgeLinkElement( $op->getNewValue() ) );
		} elseif ( $op instanceof DiffOpRemove ) {
			$oldHtml = $this->getDeletedLine( $this->getBadgeLinkElement( $op->getOldValue() ) );
		} elseif ( $op instanceof DiffOpChange ) {
			$oldHtml = $this->getDeletedLine( $this->getBadgeLinkElement( $op->getOldValue() ) );
			$newHtml = $this->getAddedLine( $this->getBadgeLinkElement( $op->getNewValue() ) );
		} else {
			throw new MWException( 'Unknown DiffOp type' );
		}

		return $this->generateHtmlDiffTableRow( $oldHtml, $newHtml );
	}

	private function generateLinkDiffOpHtml( string $siteId, AtomicDiffOp $op ): string {
		$oldHtml = null;
		$newHtml = null;

		if ( $op instanceof DiffOpAdd ) {
			$newHtml = $this->getAddedLine( $this->getSiteLinkElement( $siteId, $op->getNewValue() ) );
		} elseif ( $op instanceof DiffOpRemove ) {
			$oldHtml = $this->getDeletedLine( $this->getSiteLinkElement( $siteId,  $op->getOldValue() ) );
		} elseif ( $op instanceof DiffOpChange ) {
			$wordLevelDiff = new WordLevelDiff(
				[ $op->getOldValue() ],
				[ $op->getNewValue() ]
			);
			$oldHtml = $this->getSiteLinkElement( $siteId, $op->getOldValue(), $wordLevelDiff->orig()[0] );
			$newHtml = $this->getSiteLinkElement( $siteId, $op->getNewValue(), $wordLevelDiff->closing()[0] );
		} else {
			throw new MWException( 'Unknown DiffOp type' );
		}

		return $this->generateHtmlDiffTableRow( $oldHtml, $newHtml );
	}

	/**
	 * Generates an HTML table row for a change diffOp
	 * given HTML snippets representing old and new
	 * sides of the Diff
	 */
	protected function generateHtmlDiffTableRow( ?string $oldHtml, ?string $newHtml ): string {
		$html = Html::openElement( 'tr' );
		if ( $oldHtml !== null ) {
			$html .= Html::element( 'td', [ 'class' => 'diff-marker', 'data-marker' => '−' ] );
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-deletedline' ],
				Html::rawElement( 'div', [], $oldHtml ) );
		}
		if ( $newHtml !== null ) {
			if ( $oldHtml === null ) {
				$html .= Html::element( 'td', [ 'colspan' => '2' ], "\u{00A0}" );
			}
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-marker', 'data-marker' => '+' ] );
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-addedline' ],
				Html::rawElement( 'div', [], $newHtml ) );
		}
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	private function getDeletedLine( string $html ): string {
		return $this->getChangedLine( 'del', $html );
	}

	private function getAddedLine( string $html ): string {
		return $this->getChangedLine( 'ins', $html );
	}

	private function getChangedLine( string $tag, string $html ): string {
		return Html::rawElement( $tag, [ 'class' => 'diffchange diffchange-inline' ], $html );
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param string|null $html Defaults to $pageName (HTML-escaped)
	 *
	 * @return string
	 */
	private function getSiteLinkElement( string $siteId, string $pageName, string $html = null ): string {
		$site = $this->siteLookup->getSite( $siteId );

		$tagName = 'span';
		$attrs = [
			'dir' => 'auto',
		];
		if ( $html === null ) {
			$html = htmlspecialchars( $pageName );
		}

		if ( $site instanceof Site ) {
			// Otherwise it may have been deleted from the sites table
			$tagName = 'a';
			$attrs['href'] = $site->getPageUrl( $pageName );
			$attrs['hreflang'] = LanguageCode::bcp47( $site->getLanguageCode() );
		}

		return Html::rawElement( $tagName, $attrs, $html );
	}

	/**
	 * @param string $idString
	 *
	 * @return string HTML
	 */
	private function getBadgeLinkElement( string $idString ): string {
		try {
			$itemId = new ItemId( $idString );
		} catch ( InvalidArgumentException $ex ) {
			return htmlspecialchars( $idString );
		}

		return $this->entityIdFormatter->formatEntityId( $itemId );
	}

	/**
	 * Generates HTML for the header of the diff operation
	 */
	protected function generateDiffHeaderHtml( string $name ): string {
		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', [ 'colspan' => '2', 'class' => 'diff-lineno' ], $name );
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$html .= Html::element( 'td', [ 'colspan' => '2', 'class' => 'diff-lineno' ], $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

}
