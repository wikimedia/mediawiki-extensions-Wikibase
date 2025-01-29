<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchItemEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchPropertyEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class WholeEntityEditSummaryToFormattableSummaryConverter {

	use ModifiedLanguageCodes;

	public function newSummaryForPropertyPatch( PatchPropertyEditSummary $editSummary ): Summary {
		$originalProperty = $editSummary->getOriginalProperty();
		$patchedProperty = $editSummary->getPatchedProperty();
		$hasStatementsChanged = !$patchedProperty->getStatements()->equals( $originalProperty->getStatements() );

		$modifiedLanguages = $this->modifiedLanguageCodes( $originalProperty, $patchedProperty );

		$summary = $this->setSummary( $modifiedLanguages, $hasStatementsChanged );
		$summary->setUserSummary( $editSummary->getUserComment() );

		return $summary;
	}

	public function newSummaryForItemPatch( PatchItemEditSummary $editSummary ): Summary {
		$originalItem = $editSummary->getOriginalItem();
		$patchedItem = $editSummary->getPatchedItem();

		$hasStatementsChanged = !$patchedItem->getStatements()->equals( $originalItem->getStatements() );
		$hasSitelinksChanged = !$patchedItem->getSiteLinkList()->equals( $originalItem->getSiteLinkList() );

		$modifiedLanguages = $this->modifiedLanguageCodes( $originalItem, $patchedItem );

		$summary = $this->setSummary( $modifiedLanguages, $hasStatementsChanged || $hasSitelinksChanged );
		$summary->setUserSummary( $editSummary->getUserComment() );

		return $summary;
	}

	private function setSummary( array $modifiedLanguages, bool $hasStatementsOrSitelinksChanged ): Summary {
		$languagesCount = count( $modifiedLanguages );

		if ( $languagesCount >= ChangedLanguagesCounter::SHORTENED_SUMMARY_MAX_EDIT ) {
			$actionName = $hasStatementsOrSitelinksChanged ? 'update-languages-and-other' : 'update-languages';
			$commentArgs = [ (string)$languagesCount ];
		} elseif ( $languagesCount > 0 ) {
			$actionName = $hasStatementsOrSitelinksChanged ? 'update-languages-and-other-short' : 'update-languages-short';
			$commentArgs = [ implode( ', ', $modifiedLanguages ) ];
		} else {
			$actionName = 'update';
			$commentArgs = [];
		}

		return new Summary( 'wbeditentity', $actionName, null, $commentArgs );
	}

	/**
	 * @param Item|Property $originalEntity
	 * @param Item|Property $patchedEntity
	 *
	 * @return array
	 */
	private function modifiedLanguageCodes( $originalEntity, $patchedEntity ): array {
		$modifiedLanguageCodes = array_unique(
			array_merge(
				$this->getModifiedLanguageCodes( $originalEntity->getLabels(), $patchedEntity->getLabels() ),
				$this->getModifiedLanguageCodes( $originalEntity->getDescriptions(), $patchedEntity->getDescriptions() ),
				$this->getModifiedLanguageCodes( $originalEntity->getAliasGroups(), $patchedEntity->getAliasGroups() )
			)
		);
		sort( $modifiedLanguageCodes );

		return $modifiedLanguageCodes;
	}

}
