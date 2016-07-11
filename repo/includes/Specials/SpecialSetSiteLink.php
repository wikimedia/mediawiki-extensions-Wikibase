<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use OutOfBoundsException;
use Status;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Special page for setting the sitepage of a Wikibase entity.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetSiteLink extends SpecialModifyEntity {

	/**
	 * The site of the site link.
	 *
	 * @var string|null
	 */
	private $site;

	/**
	 * The page of the site link.
	 *
	 * @var string
	 */
	private $page;

	/**
	 * The badges of the site link.
	 *
	 * @var string[]
	 */
	private $badges;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetSiteLink' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$settings = $wikibaseRepo->getSettings();

		$this->badgeItems = $settings->getSetting( 'badgeItems' );
		$this->siteLinkGroups = $settings->getSetting( 'siteLinkGroups' );

		$this->siteLinkChangeOpFactory = $wikibaseRepo->getChangeOpFactoryProvider()->getSiteLinkChangeOpFactory();
		$this->siteLinkTargetProvider = new SiteLinkTargetProvider(
			$this->siteStore,
			$settings->getSetting( 'specialSiteLinkGroups' )
		);

		$this->labelDescriptionLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyEntity::prepareArguments()
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 */
	protected function prepareArguments( $subPage ) {
		parent::prepareArguments( $subPage );

		$request = $this->getRequest();
		// explode the sub page from the format Special:SetSitelink/q123/enwiki
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );

		// check if id belongs to an item
		if ( $this->entityRevision !== null
			&& !( $this->entityRevision->getEntity() instanceof Item )
		) {
			$itemId = $this->entityRevision->getEntity()->getId();
			$msg = $this->msg( 'wikibase-setsitelink-not-item', $itemId->getSerialization() );
			$this->showErrorHTML( $msg->parse() );
			$this->entityRevision = null;
		}

		$this->site = trim( $request->getVal( 'site', isset( $parts[1] ) ? $parts[1] : '' ) );

		if ( $this->site === '' ) {
			$this->site = null;
		}

		if ( $this->site !== null && !$this->isValidSiteId( $this->site ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-setsitelink-invalid-site', $this->site )->parse() );
		}

		$this->page = $request->getVal( 'page' );

		$this->badges = array();
		foreach ( $this->badgeItems as $badgeId => $value ) {
			if ( $request->getVal( 'badge-' . $badgeId ) ) {
				$this->badges[] = $badgeId;
			}
		}
	}

	/**
	 * @see SpecialModifyEntity::validateInput()
	 *
	 * @return bool
	 */
	protected function validateInput() {
		$request = $this->getRequest();

		if ( !$this->isValidSiteId( $this->site ) ) {
			return false;
		}

		if ( !parent::validateInput() ) {
			return false;
		}

		// If the user just enters an item id and a site, dont remove the site link.
		// The user can remove the site link in the second form where it has to be
		// actually removed. This prevents users from removing site links accidentally.
		if ( !$request->getCheck( 'remove' ) && $this->page === '' ) {
			$this->page = null;
			return false;
		}

		return true;
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Summary|bool The summary or false
	 */
	protected function modifyEntity( EntityDocument $entity ) {
		try {
			$status = $this->setSiteLink( $entity, $this->site, $this->page, $this->badges, $summary );
		} catch ( ChangeOpException $e ) {
			$this->showErrorHTML( $e->getMessage() );
			return false;
		}

		if ( !$status->isGood() ) {
			$this->showErrorHTML( $status->getMessage()->parse() );
			return false;
		}

		return $summary;
	}

	/**
	 * Checks if the site id is valid.
	 *
	 * @param string $siteId the site id
	 *
	 * @return bool
	 */
	private function isValidSiteId( $siteId ) {
		return $siteId !== null
			&& $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups )->hasSite( $siteId );
	}

	/**
	 * @see SpecialModifyEntity::getFormElements()
	 *
	 * @param EntityDocument|null $entity
	 *
	 * @return string HTML
	 */
	protected function getFormElements( EntityDocument $entity = null ) {
		$this->getOutput()->addModules( 'mediawiki.ui.input' );

		if ( $this->page === null ) {
			$this->page = $this->site === null ? '' : $this->getSiteLink( $entity, $this->site );
		}
		if ( empty( $this->badges ) ) {
			$this->badges = $this->site === null ? array() : $this->getBadges( $entity, $this->site );
		}
		$pageinput = Html::element( 'br' )
			. Html::label(
				$this->msg( 'wikibase-setsitelink-label' )->text(),
				'wb-setsitelink-page',
				array(
					'class' => 'wb-label'
				)
			) .
			Html::input(
				'page',
				$this->getRequest()->getVal( 'page' ) ?: $this->page,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-setsitelink-page',
				)
			);

		if ( !empty( $this->badgeItems ) ) {
			$pageinput .= Html::element( 'br' )
			. Html::label(
				$this->msg( 'wikibase-setsitelink-badges' )->text(),
				'wb-setsitelink-badges',
				array(
					'class' => 'wb-label'
				)
			)
			. $this->getHtmlForBadges();
		}

		$site = $this->siteStore->getSite( $this->site );

		if ( $entity !== null && $this->site !== null && $site !== null ) {
			// show the detailed form which also allows users to remove site links
			return Html::rawElement(
				'p',
				array(),
				$this->msg(
					'wikibase-setsitelink-introfull',
					$this->getEntityTitle( $entity->getId() )->getPrefixedText(),
					'[' . $site->getPageUrl( '' ) . ' ' . $this->site . ']'
				)->parse()
			)
			. Html::input( 'site', $this->site, 'hidden' )
			. Html::input( 'id', $this->entityRevision->getEntity()->getId()->getSerialization(), 'hidden' )
			. Html::input( 'remove', 'remove', 'hidden' )
			. $pageinput;
		} else {
			$intro = $this->msg( 'wikibase-setsitelink-intro' )->text();

			if ( !empty( $this->badgeItems ) ) {
				$intro .= $this->msg( 'word-separator' )->text() . $this->msg( 'wikibase-setsitelink-intro-badges' )->text();
			}

			return Html::element(
				'p',
				array(),
				$intro
			)
			. parent::getFormElements( $entity )
			. Html::element( 'br' )
			. Html::label(
				$this->msg( 'wikibase-setsitelink-site' )->text(),
				'wb-setsitelink-site',
				array(
					'class' => 'wb-label'
				)
			)
			. Html::input(
				'site',
				$this->getRequest()->getVal( 'site' ) ?: $this->site,
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-setsitelink-site'
				)
			)
			. $pageinput;
		}
	}

	/**
	 * Returns the HTML containing a checkbox for each badge.
	 *
	 * @return string
	 */
	private function getHtmlForBadges() {
		$options = '';

		/** @var ItemId[] $badgeItemIds */
		$badgeItemIds = array_map(
			function( $badgeId ) {
				return new ItemId( $badgeId );
			},
			array_keys( $this->badgeItems )
		);

		$labelLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->getLanguage(),
			$badgeItemIds
		);

		foreach ( $badgeItemIds as $badgeId ) {
			$idSerialization = $badgeId->getSerialization();
			$name = 'badge-' . $idSerialization;

			$label = $labelLookup->getLabel( $badgeId );
			$label = $label === null ? $idSerialization : $label->getText();

			$options .= Html::rawElement(
				'div',
				array(
					'class' => 'wb-label'
				),
				Html::check(
					$name,
					in_array( $idSerialization, $this->badges ),
					array(
						'id' => $name
					)
				)
				. Html::label( $label, $name )
			);
		}

		return $options;
	}

	/**
	 * Returning the site page of the entity.
	 *
	 * @param Item|null $item
	 * @param string $siteId
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	private function getSiteLink( Item $item = null, $siteId ) {
		if ( $item === null || !$item->hasLinkToSite( $siteId ) ) {
			return '';
		}

		return $item->getSitelink( $siteId )->getPageName();
	}

	/**
	 * Returning the badges of the entity.
	 *
	 * @param Item|null $item
	 * @param string $siteId
	 *
	 * @throws OutOfBoundsException
	 * @return string[]
	 */
	private function getBadges( Item $item = null, $siteId ) {
		if ( $item === null || !$item->getSiteLinkList()->hasLinkWithSiteId( $siteId ) ) {
			return array();
		}

		return array_map(
			function( ItemId $badge ) {
				return $badge->getSerialization();
			},
			$item->getSiteLinkList()->getBySiteId( $siteId )->getBadges()
		);
	}

	/**
	 * Validates badges from params and turns them into an array of ItemIds.
	 *
	 * @param string[] $badges
	 * @param Status $status
	 *
	 * @return ItemId[]|boolean
	 */
	private function parseBadges( array $badges, Status $status ) {
		$badgesObjects = array();

		foreach ( $badges as $badge ) {
			try {
				$badgeId = new ItemId( $badge );
			} catch ( InvalidArgumentException $ex ) {
				$status->fatal( 'wikibase-wikibaserepopage-not-itemid', $badge );
				return false;
			}

			if ( !array_key_exists( $badgeId->getSerialization(), $this->badgeItems ) ) {
				$status->fatal( 'wikibase-setsitelink-not-badge', $badgeId->getSerialization() );
				return false;
			}

			$itemTitle = $this->getEntityTitle( $badgeId );

			if ( is_null( $itemTitle ) || !$itemTitle->exists() ) {
				$status->fatal( 'wikibase-wikibaserepopage-invalid-id', $badgeId );
				return false;
			}

			$badgesObjects[] = $badgeId;
		}

		return $badgesObjects;
	}

	/**
	 * Setting the sitepage of the entity.
	 *
	 * @param EntityDocument $item
	 * @param string $siteId
	 * @param string $pageName
	 * @param string[] $badgeIds
	 * @param Summary|null &$summary The summary for this edit will be saved here.
	 *
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	private function setSiteLink( EntityDocument $item, $siteId, $pageName, array $badgeIds, Summary &$summary = null ) {
		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$entity must be an Item' );
		}

		$status = Status::newGood();
		$site = $this->siteStore->getSite( $siteId );

		if ( $site === null ) {
			$status->fatal( 'wikibase-setsitelink-invalid-site', $siteId );
			return $status;
		}

		$summary = new Summary( 'wbsetsitelink' );

		// when $pageName is an empty string, we want to remove the site link
		if ( $pageName === '' ) {
			if ( !$item->hasLinkToSite( $siteId ) ) {
				$status->fatal( 'wikibase-setsitelink-remove-failed' );
				return $status;
			}
		} else {
			$pageName = $site->normalizePageName( $pageName );

			if ( $pageName === false ) {
				$status->fatal( 'wikibase-error-ui-no-external-page' );
				return $status;
			}
		}

		$badges = $this->parseBadges( $badgeIds, $status );

		if ( !$status->isGood() ) {
			return $status;
		}

		$changeOp = $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $siteId, $pageName, $badges );
		$this->applyChangeOp( $changeOp, $item, $summary );

		return $status;
	}

}
