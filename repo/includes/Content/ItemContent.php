<?php

namespace Wikibase\Repo\Content;

use Hooks;
use InvalidArgumentException;
use LogicException;
use MWException;
use Title;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ItemSearchTextGenerator;

/**
 * Content object for articles representing Wikibase items.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 *
 * @method \Wikibase\Repo\Content\ItemHandler getContentHandler()
 */
class ItemContent extends EntityContent {

	public const CONTENT_MODEL_ID = 'wikibase-item';

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
	 */
	public function __construct(
		EntityHolder $itemHolder = null,
		EntityRedirect $entityRedirect = null,
		Title $redirectTitle = null
	) {
		parent::__construct( self::CONTENT_MODEL_ID );

		if ( $itemHolder !== null && $entityRedirect !== null ) {
			throw new InvalidArgumentException(
				'Can not contain an Item and be a redirect at the same time'
			);
		}

		if ( $itemHolder !== null && $itemHolder->getEntityType() !== Item::ENTITY_TYPE ) {
			throw new InvalidArgumentException( '$itemHolder must contain a Item entity!' );
		}

		if ( ( $entityRedirect === null ) !== ( $redirectTitle === null ) ) {
			throw new InvalidArgumentException(
				'$entityRedirect and $redirectTitle must both be provided or both be empty.' );
		}

		if ( $redirectTitle !== null
			&& $redirectTitle->getContentModel() !== self::CONTENT_MODEL_ID
		) {
			if ( $redirectTitle->exists() ) {
				throw new InvalidArgumentException(
					'$redirectTitle must refer to a page with content model '
					. self::CONTENT_MODEL_ID );
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

	protected function getIgnoreKeysForFilters() {
		// FIXME: Refine this after https://phabricator.wikimedia.org/T205254 is complete
		return [
			'language',
			'site',
			'type',
			'hash',
			'id',
		];
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
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @throws LogicException if the content object is empty and does not contain an entity.
	 * @return Item
	 */
	public function getItem() {
		$redirect = $this->getRedirectTarget();

		if ( $redirect ) {
			throw new MWException( 'Unresolved redirect to [[' . $redirect->getFullText() . ']]' );
		}

		if ( !$this->itemHolder ) {
			throw new LogicException( 'This content object is empty' );
		}

		return $this->itemHolder->getEntity( Item::class );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @throws LogicException if the content object is empty and does not contain an entity.
	 * @return Item
	 */
	public function getEntity() {
		return $this->getItem();
	}

	/**
	 * @see EntityContent::getEntityHolder
	 *
	 * @return EntityHolder|null
	 */
	public function getEntityHolder() {
		return $this->itemHolder;
	}

	/**
	 * @inheritDoc
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		// TODO: Refactor ItemSearchTextGenerator to share an interface with
		// FingerprintSearchTextGenerator, so we don't have to re-implement getTextForSearchIndex() here.
		$searchTextGenerator = new ItemSearchTextGenerator();
		$text = $searchTextGenerator->generate( $this->getItem() );

		if ( !Hooks::run( 'WikibaseTextForSearchIndex', [ $this, &$text ] ) ) {
			return '';
		}

		return $text;
	}

	/**
	 * @see EntityContent::isEmpty
	 *
	 * @return bool True if this is not a redirect and the item is empty.
	 */
	public function isEmpty() {
		return !$this->isRedirect() && ( !$this->itemHolder || $this->getItem()->isEmpty() );
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
			$properties['wb-identifiers'] = $this->getContentHandler()
				->getIdentifiersCount( $item->getStatements() );
		}

		return $properties;
	}
}
