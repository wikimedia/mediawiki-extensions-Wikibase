<?php

namespace Wikibase\Client\Changes;

use ArrayIterator;
use InvalidArgumentException;
use Title;
use Traversable;
use UnexpectedValueException;
use Wikibase\Change;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageAspectTransformer;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class AffectedPagesFinder {

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string
	 */
	private $contentLanguageCode;

	/**
	 * @var bool
	 */
	private $checkPageExistence;

	/**
	 * @param UsageLookup $usageLookup
	 * @param TitleFactory $titleFactory
	 * @param string $siteId
	 * @param string $contentLanguageCode
	 * @param bool $checkPageExistence To disable slow filtering that is not relevant in test
	 *  scenarios. Not meant to be used in production!
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		UsageLookup $usageLookup,
		TitleFactory $titleFactory,
		$siteId,
		$contentLanguageCode,
		$checkPageExistence = true
	) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId must be a string' );
		}

		if ( !is_string( $contentLanguageCode ) ) {
			throw new InvalidArgumentException( '$contentLanguageCode must be a string' );
		}

		if ( !is_bool( $checkPageExistence ) ) {
			throw new InvalidArgumentException( '$checkPageExistence must be a boolean' );
		}

		$this->usageLookup = $usageLookup;
		$this->titleFactory = $titleFactory;
		$this->siteId = $siteId;
		$this->contentLanguageCode = $contentLanguageCode;
		$this->checkPageExistence = $checkPageExistence;
	}

	/**
	 * @param Change $change
	 *
	 * @return PageEntityUsages[]
	 */
	public function getAffectedUsagesByPage( Change $change ) {
		if ( $change instanceof EntityChange ) {
			$usages = $this->getAffectedPages( $change );
			return $this->filterUpdates( $usages );
		}

		return [];
	}

	/**
	 * @param EntityChange $change
	 *
	 * @return string[]
	 */
	public function getChangedAspects( EntityChange $change ) {
		$aspects = [];
		$diffAspects = $change->getCompactDiff();

		if ( $diffAspects->getSiteLinkChanges() !== [] ) {
			$sitelinkChanges = $diffAspects->getSiteLinkChanges();
			$aspects[] = EntityUsage::SITELINK_USAGE;

			if ( isset( $sitelinkChanges[$this->siteId] )
				&& !$this->isBadgesOnlyChange( $sitelinkChanges[$this->siteId] )
			) {
				$aspects[] = EntityUsage::TITLE_USAGE;
			}
		}

		if ( $diffAspects->getLabelChanges() !== [] ) {
			$labelAspects = $this->getChangedTermAspects(
				EntityUsage::LABEL_USAGE,
				$diffAspects->getLabelChanges()
			);
			$aspects = array_merge( $aspects, $labelAspects );
		}

		if ( $diffAspects->getDescriptionChanges() !== [] ) {
			$descriptionsAspects = $this->getChangedTermAspects(
				EntityUsage::DESCRIPTION_USAGE,
				$diffAspects->getDescriptionChanges()
			);
			$aspects = array_merge( $aspects, $descriptionsAspects );
		}

		if ( $diffAspects->getStatementChanges() !== [] ) {
			$statementAspects = $this->getChangedStatementAspects(
				$diffAspects->getStatementChanges()
			);
			$aspects = array_merge( $aspects, $statementAspects );
		}

		if ( $diffAspects->hasOtherChanges() !== false ) {
			$aspects[] = EntityUsage::OTHER_USAGE;
		}

		if ( $aspects === [] ) {
			// This is needed when diff is supressed for performance reasons
			$aspects[] = EntityUsage::OTHER_USAGE;
		}

		return $aspects;
	}

	/**
	 * @param string[] $diff
	 *
	 * @return string[]
	 */
	private function getChangedStatementAspects( array $diff ) {
		$aspects = [];

		foreach ( $diff as $propertyId ) {
			$aspects[] = EntityUsage::makeAspectKey( EntityUsage::STATEMENT_USAGE, $propertyId );
		}

		$aspects[] = EntityUsage::makeAspectKey( EntityUsage::STATEMENT_USAGE );

		return $aspects;
	}

	/**
	 * @param string $aspect
	 * @param string[] $diff
	 *
	 * @return string[]
	 */
	private function getChangedTermAspects( $aspect, array $diff ) {
		$aspects = [];

		foreach ( $diff as $lang ) {
			$aspects[] = EntityUsage::makeAspectKey( $aspect, $lang );
		}

		$aspects[] = EntityUsage::makeAspectKey( $aspect );

		return $aspects;
	}

	/**
	 * Returns the page updates implied by the given the change.
	 *
	 * @param EntityChange $change
	 *
	 * @return Traversable of PageEntityUsages
	 */
	private function getAffectedPages( EntityChange $change ) {
		$entityId = $change->getEntityId();
		$changedAspects = $this->getChangedAspects( $change );

		$usages = $this->usageLookup->getPagesUsing(
			// @todo: more than one entity at once!
			[ $entityId ],
			// Look up pages that are marked as either using one of the changed or all aspects
			array_merge( $changedAspects, [ EntityUsage::ALL_USAGE ] )
		);

		// @todo: use iterators throughout!
		$usages = iterator_to_array( $usages, true );
		$usages = $this->transformAllPageEntityUsages( $usages, $entityId, $changedAspects );

		if ( $change instanceof ItemChange && in_array( EntityUsage::TITLE_USAGE, $changedAspects ) ) {
			$diffChangedAspects = $change->getCompactDiff();
			$namesFromDiff = $this->getPagesReferencedInDiff(
				$diffChangedAspects->getSiteLinkChanges()
			);
			$titlesFromDiff = $this->getTitlesFromTexts( $namesFromDiff );
			$usagesFromDiff = $this->makeVirtualUsages(
				$titlesFromDiff,
				$entityId,
				[ EntityUsage::SITELINK_USAGE ]
			);

			//FIXME: we can't really merge if $usages is an iterator, not an array.
			//TODO: Inject $usagesFromDiff "on the fly" while streaming other usages.
			//NOTE: $usages must pass through mergeUsagesInto for re-indexing
			$mergedUsages = [];
			$this->mergeUsagesInto( $usages, $mergedUsages );
			$this->mergeUsagesInto( $usagesFromDiff, $mergedUsages );
			$usages = $mergedUsages;
		}

		return new ArrayIterator( $usages );
	}

	/**
	 * @param PageEntityUsages[] $from
	 * @param PageEntityUsages[] &$into Array to merge into
	 */
	private function mergeUsagesInto( array $from, array &$into ) {
		foreach ( $from as $pageEntityUsages ) {
			$key = $pageEntityUsages->getPageId();

			if ( isset( $into[$key] ) ) {
				$into[$key]->addUsages( $pageEntityUsages->getUsages() );
			} else {
				$into[$key] = $pageEntityUsages;
			}
		}
	}

	/**
	 * @param array[] $siteLinkDiff
	 *
	 * @throws UnexpectedValueException
	 * @return string[]
	 */
	private function getPagesReferencedInDiff( array $siteLinkDiff ) {
		$pagesToUpdate = [];
		$siteLinkDiffWiki = $siteLinkDiff[$this->siteId];

		if ( $siteLinkDiffWiki[0] !== null ) {
			$pagesToUpdate[] = $siteLinkDiffWiki[0];
		}

		if ( $siteLinkDiffWiki[1] !== null ) {
			$pagesToUpdate[] = $siteLinkDiffWiki[1];
		}

		return $pagesToUpdate;
	}

	/**
	 * @param array $siteLinkDiff
	 *
	 * @return bool
	 */
	private function isBadgesOnlyChange( array $siteLinkDiff ) {
		return ( $siteLinkDiff[0] === $siteLinkDiff[1] && $siteLinkDiff[2] === true );
	}

	/**
	 * Filters updates based on namespace. This removes duplicates, non-existing pages, and pages from
	 * namespaces that are not considered "enabled" by the namespace checker.
	 *
	 * @param Traversable $usages A traversable of PageEntityUsages.
	 *
	 * @return PageEntityUsages[]
	 */
	private function filterUpdates( Traversable $usages ) {
		$titlesToUpdate = [];

		/** @var PageEntityUsages $pageEntityUsages */
		foreach ( $usages as $pageEntityUsages ) {
			try {
				$title = $this->titleFactory->newFromID( $pageEntityUsages->getPageId() );
			} catch ( StorageException $ex ) {
				// page not found, skip
				continue;
			}

			if ( $this->checkPageExistence && !$title->exists() ) {
				continue;
			}

			$key = $pageEntityUsages->getPageId();
			$titlesToUpdate[$key] = $pageEntityUsages;
		}

		return $titlesToUpdate;
	}

	/**
	 * @param string[] $names
	 *
	 * @return Title[]
	 */
	private function getTitlesFromTexts( array $names ) {
		$titles = [];

		foreach ( $names as $name ) {
			try {
				$titles[] = $this->titleFactory->newFromText( $name );
			} catch ( StorageException $ex ) {
				// Invalid title in the diff? Skip.
			}
		}

		return $titles;
	}

	/**
	 * @param Title[] $titles
	 * @param EntityId $entityId
	 * @param string[] $aspects
	 *
	 * @return PageEntityUsages[]
	 */
	private function makeVirtualUsages( array $titles, EntityId $entityId, array $aspects ) {
		$usagesForItem = [];
		foreach ( $aspects as $aspect ) {
			list( $aspect, $modifier ) = EntityUsage::splitAspectKey( $aspect );
			$usagesForItem[] = new EntityUsage( $entityId, $aspect, $modifier );
		}

		$usagesPerPage = [];
		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();

			if ( $pageId === 0 ) {
				wfDebugLog( 'WikibaseChangeNotification', __METHOD__ . ': Article ID for '
					. $title->getFullText() . ' is 0.' );

				continue;
			}

			$usagesPerPage[$pageId] = new PageEntityUsages( $pageId, $usagesForItem );
		}

		return $usagesPerPage;
	}

	/**
	 * @param PageEntityUsages[] $usages
	 * @param EntityId $entityId
	 * @param string[] $changedAspects
	 *
	 * @return PageEntityUsages[]
	 */
	private function transformAllPageEntityUsages( array $usages, EntityId $entityId, array $changedAspects ) {
		$aspectTransformer = new UsageAspectTransformer();
		$aspectTransformer->setRelevantAspects( $entityId, $changedAspects );

		$transformed = [];

		foreach ( $usages as $key => $usagesOnPage ) {
			$transformedUsagesOnPage = $aspectTransformer->transformPageEntityUsages( $usagesOnPage );

			if ( !$transformedUsagesOnPage->isEmpty() ) {
				$transformed[$key] = $transformedUsagesOnPage;
			}
		}

		return $transformed;
	}

}
