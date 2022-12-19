<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use MWException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;

/**
 * Represents a lookup database table for sitelinks.
 * It should have these fields: ips_item_id, ips_site_id, ips_site_page.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SiteLinkTable implements SiteLinkStore {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var bool
	 */
	protected $readonly;

	/** @var RepoDomainDb */
	private $db;

	/**
	 * @param string $table The table to use for the sitelinks
	 * @param bool $readonly Whether the table can be modified.
	 * @param RepoDomainDb $db
	 */
	public function __construct(
		string $table,
		bool $readonly,
		RepoDomainDb $db
	) {
		$this->table = $table;
		$this->readonly = $readonly;
		$this->db = $db;
		// TODO: Inject
		$this->logger = LoggerFactory::getInstance( 'Wikibase' );
	}

	/**
	 * @param SiteLink[] $siteLinks1
	 * @param SiteLink[] $siteLinks2
	 *
	 * @return SiteLink[]
	 */
	private function diffSiteLinks( array $siteLinks1, array $siteLinks2 ): array {
		return array_udiff(
			$siteLinks1,
			$siteLinks2,
			function( SiteLink $a, SiteLink $b ) {
				$result = strcmp( $a->getSiteId(), $b->getSiteId() );

				if ( $result === 0 ) {
					$result = strcmp( $a->getPageName(), $b->getPageName() );
				}

				return $result;
			}
		);
	}

	public function saveLinksOfItem( Item $item ): bool {
		//First check whether there's anything to update
		$newLinks = $item->getSiteLinkList()->toArray();
		$oldLinks = $this->getSiteLinksForItem( $item->getId() );

		$linksToInsert = $this->diffSiteLinks( $newLinks, $oldLinks );
		$linksToDelete = $this->diffSiteLinks( $oldLinks, $newLinks );

		if ( !$linksToInsert && !$linksToDelete ) {
			$this->logger->debug(
				'{method}: links did not change, returning.',
				[
					'method' => __METHOD__,
				]
			);
			return true;
		}

		$ok = true;
		$dbw = $this->db->connections()->getWriteConnection();

		if ( $linksToDelete ) {
			$this->logger->debug(
				'{method}: {linksToDeleteCount} links to delete.',
				[
					'method' => __METHOD__,
					'linksToDeleteCount' => count( $linksToDelete ),
				]
			);
			$ok = $this->deleteLinks( $item, $linksToDelete, $dbw );
		}

		if ( $ok && $linksToInsert ) {
			$this->logger->debug(
				'{method}: {linksToInsertCount} links to insert.',
				[
					'method' => __METHOD__,
					'linksToInsertCount' => count( $linksToInsert ),
				]
			);
			$ok = $this->insertLinks( $item, $linksToInsert, $dbw );
		}

		return $ok;
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] $links
	 * @param IDatabase $dbw
	 *
	 * @return bool Success indicator
	 */
	private function insertLinks( Item $item, array $links, IDatabase $dbw ): bool {
		$this->logger->debug(
			'{method}: inserting links for {entityId}',
			[
				'method' => __METHOD__,
				'entityId' => $item->getId()->getSerialization(),
			]
		);

		$insert = [];
		foreach ( $links as $siteLink ) {
			$insert[] = [
				'ips_item_id' => $item->getId()->getNumericId(),
				'ips_site_id' => $siteLink->getSiteId(),
				'ips_site_page' => $siteLink->getPageName(),
			];
		}

		$dbw->insert(
			$this->table,
			$insert,
			__METHOD__,
			[ 'IGNORE' ]
		);

		return $dbw->affectedRows() ? true : false;
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] $links
	 * @param IDatabase $dbw
	 *
	 * @return bool Success indicator
	 */
	private function deleteLinks( Item $item, array $links, IDatabase $dbw ): bool {
		$this->logger->debug(
			'{method}: deleting links for {entityId}',
			[
				'method' => __METHOD__,
				'entityId' => $item->getId()->getSerialization(),
			]
		);

		$siteIds = [];
		foreach ( $links as $siteLink ) {
			$siteIds[] = $siteLink->getSiteId();
		}

		$dbw->delete(
			$this->table,
			[
				'ips_item_id' => $item->getId()->getNumericId(),
				'ips_site_id' => $siteIds,
			],
			__METHOD__
		);

		return true;
	}

	/**
	 * @see SiteLinkStore::deleteLinksOfItem
	 *
	 * @param ItemId $itemId
	 *
	 * @return boolean Success indicator
	 * @throws MWException
	 */
	public function deleteLinksOfItem( ItemId $itemId ): bool {
		if ( $this->readonly ) {
			throw new MWException( 'Cannot write when in readonly mode' );
		}

		$dbw = $this->db->connections()->getWriteConnection();

		$dbw->delete(
			$this->table,
			[ 'ips_item_id' => $itemId->getNumericId() ],
			__METHOD__
		);

		return true;
	}

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @todo may want to deprecate this or change it to always return entity id object only
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @throws InvalidArgumentException if a parameter does not have the expected type
	 * @return ItemId|null
	 */
	public function getItemIdForLink( string $globalSiteId, string $pageTitle ): ?ItemId {
		Assert::parameterType( 'string', $globalSiteId, '$globalSiteId' );
		Assert::parameterType( 'string', $pageTitle, '$pageTitle' );

		// We store page titles with spaces instead of underscores
		$pageTitle = str_replace( '_', ' ', $pageTitle );

		$dbr = $this->db->connections()->getReadConnection();

		$result = $dbr->newSelectQueryBuilder()
			->select( 'ips_item_id' )
			->from( $this->table )
			->where( [
				'ips_site_id' => $globalSiteId,
				'ips_site_page' => $pageTitle,
			] )
			->caller( __METHOD__ )
			->fetchRow();

		return $result === false ? null : ItemId::newFromNumber( (int)$result->ips_item_id );
	}

	public function getItemIdForSiteLink( SiteLink $siteLink ): ?ItemId {
		return $this->getItemIdForLink( $siteLink->getSiteId(), $siteLink->getPageName() );
	}

	/**
	 * @note The arrays returned by this method do not contain badges!
	 */
	public function getLinks(
		?array $numericIds = null,
		?array $siteIds = null,
		?array $pageNames = null
	): array {
		$conditions = [];

		if ( $numericIds !== null ) {
			$conditions['ips_item_id'] = $numericIds;
		}

		if ( $siteIds !== null ) {
			$conditions['ips_site_id'] = $siteIds;
		}

		if ( $pageNames !== null ) {
			$conditions['ips_site_page'] = $pageNames;
		}

		foreach ( $conditions as $condition ) {
			if ( $condition === [] ) {
				return [];
			}
		}

		if ( $numericIds === null && $pageNames === null ) {
			$this->logger->warning(
				__METHOD__ . ': querying for all links of one or more sites, this is expensive! (T276762)',
				[
					'siteIds' => $siteIds,
					'exception' => new RuntimeException(),
				]
			);
		}

		$dbr = $this->db->connections()->getReadConnection();
		$links = $dbr->newSelectQueryBuilder()
			->select( [
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			] )
			->from( $this->table )
			->where( $conditions )
			->caller( __METHOD__ )
			->fetchResultSet();

		$siteLinks = [];

		foreach ( $links as $link ) {
			$siteLinks[] = [
				$link->ips_site_id,
				$link->ips_site_page,
				$link->ips_item_id,
			];
		}

		return $siteLinks;
	}

	/**
	 * @see SiteLinkLookup::getSiteLinksForItem
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 * @note The SiteLink objects returned by this method do not contain badges!
	 */
	public function getSiteLinksForItem( ItemId $itemId ): array {
		$numericId = $itemId->getNumericId();

		$dbr = $this->db->connections()->getReadConnection();

		$rows = $dbr->newSelectQueryBuilder()
			->select( [ 'ips_site_id', 'ips_site_page' ] )
			->from( $this->table )
			->where( [ 'ips_item_id' => $numericId ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$siteLinks = [];

		foreach ( $rows as $row ) {
			$siteLinks[] = new SiteLink( $row->ips_site_id, $row->ips_site_page );
		}

		return $siteLinks;
	}

	/**
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @throws InvalidArgumentException if a parameter does not have the expected type
	 * @return EntityId|null
	 */
	public function getEntityIdForLinkedTitle( $globalSiteId, $pageTitle ): ?EntityId {
		return $this->getItemIdForLink( $globalSiteId, $pageTitle );
	}

}
