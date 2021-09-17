<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Content;

use InvalidArgumentException;
use MediaWiki\Interwiki\InterwikiLookup;
use MWException;
use OutOfBoundsException;
use Title;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ContentHandlerEntityTitleLookup implements EntityTitleStoreLookup {

	private $titleForIdCache;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var DatabaseEntitySource
	 */
	private $localEntitySource;

	/**
	 * @var InterwikiLookup
	 */
	private $interwikiLookup;

	public function __construct(
		EntityContentFactory $entityContentFactory,
		EntitySourceDefinitions $entitySourceDefinitions,
		DatabaseEntitySource $localEntitySource,
		InterwikiLookup $interwikiLookup = null
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->localEntitySource = $localEntitySource;
		$this->interwikiLookup = $interwikiLookup;
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @throws MWException
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function getTitleForId( EntityId $id ): ?Title {
		if ( isset( $this->titleForIdCache[ $id->getSerialization() ] ) ) {
			return $this->titleForIdCache[ $id->getSerialization() ];
		}
		$title = $this->getTitleForFederatedId( $id );
		if ( $title ) {
			return $title;
		}

		$handler = $this->entityContentFactory->getContentHandlerForType( $id->getEntityType() );
		$title = $handler->getTitleForId( $id );
		$this->titleForIdCache[ $id->getSerialization() ] = $title;
		return $title;
	}

	/**
	 * If the EntityId is federated, return a Title for it. Otherwise return null
	 */
	private function getTitleForFederatedId( EntityId $id ): ?Title {
		if ( $this->entityNotFromLocalEntitySource( $id ) ) {
			$interwiki = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $id->getEntityType() )->getInterwikiPrefix();
			if ( $this->interwikiLookup && $this->interwikiLookup->isValidInterwiki( $interwiki ) ) {
				$pageName = 'EntityPage/' . $id->getSerialization();

				// TODO: use a TitleFactory
				$title = Title::makeTitle( NS_SPECIAL, $pageName, '', $interwiki );
				$this->titleForIdCache[ $id->getSerialization() ] = $title;
				return $title;
			}
		}

		return null;
	}

	/**
	 * Returns Title objects for the entities with provided ids
	 *
	 * @param EntityId[] $ids
	 *
	 * @throws MWException
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 * @return Title[]
	 */
	public function getTitlesForIds( array $ids ): array {
		Assert::parameterElementType( EntityId::class, $ids, '$ids' );
		$titles = [];
		$idsByType = [];
		// get whatever federated ids or cached ids we can, and batch the rest of the ids by type
		foreach ( $ids as $id ) {
			$idString = $id->getSerialization();
			if ( isset( $this->titleForIdCache[$idString] ) ) {
				$titles[$idString] = $this->titleForIdCache[$idString];
				continue;
			}
			$title = $this->getTitleForFederatedId( $id );
			if ( $title ) {
				$titles[$idString] = $title;
				continue;
			}
			$idsByType[ $id->getEntityType() ][] = $id;
		}

		foreach ( $idsByType as $entityType => $idsForType ) {
			$handler = $this->entityContentFactory->getContentHandlerForType( $entityType );
			$titlesForType = $handler->getTitlesForIds( $idsForType );
			$titles += $titlesForType;
		}

		foreach ( $titles as $idString => $title ) {
			$this->titleForIdCache[$idString] = $title;
		}

		return $titles;
	}

	private function entityNotFromLocalEntitySource( EntityId $id ): bool {
		$entitySource = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $id->getEntityType() );
		return $entitySource->getSourceName() !== $this->localEntitySource->getSourceName();
	}

}
