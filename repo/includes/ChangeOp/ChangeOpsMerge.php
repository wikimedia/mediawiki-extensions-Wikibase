<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use Site;
use SiteLookup;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Validators\CompositeEntityValidator;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\UniquenessViolation;

/**
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 */
class ChangeOpsMerge {

	/**
	 * @var Item
	 */
	private $fromItem;

	/**
	 * @var Item
	 */
	private $toItem;

	/**
	 * @var ChangeOps
	 */
	private $fromChangeOps;

	/**
	 * @var ChangeOps
	 */
	private $toChangeOps;

	/**
	 * @var string[]
	 */
	private $ignoreConflicts;

	/**
	 * @var EntityConstraintProvider
	 */
	private $constraintProvider;

	/**
	 * @var ChangeOpFactoryProvider
	 */
	private $changeOpFactoryProvider;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	public static $conflictTypes = [ 'description', 'sitelink', 'statement' ];

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param string[] $ignoreConflicts list of elements to ignore conflicts for
	 *        can only contain 'description' and or 'sitelink' and or 'statement'
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ChangeOpFactoryProvider $changeOpFactoryProvider
	 * @param SiteLookup $siteLookup
	 *
	 * @todo: Injecting ChangeOpFactoryProvider is an Abomination Unto Nuggan, we'll
	 *        need a MergeChangeOpsSequenceBuilder or some such. This will allow us
	 *        to merge different kinds of entities nicely, too.
	 */
	public function __construct(
		Item $fromItem,
		Item $toItem,
		array $ignoreConflicts,
		EntityConstraintProvider $constraintProvider,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		SiteLookup $siteLookup
	) {
		$this->assertValidIgnoreConflictValues( $ignoreConflicts );

		$this->fromItem = $fromItem;
		$this->toItem = $toItem;
		$this->fromChangeOps = new ChangeOps();
		$this->toChangeOps = new ChangeOps();
		$this->ignoreConflicts = $ignoreConflicts;
		$this->constraintProvider = $constraintProvider;
		$this->siteLookup = $siteLookup;

		$this->changeOpFactoryProvider = $changeOpFactoryProvider;
	}

	/**
	 * @param string[] $ignoreConflicts can contain strings 'description' or 'sitelink'
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertValidIgnoreConflictValues( array $ignoreConflicts ) {
		if ( array_diff( $ignoreConflicts, self::$conflictTypes ) ) {
			throw new InvalidArgumentException(
				'$ignoreConflicts array can only contain "description" and or "sitelink" and or "statement" values'
			);
		}
	}

	/**
	 * @return FingerprintChangeOpFactory
	 */
	private function getFingerprintChangeOpFactory() {
		return $this->changeOpFactoryProvider->getFingerprintChangeOpFactory();
	}

	/**
	 * @return StatementChangeOpFactory
	 */
	private function getStatementChangeOpFactory() {
		return $this->changeOpFactoryProvider->getStatementChangeOpFactory();
	}

	/**
	 * @return SiteLinkChangeOpFactory
	 */
	private function getSiteLinkChangeOpFactory() {
		return $this->changeOpFactoryProvider->getSiteLinkChangeOpFactory();
	}

	/**
	 * @throws ChangeOpException
	 */
	public function apply() {
		// NOTE: we don't want to validate the ChangeOps individually, since they represent
		// data already present and saved on the system. Also, validating each would be
		// potentially expensive.

		$this->generateChangeOps();

		$this->fromChangeOps->apply( $this->fromItem );
		$this->toChangeOps->apply( $this->toItem );

		//NOTE: we apply constraint checks on the modified items, but no
		//      validation of individual change ops, since we are merging
		//      two valid items.
		$this->applyConstraintChecks( $this->toItem, $this->fromItem->getId() );
	}

	private function generateChangeOps() {
		$this->generateLabelsChangeOps();
		$this->generateDescriptionsChangeOps();
		$this->generateAliasesChangeOps();
		$this->generateSitelinksChangeOps();
		$this->generateStatementsChangeOps();
	}

