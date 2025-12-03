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
		$summary->setUserSummary( $preparedParameters[ModifyEntity::PARAM_SUMMARY] );

		if ( $this->isUpdatingExistingEntity( $preparedParameters ) ) {
			if ( $preparedParameters[EditEntity::PARAM_CLEAR] !== false ) {
				$summary->setAction( 'override' );
			} else {
				$entityDiff = $this->entityDiffer->diffEntities( $oldEntity, $newEntity );
				$this->prepareEditSummary( $summary, $entityDiff );
			}
		} else {
			$summary->setAction( 'create-' . $newEntity->getType() );
		}

		return $summary;
	}

	public function prepareEditSummary( Summary $summary, EntityDiff $entityDiff ): void {
		$labelsDiff = $entityDiff->getLabelsDiff();
		$descriptionsDiff = $entityDiff->getDescriptionsDiff();
		$aliasesDiff = $entityDiff->getAliasesDiff();
		$diffCount = $entityDiff->count();

		$languagesDiffCount = $labelsDiff->count() + $descriptionsDiff->count() + $aliasesDiff->count();
		if ( $languagesDiffCount > 0 ) {
			$this->prepareEditSummaryForLanguages(
				$summary,
				$labelsDiff,
				$descriptionsDiff,
				$aliasesDiff,
				$diffCount !== $languagesDiffCount,
			);
		} else {
			$this->prepareGenericEditSummary( $summary );
		}
	}

	private function isUpdatingExistingEntity( array $preparedParameters ): bool {
		$isTargetingEntity = isset( $preparedParameters['id'] );
		$isTargetingPage = isset( $preparedParameters['site'] ) && isset( $preparedParameters['title'] );

		return $isTargetingEntity xor $isTargetingPage;
	}

	private function prepareEditSummaryForLanguages(
		Summary $summary,
		Diff $labelsDiff,
		Diff $descriptionsDiff,
		Diff $aliasesDiff,
		bool $hasOtherChanges,
	): void {
		$changedLanguagesAsKeys = [];
		foreach ( [ $labelsDiff, $descriptionsDiff, $aliasesDiff ] as $diff ) {
			Assert::invariant( $diff->isAssociative(), '$diff->isAssociative()' );
			foreach ( $diff->getOperations() as $languageCode => $diffOp ) {
				$changedLanguagesAsKeys[$languageCode] = 1;
			}
		}
		$changedLanguagesCount = count( $changedLanguagesAsKeys );
		Assert::invariant( $changedLanguagesCount > 0, '$changedLanguagesCount > 0' );

		if ( $changedLanguagesCount <= self::SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES ) {
			$summary->setAction( $hasOtherChanges ? 'update-languages-and-other-short' : 'update-languages-short' );
			$summary->setAutoCommentArgs( [ array_keys( $changedLanguagesAsKeys ) ] );
		} else {
			$summary->setAction( $hasOtherChanges ? 'update-languages-and-other' : 'update-languages' );
			$summary->setAutoCommentArgs( [ $changedLanguagesCount ] );
		}
	}

	private function prepareGenericEditSummary( Summary $summary ): void {
		$summary->setAction( 'update' );
	}

}
