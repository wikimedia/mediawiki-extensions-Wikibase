<?php

namespace Wikibase\Repo\Specials;

use Html;
use OutOfBoundsException;
use Status;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Special page for setting the sitepage of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetSiteLink extends SpecialModifyEntity {

	/**
	 * The site of the site link.
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $site;

	/**
	 * The page of the site link.
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $page;

	/**
	 * The badges of the site link.
	 *
	 * @since 0.5
	 *
	 * @var string[]
	 */
	protected $badges;

	/**
	 * @var string
	 */
	protected $rightsUrl;

	/**
	 * @var string
	 */
	protected $rightsText;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	protected $siteLinkChangeOpFactory;

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetSiteLink' );

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		$this->rightsUrl = $settings->getSetting( 'dataRightsUrl' );
		$this->rightsText = $settings->getSetting( 'dataRightsText' );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->siteLinkChangeOpFactory = $changeOpFactoryProvider->getSiteLinkChangeOpFactory();
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
		if ( $this->entityRevision !== null && !( $this->entityRevision->getEntity() instanceof Item ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-setsitelink-not-item', $this->entityRevision->getEntity()->getId()->getPrefixedId() )->parse() );
			$this->entityRevision = null;
		}

		// site
		$this->site = $request->getVal( 'site', isset( $parts[1] ) ? $parts[1] : '' );

		if ( $this->site === '' ) {
			$this->site = null;
		}

		if ( !$this->isValidSiteId( $this->site ) && $this->site !== null ) {
			$this->showErrorHTML( $this->msg( 'wikibase-setsitelink-invalid-site', $this->site )->parse() );
		}

		// title
		$this->page = $request->getVal( 'page' );

		// badges
		$badges = $request->getVal( 'badges', '' );

		if ( $badges === '' ) {
			$badges = null;
		}

		if ( $badges !== null ) {
			$this->badges = explode( '|', $badges );
		}
	}

	/**
	 * @see SpecialModifyEntity::validateInput()
	 *
	 * @return bool
	 */
	protected function validateInput() {
		$request = $this->getRequest();

		if ( !parent::validateInput() ) {
			return false;
		}

		// to provide removing after posting the full form
		if ( $request->getVal( 'remove' ) === null && $this->page === '' ) {
			$this->showErrorHTML(
				$this->msg(
					'wikibase-setsitelink-warning-remove',
					$this->getEntityTitle( $this->entityRevision->getEntity()->getId() )
				)->parse(),
				'warning'
			);
			return false;
		}

		return true;
	}

	/**
	 * @see SpecialModifyEntity::modifyEntity()
	 *
	 * @param Entity $entity
	 *
	 * @return Summary|bool The summary or false
	 */
	protected function modifyEntity( Entity $entity ) {
		try {
			$status = $this->setSiteLink( $entity, $this->site, $this->page, $this->badges, $summary );
		} catch ( ChangeOpException $e ) {
			$this->showErrorHTML( $e->getMessage() );
			return false;
		}

		if ( !$status->isGood() ) {
			$this->showErrorHTML( $status->getHTML() );
			return false;
		}

		return $summary;
	}

	/**
	 * @todo could factor this out into a special page form builder and renderer
	 */
	protected function addCopyrightText() {
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$this->rightsUrl,
			$this->rightsText
		);

		$html = $copyrightView->getHtml( $this->getLanguage() );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Checks if the site id is valid.
	 *
	 * @since 0.4
	 *
	 * @param $siteId string the site id
	 *
	 * @return bool
	 */
	private function isValidSiteId( $siteId ) {
		return $siteId !== null && $this->siteStore->getSite( $siteId ) !== null;
	}

	/**
	 * @see SpecialModifyEntity::getFormElements()
	 *
	 * @param Entity $entity
	 *
	 * @return string
	 */
	protected function getFormElements( Entity $entity = null ) {
		if ( $this->page === null ) {
			$this->page = $this->site === null ? '' : $this->getSiteLink( $entity, $this->site );
		}
		if ( $this->badges === null ) {
			$this->badges = $this->site === null ? array() : $this->getBadges( $entity, $this->site );
		}
		$pageinput = Html::input(
			'page',
			$this->getRequest()->getVal( 'page' ) ? $this->getRequest()->getVal( 'page' ) : $this->page,
			'text',
			array(
				'class' => 'wb-input wb-input-text',
				'id' => 'wb-setsitelink-page',
				'size' => 50
			)
		);

		// Experimental setting of badges on the special page
		// @todo remove experimental once JS UI is in place, (also remove the experimental test case)
		// @todo when removing from experimental update i18n wikibase-setsitelink-intro
		//	   @see https://gerrit.wikimedia.org/r/#/c/94939/13/repo/Wikibase.i18n.php
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$pageinput .= Html::element( 'br' )
			. Html::element(
				'label',
				array(
					'for' => 'wb-setsitelink-badges',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-setsitelink-badges' )->text()
			)
			. Html::input(
				'badges',
				implode( '|', $this->badges ),
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-setsitelink-badges'
				)
			);
		}

		$site = $this->siteStore->getSite( $this->site );

		if ( $entity !== null && $this->site !== null && $site !== null ) {
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
		}
		else {
			return Html::element(
				'p',
				array(),
				$this->msg( 'wikibase-setsitelink-intro' )->parse()
			)
			. parent::getFormElements( $entity )
			. Html::element(
				'label',
				array(
					'for' => 'wb-setsitelink-site',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-setsitelink-site' )->text()
			)
			. Html::input(
				'site',
				$this->getRequest()->getVal( 'site' ),
				'text',
				array(
					'class' => 'wb-input',
					'id' => 'wb-setsitelink-site'
				)
			)
			. Html::element( 'br' )
			. Html::element(
				'label',
				array(
					'for' => 'wb-setsitelink-page',
					'class' => 'wb-label'
				),
				$this->msg( 'wikibase-setsitelink-label' )->text()
			)
			. $pageinput;
		}
	}

	/**
	 * Returning the site page of the entity.
	 *
	 * @since 0.4
	 *
	 * @param Item|null $entity
	 * @param string $siteId
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	protected function getSiteLink( $entity, $siteId ) {
		if ( $entity === null || !( $entity instanceof Item ) ) {
			return '';
		}

		/* @var Item $entity */

		if ( $entity->hasLinkToSite( $siteId ) ) {
			return $entity->getSitelink( $siteId )->getPageName();
		}

		return '';
	}

	/**
	 * Returning the badges of the entity.
	 *
	 * @since 0.5
	 *
	 * @param Item|null $entity
	 * @param string $siteId
	 *
	 * @throws OutOfBoundsException
	 * @return string[]
	 */
	protected function getBadges( $entity, $siteId ) {
		if ( $entity === null || !( $entity instanceof Item ) ) {
			return array();
		}

		/* @var Item $entity */

		if ( $entity->hasLinkToSite( $siteId ) ) {
			$badges = array();
			foreach ( $entity->getSitelink( $siteId )->getBadges() as $badge ) {
				$badges[] = $badge->getPrefixedId();
			}
			return $badges;
		}

		return array();
	}

	/**
	 * Validates badges from params and turns them into an array of ItemIds.
	 *
	 * @since 0.5
	 *
	 * @param string[] $badges
	 * @param Status $status
	 *
	 * @return ItemId[]|boolean
	 */
	protected function parseBadges( array $badges, Status $status ) {
		$repo = WikibaseRepo::getDefaultInstance();

		$entityIdParser = $repo->getEntityIdParser();

		$badgesObjects = array();

		foreach ( $badges as $badge ) {
			try {
				$badgeId = $entityIdParser->parse( $badge );
			} catch ( EntityIdParsingException $ex ) {
				$status->fatal( 'wikibase-setsitelink-not-badge', $badge );
				return false;
			}

			if ( !( $badgeId instanceof ItemId ) ) {
				$status->fatal( 'wikibase-setsitelink-not-item', $badgeId->getPrefixedId() );
				return false;
			}

			$badgeItems = WikibaseRepo::getDefaultInstance()->getSettings()
					->getSetting( 'badgeItems' );

			if ( !array_key_exists( $badgeId->getPrefixedId(), $badgeItems ) ) {
				$status->fatal( 'wikibase-setsitelink-not-badge', $badgeId->getPrefixedId() );
				return false;
			}

			$itemTitle = $this->getEntityTitle( $badgeId );

			if ( is_null( $itemTitle ) || !$itemTitle->exists() ) {
				$status->fatal( 'wikibase-setsitelink-not-badge', $badgeId );
				return false;
			}

			$badgesObjects[] = $badgeId;
		}

		return $badgesObjects;
	}

	/**
	 * Setting the sitepage of the entity.
	 *
	 * @since 0.4
	 *
	 * @param Item $item
	 * @param string $siteId
	 * @param string $pageName
	 * @param string[] $badges
	 * @param Summary &$summary The summary for this edit will be saved here.
	 *
	 * @return Status
	 */
	protected function setSiteLink( Item $item, $siteId, $pageName, $badges, &$summary ) {
		$status = Status::newGood();
		$site = $this->siteStore->getSite( $siteId );

		if ( $site === null ) {
			$status->fatal( 'wikibase-setsitelink-invalid-site', $siteId );
			return $status;
		}

		$summary = $this->getSummary( 'wbsetsitelink' );

		if ( $pageName === '' ) {
			$pageName = null;

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

		$hasBadges = $item->getSiteLinkList()->hasLinkWithSiteId( $siteId ) &&
				$item->getSiteLinkList()->getBySiteId( $siteId )->getBadges();

		if ( $badges !== null ) {
			$badges = $this->parseBadges( $badges, $status );
		} elseif( $hasBadges ) {
			// If badges are already present and the field is empty, remove them
			$badges = array();
		} else {
			$badges = null;
		}

		if ( !$status->isGood() ) {
			return $status;
		}

		$changeOp = $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $siteId, $pageName, $badges );

		$this->applyChangeOp( $changeOp, $item, $summary );

		return $status;
	}

}
