<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use LogicException;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class EditSummaryFormatter {

	private SummaryFormatter $summaryFormatter;

	public function __construct( SummaryFormatter $summaryFormatter ) {
		$this->summaryFormatter = $summaryFormatter;
	}

	public function format( EditSummary $summary ): string {
		return $this->summaryFormatter->formatSummary(
			$this->convertToFormattableSummary( $summary )
		);
	}

	private function convertToFormattableSummary( EditSummary $summary ): FormatableSummary {
		if ( $summary instanceof StatementEditSummary ) {
			switch ( $summary->getEditAction() ) {
				case EditSummary::ADD_ACTION:
					$formatableSummary = $this->newFormatableSummaryForStatementEdit(
						$summary,
						'wbsetclaim',
						'create'
					);
					// the "1" signifies the number of edited statements in wbsetclaim-related messages
					$formatableSummary->addAutoCommentArgs( 1 );

					return $formatableSummary;
				case EditSummary::REMOVE_ACTION:
					return $this->newFormatableSummaryForStatementEdit(
						$summary,
						'wbremoveclaims',
						'remove'
					);
				case EditSummary::REPLACE_ACTION:
				case EditSummary::PATCH_ACTION:
					$formatableSummary = $this->newFormatableSummaryForStatementEdit(
						$summary,
						'wbsetclaim',
						'update'
					);
					// the "1" signifies the number of edited statements in wbsetclaim-related messages
					$formatableSummary->addAutoCommentArgs( 1 );

					return $formatableSummary;
			}
		}

		throw new LogicException( "Unknown summary type '{$summary->getEditAction()}' " . get_class( $summary ) );
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
			[ $statement->getPropertyId()->getSerialization() => $statement->getMainSnak() ],
		] );

		return $formatableSummary;
	}

}
