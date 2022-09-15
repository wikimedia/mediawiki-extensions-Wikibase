<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;

/**
 * @license GPL-2.0-or-later
 */
class FormatableSummaryConverter {

	public function convert( EditSummary $summary ): FormatableSummary {
		if ( $summary instanceof StatementEditSummary ) {
			switch ( $summary->getEditAction() ) {
				case EditSummary::ADD_ACTION:
					return $this->newFormatableSummaryForStatementEdit(
						$summary,
						'wbsetclaim',
						'create'
					);
				case EditSummary::REMOVE_ACTION:
					return $this->newFormatableSummaryForStatementEdit(
						$summary,
						'wbremoveclaims',
						'remove'
					);
				case EditSummary::REPLACE_ACTION:
				case EditSummary::PATCH_ACTION:
					return $this->newFormatableSummaryForStatementEdit(
						$summary,
						'wbsetclaim',
						'update'
					);
			}
		}

		throw new \LogicException( "Unknown summary type '{$summary->getEditAction()}' " . get_class( $summary ) );
	}

	private function newFormatableSummaryForStatementEdit(
		StatementEditSummary $editSummary,
		string $moduleName,
		string $actionName
	): Summary {
		$statement = $editSummary->getStatement();
		$formatableSummary = new Summary( $moduleName, $actionName );

		$formatableSummary->setUserSummary( $editSummary->getUserComment() );
		$formatableSummary->addAutoSummaryArgs( [
			[ $statement->getPropertyId()->getSerialization() => $statement->getMainSnak() ]
		] );

		return $formatableSummary;
	}

}
