<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * Represents a single Wikibase item.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#Items
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class Item implements
	StatementListProvidingEntity,
	FingerprintProvider,
	StatementListHolder,
	LabelsProvider,
	DescriptionsProvider,
	AliasesProvider,
	ClearableEntity
{

	public const ENTITY_TYPE = 'item';

	/**
	 * @var ItemId|null
	 */
	private $id;

	/**
	 * @var Fingerprint
	 */
	private $fingerprint;

	/**
	 * @var SiteLinkList
	 */
	private $siteLinks;

	/**
	 * @var StatementList
	 */
	private $statements;

	/**
	 * @since 1.0
	 *
	 * @param ItemId|null $id
	 * @param Fingerprint|null $fingerprint
	 * @param SiteLinkList|null $siteLinks
	 * @param StatementList|null $statements
	 */
	public function __construct(
		ItemId $id = null,
		Fingerprint $fingerprint = null,
		SiteLinkList $siteLinks = null,
		StatementList $statements = null
	) {
		$this->id = $id;
		$this->fingerprint = $fingerprint ?: new Fingerprint();
		$this->siteLinks = $siteLinks ?: new SiteLinkList();
		$this->statements = $statements ?: new StatementList();
	}

	/**
	 * Returns the id of the entity or null if it does not have one.
	 *
	 * @since 0.1 return type changed in 0.3
	 *
	 * @return ItemId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @since 0.5, can be null since 1.0
	 *
	 * @param ItemId|null $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		if ( !( $id instanceof ItemId ) && $id !== null ) {
			throw new InvalidArgumentException( '$id must be an ItemId or null' );
		}

		$this->id = $id;
	}

	/**
	 * @since 0.7.3
	 *
	 * @return Fingerprint
	 */
	public function getFingerprint() {
		return $this->fingerprint;
	}

	/**
	 * @since 0.7.3
	 *
	 * @param Fingerprint $fingerprint
	 */
	public function setFingerprint( Fingerprint $fingerprint ) {
		$this->fingerprint = $fingerprint;
	}

	/**
	 * @see LabelsProvider::getLabels
	 *
	 * @since 6.0
	 *
	 * @return TermList
	 */
	public function getLabels() {
		return $this->fingerprint->getLabels();
	}

	/**
	 * @see DescriptionsProvider::getDescriptions
	 *
	 * @since 6.0
	 *
	 * @return TermList
	 */
	public function getDescriptions() {
		return $this->fingerprint->getDescriptions();
	}

	/**
	 * @see AliasesProvider::getAliasGroups
	 *
	 * @since 6.0
	 *
	 * @return AliasGroupList
	 */
	public function getAliasGroups() {
		return $this->fingerprint->getAliasGroups();
	}

	/**
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function setLabel( $languageCode, $value ) {
		$this->fingerprint->setLabel( $languageCode, $value );
	}

	/**
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDescription( $languageCode, $value ) {
		$this->fingerprint->setDescription( $languageCode, $value );
	}

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 */
	public function setAliases( $languageCode, array $aliases ) {
		$this->fingerprint->setAliasGroup( $languageCode, $aliases );
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
	 * @since 0.1
	 *
	 * @param string $siteId the target site's id
	 */
	public function removeSiteLink( $siteId ) {
		$this->siteLinks->removeLinkWithSiteId( $siteId );
	}

	/**
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
	 * @deprecated since 2.5, use new Item() instead.
	 *
	 * @return self
	 */
	public static function newEmpty() {
		return new self();
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string Returns the entity type "item".
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * Returns if the Item has no content.
	 * Having an id set does not count as having content.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->fingerprint->isEmpty()
			&& $this->statements->isEmpty()
			&& $this->siteLinks->isEmpty();
	}

	/**
	 * @since 1.0
	 *
	 * @return StatementList
	 */
	public function getStatements() {
		return $this->statements;
	}

	/**
	 * @since 1.0
	 *
	 * @param StatementList $statements
	 */
	public function setStatements( StatementList $statements ) {
		$this->statements = $statements;
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @since 0.1
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->fingerprint->equals( $target->fingerprint )
			&& $this->siteLinks->equals( $target->siteLinks )
			&& $this->statements->equals( $target->statements );
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @since 0.1
	 *
	 * @return self
	 */
	public function copy() {
		return clone $this;
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 *
	 * @since 5.1
	 */
	public function __clone() {
		$this->fingerprint = clone $this->fingerprint;
		// SiteLinkList is mutable, but SiteLink is not. No deeper cloning necessary.
		$this->siteLinks = clone $this->siteLinks;
		$this->statements = clone $this->statements;
	}

	/**
	 * @since 7.5
	 */
	public function clear() {
		$this->fingerprint = new Fingerprint();
		$this->siteLinks = new SiteLinkList();
		$this->statements = new StatementList();
	}

}
