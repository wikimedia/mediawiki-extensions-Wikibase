<?php

namespace Wikibase\Repo\Api;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ClaimSummaryBuilder;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikimedia\Assert\Assert;

/**
 * Helper methods for preparing summary instance for editing entity activity
 * @license GPL-2.0-or-later
 */
class EditSummaryHelper {

	public const SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES = 50;

	public function __construct(
		private readonly EntityDiffer $entityDiffer,
		private readonly LoggerInterface $logger = new NullLogger(),
	) {
	}

	public function getEditSummary( array $preparedParameters, EntityDocument $oldEntity, EntityDocument $newEntity ): Summary {
		$summary = new Summary( 'wbeditentity' );

		if ( $this->isUpdatingExistingEntity( $preparedParameters ) ) {
			if ( $preparedParameters[EditEntity::PARAM_CLEAR] !== false ) {
				$summary->setAction( 'override' );
			} else {
				$entityDiff = $this->entityDiffer->diffEntities( $oldEntity, $newEntity );
				$summary = $this->getEditSummaryForDiff( $entityDiff, $newEntity );
			}
		} else {
			$summary->setAction( 'create-' . $newEntity->getType() );
		}

		$summary->setUserSummary( $preparedParameters[ModifyEntity::PARAM_SUMMARY] );
		return $summary;
	}

	private function isUpdatingExistingEntity( array $preparedParameters ): bool {
		$isTargetingEntity = isset( $preparedParameters['id'] );
		$isTargetingPage = isset( $preparedParameters['site'] ) && isset( $preparedParameters['title'] );

		return $isTargetingEntity xor $isTargetingPage;
	}

	public function getEditSummaryForDiff( EntityDiff $entityDiff, EntityDocument $newEntity ): Summary {
		$labelsDiff = $entityDiff->getLabelsDiff();
		$descriptionsDiff = $entityDiff->getDescriptionsDiff();
		$aliasesDiff = $entityDiff->getAliasesDiff();
		$claimsDiff = $entityDiff->getClaimsDiff();
		$diffCount = $entityDiff->count();

		if ( $claimsDiff->count() === $diffCount ) {
			return $this->getEditSummaryForClaims( $claimsDiff );
		}

		$languagesDiffCount = $labelsDiff->count() + $descriptionsDiff->count() + $aliasesDiff->count();
		if ( $languagesDiffCount > 0 ) {
			return $this->getEditSummaryForLanguages(
				$labelsDiff,
				$descriptionsDiff,
				$aliasesDiff,
				$diffCount !== $languagesDiffCount,
				$newEntity,
			);
		} else {
			return $this->getGenericEditSummary();
		}
	}

	private function getEditSummaryForClaims( Diff $claimsDiff ): Summary {
		if ( $claimsDiff->count() === 1 ) {
			return $this->getEditSummaryForClaimDiff( array_first( $claimsDiff->getOperations() ) );
		}
		$statementCountsByPropertyId = [];
		$diffOpClasses = [];
		foreach ( $claimsDiff->getOperations() as $diffOp ) {
			$diffOpClasses[get_class( $diffOp )] = true;
			/** @var Statement $statement */
			if ( $diffOp instanceof DiffOpAdd || $diffOp instanceof DiffOpChange ) {
				$statement = $diffOp->getNewValue();
			} elseif ( $diffOp instanceof DiffOpRemove ) {
				$statement = $diffOp->getOldValue();
			} else {
				return $this->getFallbackEditSummary( $diffOp, [ 'claimsDiff' => $claimsDiff ] );
			}
			'@phan-var Statement $statement';
			$propertyId = $statement->getPropertyId();
			$pid = $propertyId->getSerialization();
			$statementCountsByPropertyId[$pid] = ( $statementCountsByPropertyId[$pid] ?? 0 ) + 1;
		}
		$subAction = 'update';
		if ( count( $diffOpClasses ) == 1 ) {
			if ( $diffOpClasses[DiffOpAdd::class] ?? false ) {
				$subAction = 'add';
			} elseif ( $diffOpClasses[DiffOpRemove::class] ?? false ) {
				$subAction = 'remove';
			}
		}
		if ( count( $statementCountsByPropertyId ) === 1 ) {
			return new Summary(
				'wbeditentity',
				// possible actions: statements-single-property-add,
				// statements-single-property-remove, statements-single-property-update
				"statements-single-property-$subAction",
				commentArgs: [ array_first( $statementCountsByPropertyId ) ],
				// @phan-suppress-next-line PhanPossiblyUndeclaredVariable -- loop must have run at least once to reach here
				summaryArgs: [ $propertyId ]
			);
		} else {
			return new Summary(
				'wbeditentity',
				// possible actions: statements-multiple-properties-add,
				// statements-multiple-properties-remove, statements-multiple-properties-update
				"statements-multiple-properties-$subAction",
				commentArgs: [ count( $statementCountsByPropertyId ) ]
			);
		}
	}

