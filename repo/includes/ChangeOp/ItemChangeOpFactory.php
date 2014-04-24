<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Reference;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\SiteLinkLookup;
use Wikibase\Validators\SnakValidator;

/**
 * ChangeOpFactory for Items.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ItemChangeOpFactory extends ChangeOpFactory {

	public function __construct(
		LabelDescriptionDuplicateDetector $termDuplicateDetector,
		SiteLinkLookup $siteLinkLookup,
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		ClaimGuidParser $guidParser,
		SnakValidator $snakValidator
	) {
		parent::__construct( Item::ENTITY_TYPE,
			$termDuplicateDetector,
			$siteLinkLookup,
			$guidGenerator,
			$guidValidator,
			$guidParser,
			$snakValidator
		);
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param array|null $badges
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetSiteLinkOp( $siteId, $pageName, $badges = array() ) {
		return new ChangeOpSiteLink( $siteId, $pageName, $badges );
	}

	/**
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveSiteLinkOp( $siteId ) {
		return new ChangeOpSiteLink( $siteId, null );
	}

	/**
	 * @param string $claimGuid
	 * @param Reference|null $reference
	 * @param string $referenceHash (if empty '' a new reference will be created)
	 * @param int|null $index Indicates the new desired position in the list of references. Currently not implemented.
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetReferenceOp( $claimGuid, Reference $reference, $referenceHash, $index = null ) {
		return new ChangeOpReference( $claimGuid, $reference, $referenceHash, $this->snakValidator );
	}

	/**
	 * @param string $claimGuid
	 * @param string $referenceHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveReferenceOp( $claimGuid, $referenceHash ) {
		return new ChangeOpReferenceRemove( $claimGuid, $referenceHash );
	}

	/**
	 * @param string $claimGuid
	 * @param int $rank
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetStatementRankOp( $claimGuid, $rank ) {
		return new ChangeOpStatementRank( $claimGuid, $rank );
	}

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param array $ignoreConflicts list of elements to ignore conflicts for
	 *   can only contain 'label' and or 'description' and or 'sitelink'
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOpsMerge
	 */
	public function newMergeOps(
		Item $fromItem,
		Item $toItem,
		$ignoreConflicts = array()
	) {
		return new ChangeOpsMerge(
			$fromItem,
			$toItem,
			$ignoreConflicts,
			$this->termDuplicateDetector,
			$this->siteLinkLookup,
			$this
		);
	}
}
