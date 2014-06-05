<?php

namespace Wikibase\DataModel\Entity;

use Diff\Comparer\CallbackComparer;
use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\ListPatcher;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Represents a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Item extends Entity {

	const ENTITY_TYPE = 'item';

	/**
	 * @var SiteLinkList
	 */
	private $siteLinks;

	/**
	 * @var Statement[]
	 */
	private $statements;

	/**
	 * @since 1.0
	 *
	 * @param ItemId|null $id
	 * @param Fingerprint $fingerprint
	 * @param SiteLinkList $links
	 * @param Statement[] $statements
	 */
	public function __construct( ItemId $id = null, Fingerprint $fingerprint, SiteLinkList $links, array $statements ) {
		$this->id = $id;
		$this->fingerprint = $fingerprint;
		$this->siteLinks = $links;
		$this->statements = $statements;
	}

	/**
	 * @since 0.8
	 *
	 * @return SiteLinkList
	 */
	public function getSiteLinkList() {
		return $this->siteLinks;
	}

	/**
	 * @since 0.8
	 *
	 * @param SiteLinkList $siteLinks
	 */
	public function setSiteLinkList( SiteLinkList $siteLinks ) {
		$this->siteLinks = $siteLinks;
	}

	/**
	 * Adds a site link to the list of site links.
	 * If there already is a site link with the site id of the provided site link,
	 * then that one will be overridden by the provided one.
	 *
	 * @deprecated since 0.8, use getSiteLinkList and setSiteLinkList instead
	 * @since 0.6
	 *
	 * @param SiteLink $siteLink
	 */
	public function addSiteLink( SiteLink $siteLink ) {
		if ( $this->siteLinks->hasLinkWithSiteId( $siteLink->getSiteId() ) ) {
			$this->siteLinks->removeLinkWithSiteId( $siteLink->getSiteId() );
		}

		$this->siteLinks->addSiteLink( $siteLink );
	}

	/**
	 * Removes the sitelink with specified site ID if the Item has such a sitelink.
	 *
	 * @deprecated since 0.8, use getSiteLinkList and setSiteLinkList instead
	 * @since 0.1
	 *
	 * @param string $siteId the target site's id
	 */
	public function removeSiteLink( $siteId ) {
		$this->siteLinks->removeLinkWithSiteId( $siteId );
	}

	/**
	 * @deprecated since 0.8, use getSiteLinkList and setSiteLinkList instead
	 * @since 0.6
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinks() {
		return iterator_to_array( $this->siteLinks );
	}

	/**
	 * @deprecated since 0.8, use getSiteLinkList and setSiteLinkList instead
	 * @since 0.6
	 *
	 * @param string $siteId
	 *
	 * @return SiteLink
	 * @throws OutOfBoundsException
	 */
	public function getSiteLink( $siteId ) {
		return $this->siteLinks->getBySiteId( $siteId );
	}

	/**
	 * @deprecated since 0.8, use getSiteLinkList and setSiteLinkList instead
	 * @since 0.4
	 *
	 * @param string $siteId
	 *
	 * @return bool
	 */
	public function hasLinkToSite( $siteId ) {
		return $this->siteLinks->hasLinkWithSiteId( $siteId );
	}

	/**
	 * @deprecated since 0.8, use getSiteLinkList and setSiteLinkList instead
	 * @since 0.5
	 *
	 * @return bool
	 */
	public function hasSiteLinks() {
		return !empty( $this->siteLinks );
	}

	/**
	 * @since 0.1
	 *
	 * @return Item
	 */
	public static function newEmpty() {
		return new self(
			null,
			Fingerprint::newEmpty(),
			new SiteLinkList( array() ),
			array()
		);
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * @see Entity::newClaim
	 *
	 * @since 0.3
	 *
	 * @param Snak $mainSnak
	 *
	 * @return Statement
	 */
	public function newClaim( Snak $mainSnak ) {
		return new Statement( $mainSnak );
	}

	/**
	 * @see Entity::getDiffArray
	 *
	 * @return array
	 */
	protected function getDiffArray() {
		$array = parent::getDiffArray();

		$array['links'] = $this->getLinksInDiffFormat();

		return $array;
	}

	private function getLinksInDiffFormat() {
		$links = array();

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $this->siteLinks as $siteLink ) {
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

	/**
	 * @see Entity::patchSpecificFields
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $patch
	 */
	protected function patchSpecificFields( EntityDiff $patch ) {
		if ( $patch instanceof ItemDiff ) {
			if ( !$patch->getSiteLinkDiff()->isEmpty() ) {
				$this->patchSiteLinks( $patch->getSiteLinkDiff() );
			}

			$this->patchClaims( $patch );
		}
	}

	private function patchSiteLinks( Diff $siteLinksDiff ) {
		$patcher = new MapPatcher( false, new ListPatcher() );

		$links = $this->getLinksInDiffFormat();
		$links = $patcher->patch( $links, $siteLinksDiff );

		$this->siteLinks = new SiteLinkList();

		foreach ( $links as $siteId => $linkData ) {
			if ( array_key_exists( 'name', $linkData ) ) {
				$this->siteLinks->addSiteLink( new SiteLink(
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
	}

	private function patchClaims( EntityDiff $patch ) {
		$patcher = new MapPatcher();

		$patcher->setValueComparer( new CallbackComparer(
			function( Claim $firstClaim, Claim $secondClaim ) {
				return $firstClaim->equals( $secondClaim );
			}
		) );

		$claims = array();

		foreach ( $this->getClaims() as $claim ) {
			$claims[$claim->getGuid()] = $claim;
		}

		$claims = $patcher->patch( $claims, $patch->getClaimsDiff() );

		$this->setClaims( new Claims( $claims ) );
	}

	/**
	 * @since 0.5
	 *
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 */
	protected function idFromSerialization( $idSerialization ) {
		return new ItemId( $idSerialization );
	}

	/**
	 * @see ClaimListAccess::addClaim
	 *
	 * @since 0.3
	 *
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 */
	public function addClaim( Claim $claim ) {
		if ( $claim->getGuid() === null ) {
			throw new InvalidArgumentException( 'Can\'t add a Claim without a GUID.' );
		}

		// TODO: ensure guid is valid for entity

		$this->statements[] = $claim;
	}

	/**
	 * @see ClaimAggregate::getClaims
	 *
	 * @since 0.3
	 *
	 * @return Claim[]
	 */
	public function getClaims() {
		return $this->statements;
	}

	/**
	 * TODO: change to take Claim[]
	 *
	 * @since 0.4
	 *
	 * @param Claims $claims
	 */
	public function setClaims( Claims $claims ) {
		$this->statements = iterator_to_array( $claims );
	}

	/**
	 * Convenience function to check if the entity contains any claims.
	 *
	 * On top of being a convenience function, this implementation allows for doing
	 * the check without forcing an unstub in contrast to count( $this->getClaims() ).
	 *
	 * @since 0.2
	 *
	 * @return bool
	 */
	public function hasClaims() {
		return !empty( $this->statements );
	}

	/**
	 * @see Comparable::equals
	 *
	 * Two items are considered equal if they are of the same
	 * type and have the same value. The value does not include
	 * the id, so entities with the same value but different id
	 * are considered equal.
	 *
	 * @since 0.1
	 *
	 * @param mixed $that
	 *
	 * @return boolean
	 */
	public function equals( $that ) {
		if ( !( $that instanceof self ) ) {
			return false;
		}

		if ( $that === $this ) {
			return true;
		}

		/**
		 * @var $that Item
		 */
		return $this->fingerprint->equals( $that->fingerprint )
			&& $this->siteLinks->equals( $that->siteLinks )
			&& $this->statementsEqual( $that->statements );
	}

	private function statementsEqual( array $statements ) {
		$list = new Claims( $this->statements );
		return $list->equals( new Claims( $statements ) );
	}

}
