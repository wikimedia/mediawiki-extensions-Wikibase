<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class LabelsEditSummaryToFormattableSummaryConverter {

	public function convert( LabelsEditSummary $editSummary ): Summary {
		$modifiedLabelsLanguages = $this->getModifiedLanguageCodes( $editSummary->getOriginalLabels(), $editSummary->getModifiedLabels() );
		$languagesCount = count( $modifiedLabelsLanguages );

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
				[ implode( ', ', $modifiedLabelsLanguages ) ]
			);
		}

		$summary->setUserSummary( $editSummary->getUserComment() );
		return $summary;
	}

	private function getModifiedLanguageCodes( TermList $original, TermList $modified ): array {
		$modifiedLabelsLanguages = [];

		// handle additions and label text changes
		foreach ( $modified as $label ) {
			if ( !$original->hasTermForLanguage( $label->getLanguageCode() ) ) {
				$modifiedLabelsLanguages[] = $label->getLanguageCode();
			} elseif ( $original->getByLanguage( $label->getLanguageCode() )->getText() != $label->getText() ) {
				$modifiedLabelsLanguages[] = $label->getLanguageCode();
			}
		}

		// handle label deletions
		foreach ( $original as $label ) {
			if ( !$modified->hasTermForLanguage( $label->getLanguageCode() ) ) {
				$modifiedLabelsLanguages[] = $label->getLanguageCode();
			}
		}

		return $modifiedLabelsLanguages;
	}

}
