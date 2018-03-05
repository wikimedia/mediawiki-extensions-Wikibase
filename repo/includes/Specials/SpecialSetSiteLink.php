<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use InvalidArgumentException;
use OutOfBoundsException;
use SiteLookup;
use Status;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Special page for setting the sitepage of a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetSiteLink extends SpecialModifyEntity {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

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
	 * @param SpecialPageCopyrightView $copyrightView
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EditEntityFactory $editEntityFactory
	 * @param SiteLookup $siteLookup
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param string[] $siteLinkGroups
	 * @param string[] $badgeItems
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 */
	public function __construct(
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory,
		SiteLookup $siteLookup,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		array $siteLinkGroups,
		array $badgeItems,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	) {
		parent::__construct(
			'SetSiteLink',
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory
		);

		$this->siteLookup = $siteLookup;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->badgeItems = $badgeItems;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyEntity::processArguments()
	 *
	 * @param string|null $subPage
	 */
	protected function processArguments( $subPage ) {
		parent::processArguments( $subPage );

		$request = $this->getRequest();
		// explode the sub page from the format Special:SetSitelink/q123/enwiki
		$parts = ( $subPage === '' ) ? [] : explode( '/', $subPage, 2 );

		$entityId = $this->getEntityId();

		// check if id belongs to an item
		if ( $entityId !== null
			&& $entityId->getEntityType() !== Item::ENTITY_TYPE
		) {
			$msg = $this->msg( 'wikibase-setsitelink-not-item', $entityId->getSerialization() );
			$this->showErrorHTML( $msg->parse() );
		}

		$this->site = trim( $request->getVal( 'site', isset( $parts[1] ) ? $parts[1] : '' ) );

		if ( $this->site === '' ) {
			$this->site = null;
		}

		if ( $this->site !== null && !$this->isValidSiteId( $this->site ) ) {
			$this->showErrorHTML( $this->msg(
				'wikibase-setsitelink-invalid-site',
				wfEscapeWikiText( $this->site )
			)->parse() );
		}

		$this->page = $request->getVal( 'page' );

		// If the user just enters an item id and a site, dont remove the site link.
		// The user can remove the site link in the second form where it has to be
		// actually removed. This prevents users from removing site links accidentally.
		if ( !$request->getCheck( 'remove' ) && $this->page === '' ) {
			$this->page = null;
		}

		$this->badges = array_intersect(
			array_keys( $this->badgeItems ),
			$request->getArray( 'badges', [] )
		);
	}

	/**
	 * @see SpecialModifyEntity::validateInput()
	 *
	 * @return bool
	 */
	protected function validateInput() {
		if ( !$this->isValidSiteId( $this->site ) ) {
			return false;
		}

		if ( $this->page === null ) {
			return false;
		}

		if ( !parent::validateInput() ) {
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
	 * @see SpecialModifyEntity::getForm()
	 *
	 * @param EntityDocument|null $entity
	 *
	 * @return HTMLForm
	 */
	protected function getForm( EntityDocument $entity = null ) {
		if ( $this->page === null ) {
			$this->page = $this->site === null ? '' : $this->getSiteLink( $entity, $this->site );
		}
		if ( empty( $this->badges ) ) {
			$this->badges = $this->site === null ? [] : $this->getBadges( $entity, $this->site );
		}
		$pageinput = [
			'page' => [
				'name' => 'page',
				'label-message' => 'wikibase-setsitelink-label',
				'type' => 'text',
				'default' => $this->getRequest()->getVal( 'page' ) ?: $this->page,
				'nodata' => true,
				'cssclass' => 'wb-input wb-input-text',
				'id' => 'wb-setsitelink-page'
			]
		];

		if ( !empty( $this->badgeItems ) ) {
			$pageinput['badges'] = $this->getMultiSelectForBadges();
		}

		$site = $this->siteLookup->getSite( $this->site );

		if ( $entity !== null && $this->site !== null && $site !== null ) {
			// show the detailed form which also allows users to remove site links
			$intro = $this->msg(
				'wikibase-setsitelink-introfull',
				$this->getEntityTitle( $entity->getId() )->getPrefixedText(),
				'[' . $site->getPageUrl( '' ) . ' ' . $this->site . ']'
			)->parse();
			$formDescriptor = [
				'site' => [
					'name' => 'site',
					'type' => 'hidden',
					'default' => $this->site
				],
				'id' => [
					'name' => 'id',
					'type' => 'hidden',
					'default' => $this->getEntityId()->getSerialization()
				],
				'remove' => [
					'name' => 'remove',
					'type' => 'hidden',
					'default' => 'remove'
				],
				'revid' => [
					'name' => 'revid',
					'type' => 'hidden',
					'default' => $this->getBaseRevision()->getRevisionId(),
				]
			];
		} else {
			$intro = $this->msg( 'wikibase-setsitelink-intro' )->text();

			if ( !empty( $this->badgeItems ) ) {
				$intro .= $this->msg( 'word-separator' )->text() . $this->msg( 'wikibase-setsitelink-intro-badges' )->text();
			}

			$formDescriptor = $this->getFormElements( $entity );
			$formDescriptor['site'] = [
				'name' => 'site',
				'label-message' => 'wikibase-setsitelink-site',
				'type' => 'text',
				'default' => $this->getRequest()->getVal( 'site' ) ?: $this->site,
				'cssclass' => 'wb-input',
				'id' => 'wb-setsitelink-site'
			];
		}
		$formDescriptor = array_merge( $formDescriptor, $pageinput );

		return HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setHeaderText( Html::rawElement( 'p', [], $intro ) );
	}

	/**
	 * Returns an array for generating a checkbox for each badge.
	 *
	 * @return array
	 */
	private function getMultiSelectForBadges() {
		$options = [];
		$default = [];

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

			$label = $labelLookup->getLabel( $badgeId );
			$label = $label === null ? $idSerialization : $label->getText();

			$options[$label] = $idSerialization;
			if ( in_array( $idSerialization, $this->badges ) ) {
				$default[] = $idSerialization;
			}
		}

		return [
			'name' => 'badges',
			'type' => 'multiselect',
			'label-message' => 'wikibase-setsitelink-badges',
			'options' => $options,
			'default' => $default
		];
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

		return $item->getSiteLink( $siteId )->getPageName();
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
			return [];
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
		$badgesObjects = [];

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
		$site = $this->siteLookup->getSite( $siteId );

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
				$status->fatal( 'wikibase-error-ui-no-external-page', $siteId, $this->page );
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
