<?php

namespace Wikibase\Repo\Tests;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Immutable Wikibase entity builder.
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
	 * @var string[] Indexed by global site ID
	 */
	private $siteLinks = [];

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	private function __construct() {
	}

	/**
	 * @deprecated
	 */
	public static function create() {
		return new self();
	}

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
		$result = clone $this;
		if ( !( $itemId instanceof ItemId ) ) {
			$itemId = new ItemId( $itemId );
		}
		$result->itemId = $itemId;
		return $result;
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
		$result = clone $this;
		$result->labels[$languageCode] = $label;
		return $result;
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
		$result = clone $this;
		$result->descriptions[$languageCode] = $description;
		return $result;
	}

	/**
	 * @see andAliases
	 */
	public static function withAliases( $languageCode, array $aliases ) {
		return ( new self() )->andAliases( $languageCode, $aliases );
	}

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @return self
	 */
	public function andAliases( $languageCode, array $aliases ) {
		$result = clone $this;
		$result->aliases[$languageCode] = $aliases;
		return $result;
	}

	/**
	 * @see andSiteLink
	 */
	public static function withSiteLink( $siteId, $pageName ) {
		return ( new self() )->andSiteLink( $siteId, $pageName );
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return self
	 */
	public function andSiteLink( $siteId, $pageName ) {
		$result = clone $this;
		$result->siteLinks[$siteId] = $pageName;
		return $result;
	}

	/**
	 * @see andStatement
	 */
	public static function withStatement( $statement ) {
		return ( new self() )->andStatement( $statement );
	}

	/**
	 * @param Statement|Snak $statement
	 *
	 * @return self
	 */
	public function andStatement( $statement ) {
		$result = clone $this;
		if ( $statement instanceof Snak ) {
			$statement = new Statement( $statement );
		}
		$result->statements[] = clone $statement;
		return $result;
	}

	/**
	 * @see andPropertyNoValueSnak
	 */
	public static function withPropertyNoValueSnak( $propertyId ) {
		return ( new self() )->andPropertyNoValueSnak( $propertyId );
	}

	/**
	 * @param PropertyId|string $propertyId
	 *
	 * @return self
	 */
	public function andPropertyNoValueSnak( $propertyId ) {
		if ( !( $propertyId instanceof PropertyId ) ) {
			$propertyId = new PropertyId( $propertyId );
		}

		return $this->andStatement( new PropertyNoValueSnak( $propertyId ) );
	}

	/**
	 * @see andPropertySomeValueSnak
	 */
	public static function withPropertySomeValueSnak( $propertyId ) {
		return ( new self() )->andPropertySomeValueSnak( $propertyId );
	}


	/**
	 * @param PropertyId|string $propertyId
	 *
	 * @return self
	 */
	public function andPropertySomeValueSnak( $propertyId ) {
		if ( !( $propertyId instanceof PropertyId ) ) {
			$propertyId = new PropertyId( $propertyId );
		}

		return $this->andStatement( new PropertySomeValueSnak( $propertyId ) );
	}

	/**
	 * @see andPropertyValueSnak
	 */
	public static function withPropertyValueSnak( $propertyId, $dataValue ) {
		return ( new self() )->andPropertyValueSnak( $propertyId, $dataValue );
	}

	/**
	 * @param PropertyId|string $propertyId
	 * @param DataValue|EntityId|string $dataValue If not a DataValue object, the builder tries to
	 *  guess the type and turns it into a DataValue object.
	 *
	 * @return self
	 */
	public function andPropertyValueSnak( $propertyId, $dataValue ) {
		if ( !( $propertyId instanceof PropertyId ) ) {
			$propertyId = new PropertyId( $propertyId );
		}

		if ( $dataValue instanceof EntityId ) {
			$dataValue = new EntityIdValue( $dataValue );
		} elseif ( is_string( $dataValue ) ) {
			$dataValue = new StringValue( $dataValue );
		} elseif ( !( $dataValue instanceof DataValue ) ) {
			throw new InvalidArgumentException( 'Unsupported $dataValue type' );
		}

		return $this->andStatement( new PropertyValueSnak( $propertyId, $dataValue ) );
	}

}
