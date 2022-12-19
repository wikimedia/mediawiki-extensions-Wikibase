<?php

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use Psr\Log\LoggerInterface;
use Site;
use SiteLookup;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Store\Store;

/**
 * Enables accessing items by providing the identifier of a site and the title
 * of the corresponding page on that site.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialItemByTitle extends SpecialWikibasePage {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var SiteLookup
	 */
	private $sites;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * site link groups
	 *
	 * @var string[]
	 */
	private $groups;

	/**
	 * @see SpecialWikibasePage::__construct
	 *
	 * @param EntityTitleLookup $titleLookup
	 * @param LanguageNameLookup $languageNameLookup
	 * @param SiteLookup $siteLookup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param LoggerInterface $logger
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		EntityTitleLookup $titleLookup,
		LanguageNameLookup $languageNameLookup,
		SiteLookup $siteLookup,
		SiteLinkLookup $siteLinkLookup,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		LoggerInterface $logger,
		array $siteLinkGroups
	) {
		parent::__construct( 'ItemByTitle', '', true );

		$this->titleLookup = $titleLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->sites = $siteLookup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->logger = $logger;
		$this->groups = $siteLinkGroups;
	}

	public static function factory(
		SiteLookup $siteLookup,
		EntityTitleLookup $entityTitleLookup,
		LanguageNameLookupFactory $languageNameLookupFactory,
		LoggerInterface $logger,
		SettingsArray $repoSettings,
		Store $store
	): self {
		$siteLinkTargetProvider = new SiteLinkTargetProvider(
			$siteLookup,
			$repoSettings->getSetting( 'specialSiteLinkGroups' )
		);

		return new self(
			$entityTitleLookup,
			$languageNameLookupFactory->getForAutonyms(),
			$siteLookup,
			// TODO move SiteLinkStore to service container and inject it directly
			$store->newSiteLinkStore(),
			$siteLinkTargetProvider,
			$logger,
			$repoSettings->getSetting( 'siteLinkGroups' )
		);
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		// Setup
		$request = $this->getRequest();
		$parts = $subPage ? explode( '/', $subPage, 2 ) : [];
		$site = trim( $request->getVal( 'site', $parts[0] ?? '' ) );
		$page = trim( $request->getVal( 'page', $parts[1] ?? '' ) );

		$itemContent = null;

		// If there are enough data, then try to lookup the item content
		if ( $site !== '' && $page !== '' ) {
			// FIXME: This code is duplicated in ItemByTitleHelper::getItemId!
			// Try to get a item content
			$siteId = $this->stringNormalizer->trimToNFC( $site ); // no stripping of underscores here!
			$pageName = $this->stringNormalizer->trimToNFC( $page );

			if ( !$this->sites->getSite( $siteId ) ) {
				// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
				// subdomains to be used to refer to wikipedias, instead of requiring their
				// full global id to be used.
				// @todo: Ideally, if the site can't be looked up by global ID, we
				// should try to look it up by local navigation ID.
				// Support for this depends on bug T50934.
				$siteId .= 'wiki';
			}

			$itemId = $this->siteLinkLookup->getItemIdForLink( $siteId, $pageName );

			// Do we have an item content, and if not can we try harder?
			if ( $itemId === null ) {
				// Try harder by requesting normalization on the external site
				$siteObj = $this->sites->getSite( $siteId );
				if ( $siteObj instanceof Site ) {
					$pageName = $siteObj->normalizePageName( $page );
					if ( is_string( $pageName ) ) { // T191634
						$itemId = $this->siteLinkLookup->getItemIdForLink( $siteId, $pageName );
					}
				}
			}

			// Redirect to the item page if we found its content
			if ( $itemId !== null ) {
				$title = $this->titleLookup->getTitleForId( $itemId );
				$query = $request->getValues();
				unset( $query['title'] );
				unset( $query['site'] );
				unset( $query['page'] );
				$itemUrl = $title->getFullURL( $query );
				$this->getOutput()->redirect( $itemUrl );
				return;
			}
		}

		// If there is no item content post the switch form
		$this->switchForm( $site, $page );
	}

	/**
	 * Return options for the site input field.
	 *
	 * @return array
	 */
	private function getSiteOptions() {
		$options = [];
		foreach ( $this->siteLinkTargetProvider->getSiteList( $this->groups ) as $site ) {
			$siteId = $site->getGlobalId();
			$languageName = $this->languageNameLookup->getName( $site->getLanguageCode() );
			$options["$languageName ($siteId)"] = $siteId;
		}
		return $options;
	}

	/**
	 * Output a form to allow searching for a page
	 *
	 * @param string $siteId
	 * @param string $page
	 */
	private function switchForm( $siteId, $page ) {
		$siteExists = $siteId
			&& $this->siteLinkTargetProvider->getSiteList( $this->groups )->hasSite( $siteId );

		$this->logger->debug(
			'{method}: Site {siteId} exists: {siteExists}',
			[
				'method' => __METHOD__,
				'siteId' => $siteId,
				'siteExists' => var_export( $siteExists, true ),
			]
		);

		$formDescriptor = [
			'site' => [
				'name' => 'site',
				'default' => $siteId,
				'type' => 'combobox',
				'options' => $this->getSiteOptions(),
				'id' => 'wb-itembytitle-sitename',
				'size' => 12,
				'label-message' => 'wikibase-itembytitle-lookup-site',
			],
			'page' => [
				'name' => 'page',
				'default' => $page ?: '',
				'type' => 'text',
				'id' => 'pagename',
				'size' => 36,
				'label-message' => 'wikibase-itembytitle-lookup-page',
			],
		];

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-itembytitle-form1' )
			->setMethod( 'get' )
			->setSubmitID( 'wb-itembytitle-submit' )
			->setSubmitTextMsg( 'wikibase-itembytitle-submit' )
			->setWrapperLegendMsg( 'wikibase-itembytitle-lookup-fieldset' )
			->setSubmitCallback( function () {// no-op
			} )->show();

		if ( $siteId && !$siteExists ) {
			$this->showErrorHTML( $this->msg( 'wikibase-itembytitle-error-site' )->parse() );
		} elseif ( $siteExists && $page ) {
			$this->showErrorHTML( $this->msg( 'wikibase-itembytitle-error-item' )->parse() );

			$createLink = $this->getTitleFor( 'NewItem' );
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg(
					'wikibase-itembytitle-create',
					$createLink->getFullURL( [ 'site' => $siteId, 'page' => $page ] )
				)->parse()
				. Html::closeElement( 'div' )
			);
		}
	}

}
