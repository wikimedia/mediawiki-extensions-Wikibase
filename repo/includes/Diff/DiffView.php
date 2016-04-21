<?php

namespace Wikibase\Repo\Diff;

use ContextSource;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Html;
use IContextSource;
use InvalidArgumentException;
use MWException;
use Site;
use SiteStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;

/**
 * Class for generating views of DiffOp objects.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Addshore
 * @author Thiemo Mättig
 */
class DiffView extends ContextSource {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var string[]
	 */
	private $path;

	/**
	 * @var Diff
	 */
	private $diff;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @since 0.1
	 *
	 * @param string[] $path
	 * @param Diff $diff
	 * @param SiteStore $siteStore
	 * @param EntityIdFormatter $entityIdFormatter that must return only HTML! otherwise injections might be possible
	 * @param IContextSource|null $contextSource
	 */
	public function __construct(
		array $path,
		Diff $diff,
		SiteStore $siteStore,
		EntityIdFormatter $entityIdFormatter,
		IContextSource $contextSource = null
	) {
		$this->path = $path;
		$this->diff = $diff;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->siteStore = $siteStore;

		if ( !is_null( $contextSource ) ) {
			$this->setContext( $contextSource );
		}
	}

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHtml() {
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
	private function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			$html = $this->generateDiffHeaderHtml( implode( ' / ', $path ) );

			//TODO: no path, but localized section title

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				/** @var DiffOpAdd $op */
				$html .= $this->generateChangeOpHtml( null, $op->getNewValue(), $path );
			} elseif ( $op->getType() === 'remove' ) {
				/** @var DiffOpRemove $op */
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), null, $path );
			} elseif ( $op->getType() === 'change' ) {
				/** @var DiffOpChange $op */
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), $op->getNewValue(), $path );
			} else {
				throw new MWException( 'Invalid diffOp type' );
			}
		} else {
			$html = '';
			foreach ( $op as $key => $subOp ) {
				$html .= $this->generateOpHtml(
					array_merge( $path, array( $key ) ),
					$subOp
				);
			}
		}

		return $html;
	}

	/**
	 * Generates HTML for an change diffOp
	 *
	 * @param string|null $oldValue
	 * @param string|null $newValue
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function generateChangeOpHtml( $oldValue, $newValue, array $path ) {
		//TODO: use WordLevelDiff!
		$html = Html::openElement( 'tr' );
		if ( $oldValue !== null ) {
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
				Html::rawElement( 'div', [], $this->getDeletedLine( $oldValue, $path ) ) );
		}
		if ( $newValue !== null ) {
			if ( $oldValue === null ) {
				$html .= Html::rawElement( 'td', array( 'colspan' => '2' ), '&nbsp;' );
			}
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				Html::rawElement( 'div', [], $this->getAddedLine( $newValue, $path ) ) );
		}
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * @param string $value
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function getDeletedLine( $value, array $path ) {
		return $this->getChangedLine( 'del', $value, $path );
	}

	/**
	 * @param string $value
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function getAddedLine( $value, array $path ) {
		return $this->getChangedLine( 'ins', $value, $path );
	}

	/**
	 * @param string $tag
	 * @param string $value
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function getChangedLine( $tag, $value, array $path ) {
		// @todo: inject a formatter instead of doing special cases based on the path here!
		if ( $path[0] === $this->getLanguage()->getMessage( 'wikibase-diffview-link' ) ) {
			if ( $path[2] === 'badges' ) {
				$value = $this->getBadgeLinkElement( $value );
			} else {
				$value = $this->getSiteLinkElement( $path[1], $value );
			}
			return Html::rawElement( $tag, array( 'class' => 'diffchange diffchange-inline' ), $value );
		}
		return Html::element( $tag, array( 'class' => 'diffchange diffchange-inline' ), $value );
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return string
	 */
	private function getSiteLinkElement( $siteId, $pageName ) {
		$site = $this->siteStore->getSite( $siteId );

		$tagName = 'span';
		$attrs = array(
			'dir' => 'auto',
		);

		if ( $site instanceof Site ) {
			// Otherwise it may have been deleted from the sites table
			$tagName = 'a';
			$attrs['href'] = $site->getPageUrl( $pageName );
			$attrs['hreflang'] = $site->getLanguageCode();
		}

		return Html::element( $tagName, $attrs, $pageName );
	}

	/**
	 * @param string $idString
	 *
	 * @return string HTML
	 */
	private function getBadgeLinkElement( $idString ) {
		try {
			$itemId = new ItemId( $idString );
		} catch ( InvalidArgumentException $ex ) {
			return htmlspecialchars( $idString );
		}

		return $this->entityIdFormatter->formatEntityId( $itemId );
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	private function generateDiffHeaderHtml( $name ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', array( 'colspan' => '2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::element( 'td', array( 'colspan' => '2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

}
