<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\RestApi\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class TermsEditSummaryToFormattableSummaryConverter {

	public function convertLabelsEditSummary( LabelsEditSummary $editSummary ): Summary {
		return $this->convert(
			$this->getModifiedLanguageCodes( $editSummary->getOriginalLabels(), $editSummary->getModifiedLabels() ),
			$editSummary->getUserComment()
		);
	}

	public function convertDescriptionsEditSummary( DescriptionsEditSummary $editSummary ): Summary {
		return $this->convert(
			$this->getModifiedLanguageCodes( $editSummary->getOriginalDescriptions(), $editSummary->getModifiedDescriptions() ),
			$editSummary->getUserComment()
		);
	}

	public function convertAliasesEditSummary( AliasesEditSummary $editSummary ): Summary {
		return $this->convert(
			$this->getModifiedLanguageCodes( $editSummary->getOriginalAliases(), $editSummary->getModifiedAliases() ),
			$editSummary->getUserComment()
		);
	}

	private function convert( array $modifiedLanguages, ?string $userComment ): Summary {
		$languagesCount = count( $modifiedLanguages );

		if ( $languagesCount >= ChangedLanguagesCounter::SHORTENED_SUMMARY_MAX_EDIT ) {
			$summary = new Summary(
				'wbeditentity',
				'update-languages',
				null,
				[ (string)$languagesCount ]
			);
		} else {
			$summary = new Summary(
				'wbeditentity',
				'update-languages-short',
				null,
				[ implode( ', ', $modifiedLanguages ) ]
			);
		}

		$summary->setUserSummary( $userComment );
		return $summary;
	}

	/**
	 * @param TermList|AliasGroupList $original
	 * @param TermList|AliasGroupList $modified
	 */
	private function getModifiedLanguageCodes( $original, $modified ): array {
		$original = iterator_to_array( $original );
		$modified = iterator_to_array( $modified );
		$modifiedLanguages = [];

		// handle additions and text changes
		foreach ( $modified as $language => $termOrAliases ) {
			if ( !array_key_exists( $language, $original ) || !$original[$language]->equals( $termOrAliases ) ) {
				$modifiedLanguages[] = $language;
			}
		}

		// handle deletions
		foreach ( $original as $language => $termOrAliases ) {
			if ( !array_key_exists( $language, $modified ) ) {
				$modifiedLanguages[] = $language;
			}
		}

		sort( $modifiedLanguages );

		return $modifiedLanguages;
	}

}
