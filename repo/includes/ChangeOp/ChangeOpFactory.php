<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;

/**
 * A factory for ChangeOp instances.
 *
 * Since a ChangeOp is created dynamically based on information from a user request,
 * but may require knowledge of service objects to perform their task, we need a
 * factory to encapsulate the knowledge about what ChangeOp needs which service.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface ChangeOpFactory {

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddAliasesOp( $language, array $aliases );

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetAliasesOp( $language, array $aliases );

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @return ChangeOp
	 */
	public function newRemoveAliasesOp( $language, array $aliases );

	/**
	 * @param string $language
	 * @param string $description
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetDescriptionOp( $language, $description );

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveDescriptionOp( $language );

	/**
	 * @param string $language
	 * @param string $label
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetLabelOp( $language, $label );

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveLabelOp( $language );

	/**
	 * @param string $siteId
	 * @param string|null $pageName Null in case the link with the provided siteId should be removed
	 * @param array|null $badges
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetSiteLinkOp( $siteId, $pageName, $badges = array() );

	/**
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveSiteLinkOp( $siteId );

	/**
	 * @param Claim $claim
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddClaimOp( Claim $claim, $index = null );

	/**
	 * @param Claim $claim
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetClaimOp( Claim $claim, $index = null );

	/**
	 * @param string $claimGuid
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveClaimOp( $claimGuid );

	/**
	 * @param string $claimGuid
	 * @param Snak $snak
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetMainSnakOp( $claimGuid, Snak $snak );

	/**
	 * @param string $claimGuid
	 * @param Snak $snak
	 * @param string $snakHash (if not empty '', the old snak is replaced)
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetQualifierOp( $claimGuid, Snak $snak, $snakHash );

	/**
	 * @param string $claimGuid
	 * @param string $snakHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveQualifierOp( $claimGuid, $snakHash );

	/**
	 * @param string $claimGuid
	 * @param Reference|null $reference
	 * @param string $referenceHash (if empty '' a new reference will be created)
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetReferenceOp( $claimGuid, Reference $reference, $referenceHash, $index = null );

	/**
	 * @param string $claimGuid
	 * @param string $referenceHash
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveReferenceOp( $claimGuid, $referenceHash );

	/**
	 * @param string $claimGuid
	 * @param int $rank
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetStatementRankOp( $claimGuid, $rank );

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
	 );

}