	private function getEditSummaryForClaimDiff( DiffOp $claimDiff ): Summary {
		if ( $claimDiff instanceof DiffOpRemove ) {
			/** @var Statement $statement */
			$statement = $claimDiff->getOldValue();
			'@phan-var Statement $statement';
			return new Summary(
				'wbremoveclaims',
				'remove',
				summaryArgs: [ [ $statement->getPropertyId()->getSerialization() => $statement->getMainSnak() ] ]
			);
		} elseif ( !( $claimDiff instanceof DiffOpChange || $claimDiff instanceof DiffOpAdd ) ) {
			return $this->getFallbackEditSummary( $claimDiff );
		}
		$summaryBuilder = new ClaimSummaryBuilder(
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);
		return $summaryBuilder->buildClaimSummary(
			$claimDiff instanceof DiffOpChange ? $claimDiff->getOldValue() : null,
			$claimDiff->getNewValue(),
		);
	}

	private function getEditSummaryForLanguages(
		Diff $labelsDiff,
		Diff $descriptionsDiff,
		Diff $aliasesDiff,
		bool $hasOtherChanges,
		EntityDocument $newEntity,
	): Summary {
		$changedLanguagesAsKeys = [];
		foreach ( [ $labelsDiff, $descriptionsDiff, $aliasesDiff ] as $diff ) {
			Assert::invariant( $diff->isAssociative(), '$diff->isAssociative()' );
			foreach ( $diff->getOperations() as $languageCode => $diffOp ) {
				$changedLanguagesAsKeys[$languageCode] = 1;
			}
		}
		$changedLanguagesCount = count( $changedLanguagesAsKeys );
		Assert::invariant( $changedLanguagesCount > 0, '$changedLanguagesCount > 0' );

		if ( $changedLanguagesCount === 1 && !$hasOtherChanges ) {
			return $this->getEditSummaryForLanguage( array_key_first( $changedLanguagesAsKeys ),
				$labelsDiff, $descriptionsDiff, $aliasesDiff, $newEntity );
		}

		$summary = new Summary( 'wbeditentity' );
		if ( $changedLanguagesCount <= self::SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES ) {
			$summary->setAction( $hasOtherChanges ? 'update-languages-and-other-short' : 'update-languages-short' );
			$summary->setAutoCommentArgs( [ array_keys( $changedLanguagesAsKeys ) ] );
		} else {
			$summary->setAction( $hasOtherChanges ? 'update-languages-and-other' : 'update-languages' );
			$summary->setAutoCommentArgs( [ $changedLanguagesCount ] );
		}
		return $summary;
	}

