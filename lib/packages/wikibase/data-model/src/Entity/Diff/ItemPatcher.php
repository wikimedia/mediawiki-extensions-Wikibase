<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\Comparer\CallbackComparer;
use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\ListPatcher;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemPatcher implements EntityPatcherStrategy {

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === 'item';
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @return Item
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		$this->assertIsItem( $entity );

		return $this->getPatchedItem( $entity, $patch );
	}

	private function assertIsItem( EntityDocument $item ) {
		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( 'All entities need to be items' );
		}
	}

	private function getPatchedItem( Item $item, EntityDiff $patch ) {
		$patcher = new MapPatcher();

		$item->setLabels( $patcher->patch( $item->getLabels(), $patch->getLabelsDiff() ) );
		$item->setDescriptions( $patcher->patch( $item->getDescriptions(), $patch->getDescriptionsDiff() ) );
		$item->setAllAliases( $patcher->patch( $item->getAllAliases(), $patch->getAliasesDiff() ) );

		if ( $patch instanceof ItemDiff ) {
			$this->patchSiteLinks( $item, $patch->getSiteLinkDiff() );
		}

		$this->patchClaims( $item, $patch );

		return $item;
	}

	private function patchSiteLinks( Item $item, Diff $siteLinksDiff ) {
		$patcher = new MapPatcher( false, new ListPatcher() );

		$links = $this->getLinksInDiffFormat( $item );
		$links = $patcher->patch( $links, $siteLinksDiff );

		$siteLinks = new SiteLinkList();

		foreach ( $links as $siteId => $linkData ) {
			if ( array_key_exists( 'name', $linkData ) ) {
				$siteLinks->addSiteLink( new SiteLink(
					$siteId,
					$linkData['name'],
					array_map(
						function( $idSerialization ) {
							return new ItemId( $idSerialization );
						},
						$linkData['badges']
					)
				) );
			}
		}

		$item->setSiteLinkList( $siteLinks );
	}

	private function getLinksInDiffFormat( Item $item ) {
		$links = array();

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$links[$siteLink->getSiteId()] = array(
				'name' => $siteLink->getPageName(),
				'badges' => array_map(
					function( ItemId $id ) {
						return $id->getSerialization();
					},
					$siteLink->getBadges()
				)
			);
		}

		return $links;
	}

	private function patchClaims( Item $item, EntityDiff $patch ) {
		$patcher = new MapPatcher();

		$patcher->setValueComparer( new CallbackComparer(
			function( Claim $firstClaim, Claim $secondClaim ) {
				return $firstClaim->equals( $secondClaim );
			}
		) );

		$claims = array();

		foreach ( $item->getClaims() as $claim ) {
			$claims[$claim->getGuid()] = $claim;
		}

		$claims = $patcher->patch( $claims, $patch->getClaimsDiff() );

		$item->setClaims( new Claims( $claims ) );
	}

}
