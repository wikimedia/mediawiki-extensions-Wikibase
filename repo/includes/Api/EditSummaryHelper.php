<?php

namespace Wikibase\Repo\Api;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Summary;
use Wikimedia\Assert\Assert;

/**
 * Helper methods for preparing summary instance for editing entity activity
 * @license GPL-2.0-or-later
 */
class EditSummaryHelper {

	public const SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES = 50;

	public function __construct(
		private readonly EntityDiffer $entityDiffer,
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
		$diffCount = $entityDiff->count();

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
