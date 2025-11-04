<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\Domains\Crud\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelsEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class TermsEditSummaryToFormattableSummaryConverter {
	use ModifiedLanguageCodes;

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

		if ( $languagesCount >= EditSummaryHelper::SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES ) {
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

}
