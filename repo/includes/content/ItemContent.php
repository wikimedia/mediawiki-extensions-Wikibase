<?php

namespace Wikibase;

use IContextSource;
use InvalidArgumentException;
use LogicException;
use MWException;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\ItemSearchTextGenerator;

/**
 * Content object for articles representing Wikibase items.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ItemContent extends EntityContent {

	/**
	 * For use in the wb-status page property to indicate that the entity is a "linkstub",
	 * that is, it contains sitelinks, but no claims.
	 *
	 * @see getEntityStatus()
	 */
	const STATUS_LINKSTUB = 60;

	/**
	 * @var Item
	 */
	private $item;

	/**
	 * @since 0.5
	 * @var EntityRedirect
	 */
	private $redirect;

	/**
	 * @since 0.5
	 * @var Title
	 */
	private $redirectTitle;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now cannot
	 * be since we derive from Content).
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param EntityRedirect $redirect
	 * @param Title $redirectTitle
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( Item $item = null, EntityRedirect $redirect = null, Title $redirectTitle = null ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		if ( $item === null && $redirect === null ) {
			throw new InvalidArgumentException(
				'Either $item or $redirect must be provided' );
		}

		if ( $item !== null && $redirect !== null ) {
			throw new InvalidArgumentException(
				'Only one of $item or $redirect can be provided' );
		}

		if ( $item !== null && $redirectTitle !== null ) {
			throw new InvalidArgumentException(
				'Only one of $item or $redirectTitle can be provided' );
		}

		if ( $redirect !== null && $redirectTitle === null ) {
			throw new InvalidArgumentException(
				'If $redirect is given, $redirectTitle must be given too' );
		}

		if ( $redirectTitle !== null
			&& $redirectTitle->getContentModel() !== CONTENT_MODEL_WIKIBASE_ITEM
		) {
			if ( $redirectTitle->exists() ) {
				throw new InvalidArgumentException(
					'$redirectTitle must ref to a page with content model '
					. CONTENT_MODEL_WIKIBASE_ITEM );
			}
		}

		$this->item = $item;
		$this->redirect = $redirect;
		$this->redirectTitle = $redirectTitle;
	}

	/**
	 * Create a new ItemContent object for the provided Item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 *
	 * @return ItemContent
	 */
	public static function newFromItem( Item $item ) {
		return new static( $item );
	}

	/**
	 * Create a new ItemContent object representing a redirect to the given item ID.
	 *
	 * @since 0.5
	 *
	 * @param EntityRedirect $redirect
	 * @param Title $redirectTitle
	 *
	 * @return ItemContent
	 */
	public static function newRedirect( EntityRedirect $redirect, Title $redirectTitle ) {
		return new static( null, $redirect, $redirectTitle );
	}

	/**
	 * Create a new ItemContent object from the provided Item data.
	 *
	 * @deprecated Use a dedicated deserializer
	 *
	 * @param array $data
	 *
	 * @return ItemContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new Item( $data ) );
	}

	/**
	 * @see Content::getRedirectTarget
	 *
	 * @return null|Title
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
	 * @since 0.1
	 *
	 * @throws LogicException
	 * @throws MWException
	 * @return Item
	 */
	public function getItem() {
		$redirect = $this->getRedirectTarget();

		if ( $redirect ) {
			throw new MWException( 'Unresolved redirect to [[' . $redirect->getFullText() . ']]' );
		}

		if ( !$this->item ) {
			throw new LogicException( 'Nother redirect nor item found in ItemContent!' );
		}

		return $this->item;
	}

	/**
	 * Returns a new empty ItemContent.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public static function newEmpty() {
		return new static( Item::newEmpty() );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function getEntity() {
		return $this->getItem();
	}

	/**
	 * @see EntityContent::getTextForSearchIndex()
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		wfProfileIn( __METHOD__ );
		$item = $this->getItem();

		$searchTextGenerator = new ItemSearchTextGenerator();
		$text = $searchTextGenerator->generate( $item );

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * Instantiates an EntityView.
	 *
	 * @see getEntityView()
	 *
	 * @param IContextSource $context
	 * @param SnakFormatter $snakFormatter
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param SerializationOptions $options
	 *
	 * @return EntityView
	 */
	protected function newEntityView(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		SerializationOptions $options
	) {
		$configBuilder = new ParserOutputJsConfigBuilder(
			$entityInfoBuilder,
			$idParser,
			$entityTitleLookup,
			new ReferencedEntitiesFinder(),
			$context->getLanguage()->getCode()
		);

		return new ItemView(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityTitleLookup,
			$options,
			$configBuilder
		);
	}

	/**
	 * @see EntityContent::getEntityPageProperties
	 *
	 * Records the number of sitelinks in the 'wb-sitelinks' key.
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		if ( $this->isRedirect() ) {
			return array();
		}

		$item = $this->getItem();

		return array_merge(
			parent::getEntityPageProperties(),
			array(
				'wb-sitelinks' => $item->getSiteLinkList()->count(),
			)
		);
	}

	/**
	 * @see EntityContent::getEntityStatus()
	 *
	 * An item is considered a stub if it has terms but no statements or sitelinks.
	 * If an item has sitelinks but no statements, it is considered a "linkstub".
	 * If an item has statements, it's not empty nor a stub.
	 *
	 * @see STATUS_LINKSTUB
	 *
	 * @note Will fail of this ItemContent is a redirect.
	 *
	 * @return int
	 */
	public function getEntityStatus() {
		$status = parent::getEntityStatus();
		$hasSiteLinks = $this->getItem()->hasSiteLinks();

		if ( $status === self::STATUS_EMPTY && $hasSiteLinks ) {
			$status = self::STATUS_LINKSTUB;
		} else if ( $status === self::STATUS_STUB && $hasSiteLinks ) {
			$status = self::STATUS_LINKSTUB;
		}

		return $status;
	}

}
