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
				$summary = $this->getEditSummaryForDiff( $entityDiff );
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

	public function getEditSummaryForDiff( EntityDiff $entityDiff ): Summary {
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
			);
		} else {
			return $this->getGenericEditSummary();
		}
	}

	private function getEditSummaryForClaims( Diff $claimsDiff ): Summary {
		if ( $claimsDiff->count() === 1 ) {
			return $this->getEditSummaryForClaimDiff( array_first( $claimsDiff->getOperations() ) );
		}
		return $this->getGenericEditSummary(); // TODO define messages for editing multiple statements (of the same property?)
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
			// if this message is too noisy, feel free to remove it once a Phabricator task for the Wikidata team has been filed
			$this->logger->warning( __METHOD__ . ': unexpected diff class {className}', [
				'className' => get_class( $claimDiff ),
				'diffOp' => $claimDiff,
			] );
			return $this->getGenericEditSummary();
		}
		$summaryBuilder = new ClaimSummaryBuilder(
			'wbsetclaim',
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

	private function getGenericEditSummary(): Summary {
		return new Summary( 'wbeditentity', 'update' );
	}

}
