<?php

namespace Wikibase\Repo\Tests;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Immutable
 * @method static ItemBuilder withLabel(string $language, string $label)
 * @method ItemBuilder withLabel(string $language, string $label)
 * @see ItemBuilder::_withLabel
 */
class ItemBuilder {
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
	 * @var string[][] Indexed by language on the first level
	 */
	private $aliases = [];

	/**
	 * @var string[] Indexed by siteId
	 */
	private $siteLinks = [];

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	public static function create() {
		return new self();
	}

	public function __construct() {
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
		foreach ( $this->siteLinks as $siteId => $pageName ) {
			$item->getSiteLinkList()->addNewSiteLink( $siteId, $pageName );
		}
		foreach ( $this->statements as $statement ) {
			$item->getStatements()->addStatement( $statement );
		}
		return $item;
	}

	/**
	 * @param ItemId|string $itemId
	 * @return self
	 */
	public function withId( $itemId ) {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->itemId = $itemId;
		return $result;
	}

	/**
	 * @param string $language
	 * @param string $label
	 * @return self
	 */
	protected function _withLabel( $language, $label ) {
		$result = clone $this;
		$result->labels[$language] = $label;
		return $result;
	}

	/**
	 * @param string $language
	 * @param string $description
	 * @return self
	 */
	public function withDescription( $language, $description ) {
		$result = clone $this;
		$result->descriptions[$language] = $description;
		return $result;
	}

	/**
	 * @param string $language
	 * @param array $aliases
	 * @return self
	 */
	public function withAliases( $language, array $aliases ) {
		$result = clone $this;
		$result->aliases[$language] = $aliases;
		return $result;
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @return self
	 */
	public function withSiteLink( $siteId, $pageName ) {
		$result = clone $this;
		$result->siteLinks[$siteId] = $pageName;
		return $result;
	}

	/**
	 * @param Statement|Snak $statement
	 * @return self
	 */
	public function withStatement( $statement ) {
		$result = clone $this;
		if ( $statement instanceof Snak ) {
			$statement = new Statement( $statement );
		}
		$result->statements[] = clone $statement;
		return $result;
	}

	/**
	 * @param PropertyId|string $propertyId
	 * @param EntityId|DataValue $value If given not DataValue tries to guess type and create
	 *        correct DataValue object
	 * @return ItemBuilder
	 */
	public function withPropertyValueStatement( $propertyId, $value ) {
		if ( !$propertyId instanceof PropertyId ) {
			$propertyId = new PropertyId( $propertyId );
		}

		if ( $value instanceof EntityId ) {
			$value = new EntityIdValue( $value );
		} else {
			//Assume that $value is of type DataValue
		}

		return $this->withStatement(
			new PropertyValueSnak(
				$propertyId,
				$value
			)
		);

	}

	public function __call( $name, $arguments ) {
		return call_user_func_array( [ $this, '_' . $name ], $arguments );
	}

	public static function __callStatic( $name, $arguments ) {
		$itemBuilder = self::create();
		return call_user_func_array( [ $itemBuilder, '_' . $name ], $arguments );
	}

}
