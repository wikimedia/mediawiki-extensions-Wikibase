<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Summary;

/**
 * Class for sitelink change operation
 *
 * @license GPL-2.0+
 */
class ChangeOpSiteLink extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string|null
	 */
	private $pageName;

	/**
	 * @var ItemId[]|null
	 */
	private $badges;

	/**
	 * @param string $siteId
	 * @param string|null $pageName Null to remove the sitelink (if $badges are also null)
	 * @param ItemId[]|null $badges Null for no-op
	 * @param string[] $allowedBadgeItemIds
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $siteId, $pageName, array $badges = null, array $allowedBadgeItemIds = [] ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId needs to be a string' );
		}

		if ( !is_string( $pageName ) && $pageName !== null ) {
			throw new InvalidArgumentException( '$linkPage needs to be a string or null' );
		}

		// TODO: Consider using Assert for checking this and other arguments
		foreach ( $allowedBadgeItemIds as $id ) {
			if ( !is_string( $id ) ) {
				throw new InvalidArgumentException( '$allowedBadgeItemIds must be an array of strings' );
			}
		}

		if ( $badges !== null ) {
			$badges = $this->validateBadges( $badges, $allowedBadgeItemIds );
		}

		$this->siteId = $siteId;
		$this->pageName = $pageName;
		$this->badges = $badges;
	}

	/**
	 * @param ItemId[] $badges
	 * @param string[] $allowedBadgeItemIds
	 *
	 * @throws InvalidArgumentException
	 * @return ItemId[]
	 */
	private function validateBadges( array $badges, array $allowedBadgeItemIds ) {
		$uniqueBadges = array();

		foreach ( $badges as $id ) {
			if ( !( $id instanceof ItemId ) ) {
				throw new InvalidArgumentException( '$badges needs to be an array of ItemId instances' );
			}

			if ( !in_array( $id->getSerialization(), $allowedBadgeItemIds ) ) {
				throw new InvalidArgumentException( 'Item ID is not allowed as a badge: ' . $id->getSerialization() );
			}

			$uniqueBadges[$id->getSerialization()] = $id;
		}

		return array_values( $uniqueBadges );
	}

	/**
	 * @param SiteLinkList $siteLinks
	 *
	 * @return bool
	 */
	private function badgesAreEmptyAndUnchanged( SiteLinkList $siteLinks ) {
		return ( !$siteLinks->hasLinkWithSiteId( $this->siteId )
			|| $siteLinks->getBySiteId( $this->siteId )->getBadges() === array() )
			&& $this->badges === array();
	}

	/**
	 * @param SiteLinkList $siteLinks
	 * @param string &$action
	 * @param array &$commentArgs
	 *
	 * @return ItemId[]
	 */
	private function applyBadges( SiteLinkList $siteLinks, &$action, array &$commentArgs ) {
		// If badges are not set in the change make sure they remain intact
		if ( $this->badges === null ) {
			return $siteLinks->hasLinkWithSiteId( $this->siteId )
				? $siteLinks->getBySiteId( $this->siteId )->getBadges()
				: array();
		}

		if ( $this->badgesAreEmptyAndUnchanged( $siteLinks ) ) {
			return array();
		}

		$action .= $this->pageName === null ? '-badges' : '-both';
		$commentArgs[] = $this->badges;

		return $this->badges;
	}

	/**
	 * @see ChangeOp::apply
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ChangeOpSiteLink can only be applied to Item instances' );
		}

		$siteLinks = $entity->getSiteLinkList();

		if ( ( $this->pageName === null && $this->badges === null ) || $this->pageName === '' ) {
			if ( $siteLinks->hasLinkWithSiteId( $this->siteId ) ) {
				$this->updateSummary( $summary, 'remove', $this->siteId, $siteLinks->getBySiteId( $this->siteId )->getPageName() );
				$siteLinks->removeLinkWithSiteId( $this->siteId );
			} else {
				//TODO: throw error, or ignore silently?
			}
		} else {
			$commentArgs = array();

			if ( $this->pageName === null ) {
				if ( !$siteLinks->hasLinkWithSiteId( $this->siteId ) ) {
					throw new ChangeOpException( 'The sitelink does not exist', 'no-such-sitelink', [ $this->siteId ] );
				}

				// If page name is not set (but badges are) make sure that it remains intact
				$pageName = $siteLinks->getBySiteId( $this->siteId )->getPageName();
			} else {
				$pageName = $this->pageName;
				$commentArgs[] = $pageName;
			}

			$action = $siteLinks->hasLinkWithSiteId( $this->siteId ) ? 'set' : 'add';
			$badges = $this->applyBadges( $siteLinks, $action, $commentArgs );

			$this->updateSummary( $summary, $action, $this->siteId, $commentArgs );

			// FIXME: Use SiteLinkList::setNewSiteLink.
			$siteLinks->removeLinkWithSiteId( $this->siteId );
			$siteLinks->addNewSiteLink( $this->siteId, $pageName, $badges );
		}
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result Always successful.
	 */
	public function validate( EntityDocument $entity ) {
		//TODO: move validation logic from apply() here.
		return Result::newSuccess();
	}

}
