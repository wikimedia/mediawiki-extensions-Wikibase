<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Summary;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveSiteLink extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $siteId;

	public function __construct( string $siteId ) {
		$this->siteId = $siteId;
	}

	/**
	 * @inheritDoc
	 */
	public function validate( EntityDocument $entity ): Result {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ChangeOpRemoveSiteLink can only be applied to Item instances' );
		}

		return Result::newSuccess();
	}

	/**
	 * @inheritDoc
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ChangeOpSiteLink can only be applied to Item instances' );
		}

		$siteLinks = $entity->getSiteLinkList();

		if ( !$siteLinks->hasLinkWithSiteId( $this->siteId ) ) {
			//TODO: throw error here and in validate? currently ignoring and declaring no changes to entity
			return new GenericChangeOpResult( $entity->getId(), false );
		}

		$this->updateSummary( $summary, 'remove', $this->siteId, $siteLinks->getBySiteId( $this->siteId )->getPageName() );
		$siteLinks->removeLinkWithSiteId( $this->siteId );
		return new GenericChangeOpResult( $entity->getId(), true );
	}
}
