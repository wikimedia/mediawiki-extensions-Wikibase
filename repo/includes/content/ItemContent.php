<?php

namespace Wikibase;

use Content;
use InvalidArgumentException;
use Language;
use LogicException;
use MWException;
use ParserOutput;
use Title;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\ItemSearchTextGenerator;
use Wikibase\Repo\ParserOutput\SiteLinksParserOutputGenerator;
use Wikibase\Repo\ParserOutput\StatementsParserOutputGenerator;
use Wikibase\Repo\View\ClaimsViewFactory;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SiteLinksView;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var EntityRedirect
	 */
	private $redirect;

	/**
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
	 * @param Item|null $item
	 * @param EntityRedirect|null $entityRedirect
	 * @param Title|null $redirectTitle
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		Item $item = null,
		EntityRedirect $entityRedirect = null,
		Title $redirectTitle = null
	) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		if ( is_null( $item ) === is_null( $entityRedirect ) ) {
			throw new InvalidArgumentException(
				'Either $item or $entityRedirect and $redirectTitle must be provided.' );
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

		$this->item = $item;
		$this->redirect = $entityRedirect;
		$this->redirectTitle = $redirectTitle;
	}

	/**
	 * Create a new ItemContent object for the provided Item.
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
	public static function newFromRedirect( EntityRedirect $redirect, Title $redirectTitle ) {
		return new static( null, $redirect, $redirectTitle );
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
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @throws LogicException
	 * @return Item
	 */
	public function getItem() {
		$redirect = $this->getRedirectTarget();

		if ( $redirect ) {
			throw new MWException( 'Unresolved redirect to [[' . $redirect->getFullText() . ']]' );
		}

		if ( !$this->item ) {
			throw new LogicException( 'Neither redirect nor item found in ItemContent!' );
		}

		return $this->item;
	}

	/**
	 * Returns a new empty ItemContent.
	 *
	 * @return ItemContent
	 */
	public static function newEmpty() {
		return new static( Item::newEmpty() );
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
	 * @see EntityContent::getTextForSearchIndex()
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		wfProfileIn( __METHOD__ );

		// TODO: refactor ItemSearchTextGenerator to share an interface with EntitySearchTextGenerator,
		// so we don't have to re-implement getTextForSearchIndex() here.
		$searchTextGenerator = new ItemSearchTextGenerator();
		$text = $searchTextGenerator->generate( $this->getItem() );

		if ( !wfRunHooks( 'WikibaseTextForSearchIndex', array( $this, &$text ) ) ) {
			return '';
		}

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * @see getEntityView
	 *
	 * @param Language $language
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @return ItemView
	 */
	protected function getEntityView( Language $language, LanguageFallbackChain $languageFallbackChain ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$settings = $wikibaseRepo->getSettings();

		$sectionEditLinkGenerator = new SectionEditLinkGenerator();

		$fingerprintView = new FingerprintView(
			$sectionEditLinkGenerator,
			$language->getCode()
		);

		$claimsViewFactory = new ClaimsViewFactory(
			$wikibaseRepo->getSnakFormatterFactory(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getStore()->getEntityInfoBuilderFactory()
		);

		$claimsView = $claimsViewFactory->createClaimsView( $language->getCode(), $languageFallbackChain );

		$siteLinksView = new SiteLinksView(
			$wikibaseRepo->getSiteStore()->getSites(),
			$sectionEditLinkGenerator,
			$wikibaseRepo->getEntityLookup(),
			$settings->getSetting( 'specialSiteLinkGroups' ),
			$settings->getSetting( 'badgeItems' ),
			$language->getCode()
		);

		return new ItemView(
			$fingerprintView,
			$claimsView,
			$siteLinksView,
			$settings->getSetting( 'siteLinkGroups' ),
			$language
		);
	}

	/**
	 * @see EntityContent::addDataToParserOutput
	 *
	 * @param ParserOutput $pout
	 */
	protected function addDataToParserOutput( ParserOutput $pout ) {
		parent::addDataToParserOutput( $pout );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$statementsParserOutputGenerator = new StatementsParserOutputGenerator(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getPropertyDataTypeLookup()
		);

		$statementsParserOutputGenerator->assignToParserOutput( $pout, $this->getItem()->getStatements() );

		$siteLinksParserOutputGenerator = new SiteLinksParserOutputGenerator(
			$wikibaseRepo->getEntityTitleLookup()
		);

		$siteLinksParserOutputGenerator->assignSiteLinksToParserOutput( $pout, $this->getItem()->getSiteLinkList() );
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
		$hasSiteLinks = !$this->getItem()->getSiteLinkList()->isEmpty();

		if ( $status === self::STATUS_EMPTY && $hasSiteLinks ) {
			$status = self::STATUS_LINKSTUB;
		} else if ( $status === self::STATUS_STUB && $hasSiteLinks ) {
			$status = self::STATUS_LINKSTUB;
		}

		return $status;
	}

}
