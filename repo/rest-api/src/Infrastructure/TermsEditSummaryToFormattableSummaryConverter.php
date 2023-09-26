<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class TermsEditSummaryToFormattableSummaryConverter {

	public function convertLabelsEditSummary( LabelsEditSummary $editSummary ): Summary {
		return $this->convert(
			$editSummary->getOriginalLabels(),
			$editSummary->getModifiedLabels(),
			$editSummary->getUserComment()
		);
	}

	public function convertDescriptionsEditSummary( DescriptionsEditSummary $editSummary ): Summary {
		return $this->convert(
			$editSummary->getOriginalDescriptions(),
			$editSummary->getModifiedDescriptions(),
			$editSummary->getUserComment()
		);
	}

	private function convert( TermList $original, TermList $modified, ?string $userComment ): Summary {
		$modifiedLanguages = $this->getModifiedLanguageCodes( $original, $modified );
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

	private function getModifiedLanguageCodes( TermList $original, TermList $modified ): array {
		$modifiedLanguages = [];

		// handle additions and text changes
		foreach ( $modified as $term ) {
			if ( !$original->hasTermForLanguage( $term->getLanguageCode() ) ) {
				$modifiedLanguages[] = $term->getLanguageCode();
			} elseif ( $original->getByLanguage( $term->getLanguageCode() )->getText() != $term->getText() ) {
				$modifiedLanguages[] = $term->getLanguageCode();
			}
		}

		// handle deletions
		foreach ( $original as $term ) {
			if ( !$modified->hasTermForLanguage( $term->getLanguageCode() ) ) {
				$modifiedLanguages[] = $term->getLanguageCode();
			}
		}

		sort( $modifiedLanguages );

		return $modifiedLanguages;
	}

}
