<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use LogicException;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Summary;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
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

	private function convertToFormattableSummary( EditSummary $editSummary ): FormatableSummary {
		if ( $editSummary instanceof LabelsEditSummary ) {
			return new Summary();
		}
		if ( $editSummary instanceof LabelEditSummary ) {
			switch ( $editSummary->getEditAction() ) {
				case EditSummary::ADD_ACTION:
					return $this->newSummaryForLabelEdit( $editSummary, 'add' );
				case EditSummary::REPLACE_ACTION:
					return $this->newSummaryForLabelEdit( $editSummary, 'set' );
			}
		} elseif ( $editSummary instanceof DescriptionEditSummary ) {
			switch ( $editSummary->getEditAction() ) {
				case EditSummary::ADD_ACTION:
					return $this->newSummaryForDescriptionEdit( $editSummary, 'add' );
				case EditSummary::REPLACE_ACTION:
					return $this->newSummaryForDescriptionEdit( $editSummary, 'set' );
			}
		} elseif ( $editSummary instanceof StatementEditSummary ) {
			switch ( $editSummary->getEditAction() ) {
				case EditSummary::ADD_ACTION:
					return $this->newSummaryForStatementEdit( $editSummary, 'wbsetclaim', 'create', 1 );
				case EditSummary::REMOVE_ACTION:
					return $this->newSummaryForStatementEdit( $editSummary, 'wbremoveclaims', 'remove' );
				case EditSummary::REPLACE_ACTION:
				case EditSummary::PATCH_ACTION:
					return $this->newSummaryForStatementEdit( $editSummary, 'wbsetclaim', 'update', 1 );
			}
		}

		throw new LogicException( "Unknown summary type '{$editSummary->getEditAction()}' " . get_class( $editSummary ) );
	}

	private function newSummaryForLabelEdit( LabelEditSummary $editSummary, string $actionName ): Summary {
		$summary = new Summary( 'wbsetlabel', $actionName );
		$summary->setLanguage( $editSummary->getLabel()->getLanguageCode() );
		$summary->addAutoSummaryArgs( [ $editSummary->getLabel()->getText() ] );
		$summary->setUserSummary( $editSummary->getUserComment() );

		return $summary;
	}

	private function newSummaryForDescriptionEdit( DescriptionEditSummary $editSummary, string $actionName ): Summary {
		$summary = new Summary( 'wbsetdescription', $actionName );
		$summary->setLanguage( $editSummary->getDescription()->getLanguageCode() );
		$summary->addAutoSummaryArgs( [ $editSummary->getDescription()->getText() ] );
		$summary->setUserSummary( $editSummary->getUserComment() );

		return $summary;
	}

	private function newSummaryForStatementEdit(
		StatementEditSummary $editSummary,
		string $moduleName,
		string $actionName,
		int $autoCommentArgs = null
	): Summary {
		$statement = $editSummary->getStatement();

		$summary = new Summary( $moduleName, $actionName );
		$summary->setUserSummary( $editSummary->getUserComment() );
		$summary->addAutoSummaryArgs( [
			[ $statement->getPropertyId()->getSerialization() => $statement->getMainSnak() ],
		] );
		if ( $autoCommentArgs !== null ) {
			// the number of edited statements in wbsetclaim-related messages
			$summary->addAutoCommentArgs( $autoCommentArgs );
		}

		return $summary;
	}

}
