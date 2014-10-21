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
use MWException;
use SiteStore;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Class for generating views of DiffOp objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class DiffView extends ContextSource {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @since 0.1
	 *
	 * @var string[]
	 */
	private $path;

	/**
	 * @since 0.1
	 *
	 * @var Diff
	 */
	private $diff;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string[] $path
	 * @param Diff $diff
	 * @param SiteStore $siteStore
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param IContextSource|null $contextSource
	 */
	public function __construct(
		array $path,
		Diff $diff,
		SiteStore $siteStore,
		EntityTitleLookup $entityTitleLookup,
		EntityRevisionLookup $entityRevisionLookup,
		IContextSource $contextSource = null
	) {
		$this->path = $path;
		$this->diff = $diff;
		$this->siteStore = $siteStore;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;

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
	 * @since 0.1
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
	 * @since 0.4
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
				Html::rawElement( 'div', array(), $this->getDeletedLine( $oldValue, $path ) ) );
		}
		if ( $newValue !== null ) {
			if ( $oldValue === null ) {
				$html .= Html::rawElement( 'td', array( 'colspan' => '2' ), '&nbsp;' );
			}
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				Html::rawElement( 'div', array(), $this->getAddedLine( $newValue, $path ) ) );
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

		return Html::element( 'a', array(
			'href' => $site->getPageUrl( $pageName ),
			'hreflang' => $site->getLanguageCode(),
			'dir' => 'auto',
		), $pageName );
	}

	/**
	 * @param string $badgeId
	 *
	 * @return string
	 */
	private function getBadgeLinkElement( $badgeId ) {
		try {
			$title = $this->entityTitleLookup->getTitleForId( new ItemId( $badgeId ) );
		} catch ( MWException $ex ) {
			wfWarn( "Couldn't get Title for badge $badgeId" );
			return $badgeId;
		}

		return Html::element( 'a', array(
			'href' => $title->getLinkURL(),
			'dir' => 'auto',
		), $this->getLabelForBadge( new ItemId( $badgeId ) ) );
	}

	/**
	 * Returns the title for the given badge id.
	 * @todo use TermLookup when we have one
	 * @todo this is copied from SpecialSetSiteLink
	 *
	 * @param EntityId $badgeId
	 *
	 * @return string
	 */
	private function getLabelForBadge( EntityId $badgeId ) {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision( $badgeId );

		if ( $entityRevision === null ) {
			return $badgeId->getSerialization();
		}

		$languageCode = $this->getLanguage()->getCode();
		$labels = $entityRevision->getEntity()->getFingerprint()->getLabels();

		if ( $labels->hasTermForLanguage( $languageCode ) ) {
			return $labels->getByLanguage( $languageCode )->getText();
		} else {
			return $badgeId->getSerialization();
		}
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @since 0.4
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
