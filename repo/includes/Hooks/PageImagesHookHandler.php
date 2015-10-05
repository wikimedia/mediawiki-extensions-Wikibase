<?php

namespace Wikibase\Repo\Hooks;

use DataValues\StringValue;
use InvalidArgumentException;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Code to make the PageImages extension aware of pages in the Wikibase namespaces.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class PageImagesHookHandler {

	/**
	 * @var PropertyId[]
	 */
	private $imagePropertyIds = array();

	/**
	 * @param string[] $imagePropertyIds
	 */
	function __construct( array $imagePropertyIds ) {
		// TODO: Make this a setting and inject.
		$ids = $imagePropertyIds ?: array(
			'P18',
			'P41',
			'P94',
			'P154',
			'P1766',
			'P14',
			'P158',
			'P1543',
			'P109',
			'P367',
			'P996',
			'P1621',
			'P15',
			'P1846',
			'P181',
			'P242',
			'P1944',
			'P1943',
			'P207',
			'P117',
			'P692',
			'P491',
		);
		$this->addPropertyIds( $ids );
	}

	/**
	 * @param string[] $ids
	 */
	private function addPropertyIds( array $ids ) {
		foreach ( $ids as $id ) {
			try {
				$this->imagePropertyIds[] = new PropertyId( $id );
			} catch ( InvalidArgumentException $ex ) {
				// TODO: Log configuration mistake!
			}
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return string|null The file's page name without the NS_FILE namespace, or null if not found.
	 */
	private function getBestImageFileNameByTitle( Title $title ) {
		// TODO: Is this needed?
		return null;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string|null The file's page name without the NS_FILE namespace, or null if not found.
	 */
	private function getBestImageFileNameByEntityId( EntityId $entityId ) {
		// TODO: Is this needed?
		return null;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string|null The file's page name without the NS_FILE namespace, or null if not found.
	 */
	private function getBestImageFileNameByEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			return $this->getBestImageFileName( $entity->getStatements() );
		}

		return null;
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return string|null The file's page name without the NS_FILE namespace, or null if not found.
	 */
	public function getBestImageFileName( StatementList $statements ) {
		foreach ( $this->imagePropertyIds as $propertyId ) {
			$best = $statements->getByPropertyId( $propertyId )->getBestStatements();
			$snak = $this->getFirstValueSnak( $best );

			if ( $snak !== null ) {
				$value = $snak->getDataValue();

				if ( $value instanceof StringValue ) {
					return $value->getValue();
				}
			}
		}

		return null;
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return PropertyValueSnak|null
	 */
	private function getFirstValueSnak( StatementList $statements ) {
		foreach ( $statements->toArray() as $statement ) {
			$snak = $statement->getMainSnak();

			if ( $snak instanceof PropertyValueSnak ) {
				return $snak;
			}
		}

		return null;
	}

}
