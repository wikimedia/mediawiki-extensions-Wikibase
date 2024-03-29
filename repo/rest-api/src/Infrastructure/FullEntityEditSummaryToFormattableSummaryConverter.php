<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\RestApi\Domain\Model\PropertyEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class FullEntityEditSummaryToFormattableSummaryConverter {
	use ModifiedLanguageCodes;

	public function newSummaryForPropertyEdit( PropertyEditSummary $editSummary ): Summary {
		$originalProperty = $editSummary->getOriginalProperty();
		$patchedProperty = $editSummary->getPatchedProperty();
		$hasStatementsChanged = !$editSummary->getPatchedProperty()->getStatements()
			->equals( $editSummary->getOriginalProperty()->getStatements() );

		$modifiedLanguages = $this->modifiedLanguageCodes( $originalProperty, $patchedProperty );

		$summary = $this->setSummary( $modifiedLanguages, $hasStatementsChanged );
		$summary->setUserSummary( $editSummary->getUserComment() );

		return $summary;
	}

	private function setSummary( array $modifiedLanguages, bool $hasStatementsChanged ): Summary {
		$languagesCount = count( $modifiedLanguages );

		if ( $languagesCount >= ChangedLanguagesCounter::SHORTENED_SUMMARY_MAX_EDIT ) {
			$actionName = $hasStatementsChanged ? 'update-languages-and-other' : 'update-languages';
			$commentArgs = [ (string)$languagesCount ];
		} elseif ( $languagesCount > 0 ) {
			$actionName = $hasStatementsChanged ? 'update-languages-and-other-short' : 'update-languages-short';
			$commentArgs = [ implode( ', ', $modifiedLanguages ) ];
		} else {
			$actionName = 'update';
			$commentArgs = [];
		}

		return new Summary( 'wbeditentity', $actionName, null, $commentArgs );
	}

	private function modifiedLanguageCodes( Property $original, Property $patched ): array {
		$modifiedLanguageCodes = array_unique(
			array_merge(
				$this->getModifiedLanguageCodes( $original->getLabels(), $patched->getLabels() ),
				$this->getModifiedLanguageCodes( $original->getDescriptions(), $patched->getDescriptions() ),
				$this->getModifiedLanguageCodes( $original->getAliasGroups(), $patched->getAliasGroups()
				)
			)
		);
		sort( $modifiedLanguageCodes );

		return $modifiedLanguageCodes;
	}

}
