<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiUsageException;
use Profiler;
use Site;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\StringNormalizer;

/**
 * Helper class for api modules to resolve page+title pairs into entities.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Addshore
 * @author Daniel Kinzler
 */
class EntityByTitleHelper {

	/**
	 * @var ApiBase
	 */
	private $apiModule;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntityByLinkedTitleLookup
	 */
	private $entityByLinkedTitleLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct(
		ApiBase $apiModule,
		ResultBuilder $resultBuilder,
		EntityByLinkedTitleLookup $entityByLinkedTitleLookup,
		SiteLookup $siteLookup,
		StringNormalizer $stringNormalizer
	) {
		$this->apiModule = $apiModule;
		$this->resultBuilder = $resultBuilder;
		$this->entityByLinkedTitleLookup = $entityByLinkedTitleLookup;
		$this->siteLookup = $siteLookup;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * Tries to find item ids for given client pages.
	 *
	 * @param array $sites
	 * @param array $titles
	 * @param bool $normalize
	 *
	 * @throws ApiUsageException
	 * @return array[] ( EntityId[], array[] )
	 *         List containing valid $ids and $missingEntities site title combinations
	 * @phan-return array{0:EntityId[],1:array[]}
	 */
	public function getEntityIds( array $sites, array $titles, $normalize ) {
		$ids = [];
		$numSites = count( $sites );
		$numTitles = count( $titles );

		// Make sure the arrays of sites and titles are not empty
		if ( $numSites === 0 || $numTitles === 0 ) {
			$this->throwUsageException(
				'Must request one site, one title, or an equal number of sites and titles',
				'param-missing'
			);
		}

		if ( $normalize && max( $numSites, $numTitles ) > 1 ) {
			// For performance reasons we only do this if the user asked for it and only for one title!
			$this->throwUsageException(
				'Normalize is only allowed if exactly one site and one page have been given',
				'params-illegal'
			);
		}

		// Restrict the crazy combinations of sites and titles that can be used
		if ( $numSites !== 1 && $numTitles !== 1 && $numSites !== $numTitles ) {
			$this->throwUsageException(
				'Must request one site, one title, or an equal number of sites and titles',
				'params-illegal'
			);
		}

		$missingEntities = [];
		foreach ( $sites as $siteId ) {
			foreach ( $titles as $title ) {
				$itemId = $this->getEntityId( $siteId, $title, $normalize );
				if ( $itemId !== null ) {
					$ids[] = $itemId;
				} else {
					$missingEntities[] = [ 'site' => $siteId, 'title' => $title ];
				}
			}
		}

		return [ $ids, $missingEntities ];
	}

	/**
	 * Tries to find item id for given siteId and title combination
	 *
	 * @param string $siteId
	 * @param string $title
	 * @param bool $normalize
	 *
	 * @return EntityId|null
	 */
	private function getEntityId( $siteId, $title, $normalize ) {
		// FIXME: This code is duplicated in SpecialItemByTitle::execute!
		$title = $this->stringNormalizer->trimToNFC( $title );
		$id = $this->entityByLinkedTitleLookup->getEntityIdForLinkedTitle( $siteId, $title );

		// Try harder by requesting normalization on the external site.
		if ( $id === null && $normalize === true ) {
			$siteObj = $this->siteLookup->getSite( $siteId ); // XXX: is this really needed??
			//XXX: this passes the normalized title back into $title by reference...
			$this->normalizeTitle( $title, $siteObj );
			$id = $this->entityByLinkedTitleLookup->getEntityIdForLinkedTitle( $siteId, $title );
		}

		return $id;
	}

	/**
	 * Tries to normalize the given page title against the given client site.
	 * Updates $title accordingly and adds the normalization to the API output.
	 *
	 * @param string &$title
	 * @param Site $site
	 */
	public function normalizeTitle( &$title, Site $site ) {
		$normalizedTitle = $site->normalizePageName( $title );
		if ( $normalizedTitle !== false && $normalizedTitle !== $title ) {
			// @todo this should not be in the helper but in the module itself...
			$this->resultBuilder->addNormalizedTitle( $title, $normalizedTitle );
			//XXX: the below is an ugly hack as we pass title by reference...
			$title = $normalizedTitle;
		}
	}

	/**
	 * @param string $message
	 * @param string $code
	 *
	 * @throws ApiUsageException always
	 */
	private function throwUsageException( $message, $code ) {
		Profiler::instance()->close();
		throw ApiUsageException::newWithMessage( $this->apiModule, $message, $code );
	}

}
