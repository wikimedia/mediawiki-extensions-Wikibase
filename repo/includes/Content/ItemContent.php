<?php

namespace Wikibase;

use Hooks;
use InvalidArgumentException;
use LogicException;
use MWException;
use Title;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ItemSearchTextGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * Content object for articles representing Wikibase items.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemContent extends EntityContent {

	/**
	 * @var EntityHolder|null
	 */
	private $itemHolder;

	/**
	 * @var EntityRedirect|null
	 */
	private $redirect;

	/**
	 * @var Title|null Title of the redirect target.
	 */
	private $redirectTitle;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now cannot
	 * be since we derive from Content).
	 *
	 * @param EntityHolder|null $itemHolder
	 * @param EntityRedirect|null $entityRedirect
	 * @param Title|null $redirectTitle Title of the redirect target.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityHolder $itemHolder = null,
		EntityRedirect $entityRedirect = null,
		Title $redirectTitle = null
	) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		if ( is_null( $itemHolder ) === is_null( $entityRedirect ) ) {
			throw new InvalidArgumentException(
				'Either $item or $entityRedirect and $redirectTitle must be provided.' );
		}

		if ( $itemHolder !== null && $itemHolder->getEntityType() !== Item::ENTITY_TYPE ) {
			throw new InvalidArgumentException( '$itemHolder must contain a Item entity!' );
		}

		if ( is_null( $entityRedirect ) !== is_null( $redirectTitle ) ) {
			throw new InvalidArgumentException(
				'$entityRedirect and $redirectTitle must both be provided or both be empty.' );
		}

		if ( $redirectTitle !== null
			&& $redirectTitle->getContentModel() !== CONTENT_MODEL_WIKIBASE_ITEM
		) {
			if ( $redirectTitle->exists() ) {
				throw new InvalidArgumentException(
					'$redirectTitle must refer to a page with content model '
					. CONTENT_MODEL_WIKIBASE_ITEM );
			}
		}

		$this->itemHolder = $itemHolder;
		$this->redirect = $entityRedirect;
		$this->redirectTitle = $redirectTitle;
	}

	/**
	 * Create a new ItemContent object for the provided Item.
	 *
	 * @param Item $item
	 *
	 * @return self
	 */
	public static function newFromItem( Item $item ) {
		return new static( new EntityInstanceHolder( $item ) );
	}

	/**
	 * Create a new ItemContent object representing a redirect to the given item ID.
	 *
	 * @param EntityRedirect $redirect
	 * @param Title $redirectTitle Title of the redirect target.
	 *
	 * @return self
	 */
	public static function newFromRedirect( EntityRedirect $redirect, Title $redirectTitle ) {
		return new static( null, $redirect, $redirectTitle );
	}

	/**
	 * @see Content::getRedirectTarget
	 *
	 * @return Title|null
	 */
	public function getRedirectTarget() {
		return $this->redirectTitle;
	}

	/**
	 * @see EntityContent::getEntityRedirect
	 *
	 * @return null|EntityRedirect
	 */
	public function getEntityRedirect() {
		return $this->redirect;
	}

	/**
	 * Returns the Item that makes up this ItemContent.
	 *
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @throws LogicException
	 * @return Item
	 */
	public function getItem() {
		$redirect = $this->getRedirectTarget();

		if ( $redirect ) {
			throw new MWException( 'Unresolved redirect to [[' . $redirect->getFullText() . ']]' );
		}

		if ( !$this->itemHolder ) {
			throw new LogicException( 'Neither redirect nor item found in ItemContent!' );
		}

		return $this->itemHolder->getEntity( Item::class );
	}

	/**
	 * @return self
	 */
	public static function newEmpty() {
		return new static( new EntityInstanceHolder( new Item() ) );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @return Item
	 */
	public function getEntity() {
		return $this->getItem();
	}

	/**
	 * @see EntityContent::getEntityHolder
	 *
	 * @return EntityHolder
	 */
	protected function getEntityHolder() {
		return $this->itemHolder;
	}

	/**
	 * @see EntityContent::getTextForSearchIndex()
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		// TODO: Refactor ItemSearchTextGenerator to share an interface with
		// FingerprintSearchTextGenerator, so we don't have to re-implement getTextForSearchIndex() here.
		$searchTextGenerator = new ItemSearchTextGenerator();
		$text = $searchTextGenerator->generate( $this->getItem() );

		if ( !Hooks::run( 'WikibaseTextForSearchIndex', array( $this, &$text ) ) ) {
			return '';
		}

		return $text;
	}

	/**
	 * @see EntityContent::isCountable
	 *
	 * @param bool|null $hasLinks
	 *
	 * @return bool True if this is not a redirect and the item is not empty.
	 */
	public function isCountable( $hasLinks = null ) {
		return !$this->isRedirect() && !$this->getItem()->isEmpty();
	}

	/**
	 * @see EntityContent::isEmpty
	 *
	 * @return bool True if this is not a redirect and the item is empty.
	 */
	public function isEmpty() {
		return !$this->isRedirect() && $this->getItem()->isEmpty();
	}

	/**
	 * @param StatementList $statementList
	 * @return int
	 */
	private function getIdentifiersCount( StatementList $statementList ) {
		$identifiers = 0;
		$dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup();
		foreach ( $statementList->getPropertyIds() as $propertyIdSerialization => $propertyId ) {
			try {
				$dataType = $dataTypeLookup->getDataTypeIdForProperty( $propertyId );
			} catch ( PropertyDataTypeLookupException $e ) {
				continue;
			}

			if ( $dataType === 'external-id' ) {
				$identifiers += $statementList->getByPropertyId( $propertyId )->count();
			}
		}

		return $identifiers;
	}

	/**
	 * @see EntityContent::getEntityPageProperties
	 *
	 * Records the number of statements in the 'wb-claims' key
	 * and the number of sitelinks in the 'wb-sitelinks' key.
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		$properties = parent::getEntityPageProperties();

		if ( !$this->isRedirect() ) {
			$item = $this->getItem();
			$properties['wb-claims'] = $item->getStatements()->count();
			$properties['wb-sitelinks'] = $item->getSiteLinkList()->count();
			$properties['wb-identifiers'] = $this->getIdentifiersCount( $item->getStatements() );
		}

		return $properties;
	}

}
