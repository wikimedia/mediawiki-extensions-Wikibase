<?php

namespace Wikibase\DataModel\Tests;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Immutable Wikibase entity builder.
 *
 * @license GPL-2.0-or-later
 */
class NewItem {

	/**
	 * @var ItemId
	 */
	private $itemId;

	/**
	 * @var string[] Indexed by language
	 */
	private $labels = [];

	/**
	 * @var string[] Indexed by language
	 */
	private $descriptions = [];

	/**
	 * @var array[] Indexed by language on the first level
	 */
	private $aliases = [];

	/**
	 * @var SiteLink[]
	 */
	private $siteLinks = [];

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	private function __construct() {
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 */
	public function __clone() {
		// Statements are mutable and must be cloned individually, because there is no StatementList
		// taking care of this.
		foreach ( $this->statements as &$statement ) {
			$statement = clone $statement;
		}
		// All other members are immutable.
	}

	/**
	 * @return Item
	 */
	public function build() {
		$item = new Item( $this->itemId );

		foreach ( $this->labels as $language => $label ) {
			$item->setLabel( $language, $label );
		}
		foreach ( $this->descriptions as $language => $description ) {
			$item->setDescription( $language, $description );
		}
		foreach ( $this->aliases as $language => $aliases ) {
			$item->setAliases( $language, $aliases );
		}
		foreach ( $this->siteLinks as $siteLink ) {
			$item->getSiteLinkList()->addSiteLink( $siteLink );
		}
		foreach ( $this->statements as $statement ) {
			$item->getStatements()->addStatement( $statement );
		}

		return $item;
	}

	/**
	 * @see andId
	 */
	public static function withId( $itemId ) {
		return ( new self() )->andId( $itemId );
	}

	/**
	 * @param ItemId|string $itemId
	 *
	 * @return self
	 */
	public function andId( $itemId ) {
		$copy = clone $this;
		if ( !( $itemId instanceof ItemId ) ) {
			$itemId = new ItemId( $itemId );
		}
		$copy->itemId = $itemId;
		return $copy;
	}

	/**
	 * @see andLabel
	 */
	public static function withLabel( $languageCode, $label ) {
		return ( new self() )->andLabel( $languageCode, $label );
	}

	/**
	 * @param string $languageCode
	 * @param string $label
	 *
	 * @return self
	 */
	public function andLabel( $languageCode, $label ) {
		$copy = clone $this;
		$copy->labels[$languageCode] = $label;
		return $copy;
	}

	/**
	 * @see andDescription
	 */
	public static function withDescription( $languageCode, $description ) {
		return ( new self() )->andDescription( $languageCode, $description );
	}

	/**
	 * @param string $languageCode
	 * @param string $description
	 *
	 * @return self
	 */
	public function andDescription( $languageCode, $description ) {
		$copy = clone $this;
		$copy->descriptions[$languageCode] = $description;
		return $copy;
	}

	/**
	 * @see andAliases
	 */
	public static function withAliases( $languageCode, $aliases ) {
		return ( new self() )->andAliases( $languageCode, $aliases );
	}

	/**
	 * @param string $languageCode
	 * @param string[]|string $aliases
	 *
	 * @return self
	 */
	public function andAliases( $languageCode, $aliases ) {
		$copy = clone $this;
		$copy->aliases[$languageCode] = (array)$aliases;
		return $copy;
	}

	/**
	 * @see andSiteLink
	 */
	public static function withSiteLink( $siteId, $pageName, $badges = null ) {
		return ( new self() )->andSiteLink( $siteId, $pageName, $badges );
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param ItemId[]|string[]|ItemId|string|null $badges Zero or more item ID references as either
	 *  strings or ItemId objects. Can be an array or a single value.
	 *
	 * @return self
	 */
	public function andSiteLink( $siteId, $pageName, $badges = null ) {
		$copy = clone $this;
		if ( $badges !== null ) {
			$badges = array_map( function ( $badge ) {
				return $badge instanceof ItemId ? $badge : new ItemId( $badge );
			}, (array)$badges );
		}
		$copy->siteLinks[] = new SiteLink( $siteId, $pageName, $badges );
		return $copy;
	}

	/**
	 * @see andStatement
	 */
	public static function withStatement( $statement ) {
		return ( new self() )->andStatement( $statement );
	}

	/**
	 * @param NewStatement|Statement|Snak $statement
	 *
	 * @return self
	 */
	public function andStatement( $statement ) {
		$copy = clone $this;
		if ( $statement instanceof NewStatement ) {
			$statement = $statement->build();
		} elseif ( $statement instanceof Snak ) {
			$statement = new Statement( $statement );
		}
		$copy->statements[] = clone $statement;
		return $copy;
	}

}
