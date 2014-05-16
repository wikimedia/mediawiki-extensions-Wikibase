<?php

namespace Wikibase;

use Content;
use DataUpdate;
use IContextSource;
use InvalidArgumentException;
use LogicException;
use MWException;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\ItemSearchTextGenerator;
use WikiPage;

/**
 * Content object for articles representing Wikibase items.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	 * @since 0.1
	 * @var Item
	 */
	protected $item;

	/**
	 * @since 0.5
	 * @var Title
	 */
	protected $redirectTarget;

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
	 * @param Title $redirectTarget
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Item $item = null, Title $redirectTarget = null ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		if ( $item === null && $redirectTarget === null ) {
			throw new InvalidArgumentException(
				'Either $item or $redirectTargetId must be provided' );
		}

		if ( $item !== null && $redirectTarget !== null ) {
			throw new InvalidArgumentException(
				'Only one of $item or $redirectTargetId can be provided' );
		}

		if ( $redirectTarget !== null
			&& $redirectTarget->getContentModel() !== CONTENT_MODEL_WIKIBASE_ITEM
		) {
			if ( $redirectTarget->exists() ) {
				throw new InvalidArgumentException(
					'$redirectTarget must ref to a page with content model '
					. CONTENT_MODEL_WIKIBASE_ITEM );
			}
		}

		$this->item = $item;
		$this->redirectTarget = $redirectTarget;
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
	 * @param Title $redirectTarget
	 *
	 * @return ItemContent
	 */
	public static function newRedirect( Title $redirectTarget ) {
		return new static( null, $redirectTarget );
	}

	/**
	 * Create a new ItemContent object from the provided Item data.
	 *
	 * @since 0.1
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
		return $this->redirectTarget;
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
		if ( $this->isRedirect() ) {
			// NOTE: When an item is turned into a redirect, that means the item is effectively deleted.
			return $this->getDeletionUpdates( new WikiPage( $title ), $parserOutput );
		}

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
				'wb-sitelinks' => count( $item->getSiteLinks() ),
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