	private function getEditSummaryForLanguage(
		string $languageCode,
		Diff $labelsDiff,
		Diff $descriptionsDiff,
		Diff $aliasesDiff,
		EntityDocument $newEntity,
	): Summary {
		$labelsDiffCount = $labelsDiff->count();
		$descriptionsDiffCount = $descriptionsDiff->count();
		$aliasesDiffCount = $aliasesDiff->count();

		if ( $aliasesDiffCount > 0 && ( $labelsDiffCount + $descriptionsDiffCount ) === 0 ) {
			$summary = new Summary( 'wbsetaliases' );
			/** @var Diff $diff */
			$diff = $aliasesDiff->getOperations()[$languageCode];
			'@phan-var Diff $diff';
			$addedAliases = $diff->getAddedValues();
			$removedAliases = $diff->getRemovedValues();
			if ( count( $addedAliases ) === $aliasesDiffCount ) {
				$summary->setAction( 'add' );
				$summary->addAutoSummaryArgs( ...$addedAliases );
			} elseif ( count( $removedAliases ) === $aliasesDiffCount ) {
				$summary->setAction( 'remove' );
				$summary->addAutoSummaryArgs( ...$removedAliases );
			} else {
				$summary->setAction( 'update' );
				Assert::parameterType( AliasesProvider::class, $newEntity, '$newEntity' );
				/** @var AliasesProvider $newEntity */
				'@phan-var AliasesProvider $newEntity';
				$summary->addAutoSummaryArgs( $newEntity->getAliasGroups()->getByLanguage( $languageCode )->getAliases() );
			}
		} elseif ( $aliasesDiffCount === 0 && ( $labelsDiffCount + $descriptionsDiffCount ) === 1 ) {
			if ( $labelsDiffCount === 1 ) {
				$summary = new Summary( 'wbsetlabel' );
				$singleDiffOp = $labelsDiff->getOperations()[$languageCode];
			} else {
				$summary = new Summary( 'wbsetdescription' );
				$singleDiffOp = $descriptionsDiff->getOperations()[$languageCode];
			}

			if ( $singleDiffOp instanceof DiffOpAdd ) {
				$summary->setAction( 'add' );
				$summary->addAutoSummaryArgs( $singleDiffOp->getNewValue() );
			} elseif ( $singleDiffOp instanceof DiffOpRemove ) {
				$summary->setAction( 'remove' );
				$summary->addAutoSummaryArgs( $singleDiffOp->getOldValue() );
			} elseif ( $singleDiffOp instanceof DiffOpChange ) {
				$summary->setAction( 'set' );
				$summary->addAutoSummaryArgs( $singleDiffOp->getNewValue() );
			} else {
				return $this->getFallbackEditSummary( $singleDiffOp );
			}
		} else {
			$label = '';
			$description = '';
			$aliases = [];
			if ( $newEntity instanceof LabelsProvider && $newEntity->getLabels()->hasTermForLanguage( $languageCode ) ) {
				$label = $newEntity->getLabels()->getByLanguage( $languageCode )->getText();
			}
			if ( $newEntity instanceof DescriptionsProvider && $newEntity->getDescriptions()->hasTermForLanguage( $languageCode ) ) {
				$description = $newEntity->getDescriptions()->getByLanguage( $languageCode )->getText();
			}
			if ( $newEntity instanceof AliasesProvider && $newEntity->getAliasGroups()->hasGroupForLanguage( $languageCode ) ) {
				$aliases = $newEntity->getAliasGroups()->getByLanguage( $languageCode )->getAliases();
			}
			$summary = new Summary( 'wbsetlabeldescriptionaliases', );
			$summary->addAutoSummaryArgs( $label, $description, $aliases );
		}

		$summary->setLanguage( $languageCode );
		return $summary;
	}

	private function getFallbackEditSummary( DiffOp $diffOp, array $context = [] ): Summary {
		// if this message is too noisy, feel free to remove it once a Phabricator task for the Wikidata team has been filed
		$this->logger->warning( __METHOD__ . ': unexpected diff class {className}', [
			'className' => get_class( $diffOp ),
			'diffOp' => $diffOp,
			...$context,
		] );
		return $this->getGenericEditSummary();
	}

	private function getGenericEditSummary(): Summary {
		return new Summary( 'wbeditentity', 'update' );
	}

}