	private function generateLabelsChangeOps() {
		foreach ( $this->fromItem->getLabels()->toTextArray() as $langCode => $label ) {
			if ( !$this->toItem->getLabels()->hasTermForLanguage( $langCode )
				|| $this->toItem->getLabels()->getByLanguage( $langCode )->getText() === $label
			) {
				$this->fromChangeOps->add( $this->getFingerprintChangeOpFactory()->newRemoveLabelOp( $langCode ) );
				$this->toChangeOps->add( $this->getFingerprintChangeOpFactory()->newSetLabelOp( $langCode, $label ) );
			} else {
				$this->fromChangeOps->add( $this->getFingerprintChangeOpFactory()->newRemoveLabelOp( $langCode ) );
				$this->toChangeOps->add( $this->getFingerprintChangeOpFactory()->newAddAliasesOp( $langCode, [ $label ] ) );
			}
		}
	}

	private function generateDescriptionsChangeOps() {
		foreach ( $this->fromItem->getDescriptions()->toTextArray() as $langCode => $desc ) {
			if ( !$this->toItem->getDescriptions()->hasTermForLanguage( $langCode )
				|| $this->toItem->getDescriptions()->getByLanguage( $langCode )->getText() === $desc
			) {
				$this->fromChangeOps->add( $this->getFingerprintChangeOpFactory()->newRemoveDescriptionOp( $langCode ) );
				$this->toChangeOps->add( $this->getFingerprintChangeOpFactory()->newSetDescriptionOp( $langCode, $desc ) );
			} elseif ( !in_array( 'description', $this->ignoreConflicts ) ) {
				throw new ChangeOpException( "Conflicting descriptions for language {$langCode}" );
			}
		}
	}

	private function generateAliasesChangeOps() {
		foreach ( $this->fromItem->getAliasGroups()->toTextArray() as $langCode => $aliases ) {
			$this->fromChangeOps->add( $this->getFingerprintChangeOpFactory()->newRemoveAliasesOp( $langCode, $aliases ) );
			$this->toChangeOps->add( $this->getFingerprintChangeOpFactory()->newAddAliasesOp( $langCode, $aliases ) );
		}
	}

	private function generateSitelinksChangeOps() {
		foreach ( $this->fromItem->getSiteLinkList()->toArray() as $fromSiteLink ) {
			$siteId = $fromSiteLink->getSiteId();
			if ( !$this->toItem->getSiteLinkList()->hasLinkWithSiteId( $siteId ) ) {
				$this->generateSitelinksChangeOpsWithNoConflict( $fromSiteLink );
			} else {
				$this->generateSitelinksChangeOpsWithConflict( $fromSiteLink );
			}
		}
	}

	private function generateSitelinksChangeOpsWithNoConflict( SiteLink $fromSiteLink ) {
		$siteId = $fromSiteLink->getSiteId();
		$this->fromChangeOps->add( $this->getSiteLinkChangeOpFactory()->newRemoveSiteLinkOp( $siteId ) );
		$this->toChangeOps->add(
			$this->getSiteLinkChangeOpFactory()->newSetSiteLinkOp(
				$siteId,
				$fromSiteLink->getPageName(),
				$fromSiteLink->getBadges()
			)
		);
	}

	private function generateSitelinksChangeOpsWithConflict( SiteLink $fromSiteLink ) {
		$siteId = $fromSiteLink->getSiteId();
		$toSiteLink = $this->toItem->getSiteLink( $siteId );
		$fromPageName = $fromSiteLink->getPageName();
		$toPageName = $toSiteLink->getPageName();

		if ( $fromPageName !== $toPageName ) {
			$site = $this->getSite( $siteId );
			$fromPageName = $site->normalizePageName( $fromPageName );
			$toPageName = $site->normalizePageName( $toPageName );
		}
		if ( $fromPageName === $toPageName ) {
			$this->fromChangeOps->add( $this->getSiteLinkChangeOpFactory()->newRemoveSiteLinkOp( $siteId ) );
			$this->toChangeOps->add(
				$this->getSiteLinkChangeOpFactory()->newSetSiteLinkOp(
					$siteId,
					$fromPageName,
					array_unique( array_merge( $fromSiteLink->getBadges(), $toSiteLink->getBadges() ) )
				)
			);
		} elseif ( !in_array( 'sitelink', $this->ignoreConflicts ) ) {
			throw new ChangeOpException( "Conflicting sitelinks for {$siteId}" );
		}
	}

	/**
	 * @param string $siteId
	 *
	 * @throws ChangeOpException
	 * @return Site
	 */
	private function getSite( $siteId ) {
		$site = $this->siteLookup->getSite( $siteId );
		if ( $site === null ) {
			throw new ChangeOpException( "Conflicting sitelinks for {$siteId}, Failed to normalize" );
		}
		return $site;
	}

