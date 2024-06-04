<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
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

	public function newSummaryForItemCreate( ItemEditSummary $editSummary ): Summary {
		$summary = new Summary( 'wbeditentity', 'create-item' );
		$summary->setUserSummary( $editSummary->getUserComment() );
		return $summary;
	}

	public function newSummaryForItemPatch( ItemEditSummary $editSummary ): Summary {
		$originalItem = $editSummary->getOriginalItem();
		$patchedItem = $editSummary->getPatchedItem();

		$hasStatementsChanged = !$patchedItem->getStatements()->equals( $originalItem->getStatements() );
		$hasSitelinksChanged = !$patchedItem->getSiteLinkList()->equals( $originalItem->getSiteLinkList() );

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
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