	private function generateStatementsChangeOps() {
		if ( !in_array( 'statement', $this->ignoreConflicts ) ) {
			foreach ( $this->toItem->getStatements()->toArray() as $toStatement ) {
				$this->checkIsLink( $toStatement, $this->fromItem->getId() );
			}
		}

		foreach ( $this->fromItem->getStatements()->toArray() as $fromStatement ) {
			if ( !in_array( 'statement', $this->ignoreConflicts ) ) {
				$this->checkIsLink( $fromStatement, $this->toItem->getId() );
			}

			$this->fromChangeOps->add( $this->getStatementChangeOpFactory()->newRemoveStatementOp( $fromStatement->getGuid() ) );

			$toStatement = clone $fromStatement;
			$toStatement->setGuid( null );
			$toMergeToStatement = $this->findEquivalentStatement( $toStatement );

			if ( $toMergeToStatement ) {
				$this->generateReferencesChangeOps( $toStatement, $toMergeToStatement );
			} else {
				$this->toChangeOps->add( $this->getStatementChangeOpFactory()->newSetStatementOp( $toStatement ) );
			}
		}
	}

	/**
	 * Checks if the statement's main snak is a link to the given item id
	 *
	 * @param Statement $statement
	 * @param ItemId $itemId
	 *
	 * @throws ChangeOpException
	 */
	private function checkIsLink( Statement $statement, ItemId $itemId ) {
		$snak = $statement->getMainSnak();

		if ( !( $snak instanceof PropertyValueSnak ) ) {
			return;
		}

		$dataValue = $snak->getDataValue();

		if ( !( $dataValue instanceof EntityIdValue ) ) {
			return;
		}

		if ( $dataValue->getEntityId()->equals( $itemId ) ) {
			throw new ChangeOpException(
				"The two items cannot be merged because one of them links to the other using property {$statement->getPropertyId()}"
			);
		}
	}

	/**
	 * Finds a statement in the target entity with the same main snak and qualifiers as $fromStatement
	 *
	 * @param Statement $fromStatement
	 *
	 * @return Statement|false Statement to merge reference into or false
	 */
	private function findEquivalentStatement( Statement $fromStatement ) {
		$fromHash = $this->getStatementHash( $fromStatement );

		/** @var Statement $statement */
		foreach ( $this->toItem->getStatements() as $statement ) {
			$toHash = $this->getStatementHash( $statement );
			if ( $toHash === $fromHash ) {
				return $statement;
			}
		}

		return false;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string combined hash of the Mainsnak and Qualifiers
	 */
	private function getStatementHash( Statement $statement ) {
		return $statement->getMainSnak()->getHash() . $statement->getQualifiers()->getHash();
	}

	/**
	 * @param Statement $fromStatement statement to take references from
	 * @param Statement $toStatement statement to add references to
	 */
	private function generateReferencesChangeOps( Statement $fromStatement, Statement $toStatement ) {
		/** @var Reference $reference */
		foreach ( $fromStatement->getReferences() as $reference ) {
			if ( !$toStatement->getReferences()->hasReferenceHash( $reference->getHash() ) ) {
				$this->toChangeOps->add( $this->getStatementChangeOpFactory()->newSetReferenceOp(
					$toStatement->getGuid(),
					$reference,
					''
				) );
			}
		}
	}

	/**
	 * @param Item $item
	 * @param ItemId $fromId
	 *
	 * @throws ChangeOpValidationException if it would not be possible to save the updated items.
	 */
	private function applyConstraintChecks( Item $item, ItemId $fromId ) {
		$constraintValidator = new CompositeEntityValidator(
			$this->constraintProvider->getUpdateValidators( $item->getType() )
		);

		$result = $constraintValidator->validateEntity( $item );
		$errors = $result->getErrors();

		$errors = $this->removeConflictsWithEntity( $errors, $fromId );

		if ( !empty( $errors ) ) {
			$result = Result::newError( $errors );
			throw new ChangeOpValidationException( $result );
		}
	}

	/**
	 * Strip any conflicts with the given $fromId from the array of Error objects
	 *
	 * @param Error[] $errors
	 * @param EntityId $fromId
	 *
	 * @return Error[]
	 */
	private function removeConflictsWithEntity( array $errors, EntityId $fromId ) {
		$filtered = [];

		foreach ( $errors as $error ) {
			if ( $error instanceof UniquenessViolation
				&& $fromId->equals( $error->getConflictingEntity() )
			) {
				continue;
			}

			$filtered[] = $error;
		}

		return $filtered;
	}

}
