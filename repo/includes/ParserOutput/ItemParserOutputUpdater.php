<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 */
class ItemParserOutputUpdater implements EntityParserOutputUpdater {

	private StatementDataUpdater $statementDataUpdater;

	private bool $isMobileView;

	private bool $tmpMobileEditingUI;

	public function __construct(
		StatementDataUpdater $statementDataUpdater,
		bool $isMobileView,
		bool $tmpMobileEditingUI
	) {
		$this->statementDataUpdater = $statementDataUpdater;
		$this->isMobileView = $isMobileView;
		$this->tmpMobileEditingUI = $tmpMobileEditingUI;
	}

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			$this->updateParserOutputForItem( $parserOutput, $entity );
		}
	}

	public function updateParserOutputForItem( ParserOutput $parserOutput, Item $item ) {
		foreach ( $item->getStatements() as $statement ) {
			$this->statementDataUpdater->processStatement( $statement );
		}

		$this->statementDataUpdater->updateParserOutput( $parserOutput );

		if ( $this->isMobileView && $this->tmpMobileEditingUI ) {
			$parserOutput->addModules( [
				'wikibase.wbui2025.entityViewInit',
			] );
			$parserOutput->addModuleStyles( [
				'wikibase.wbui2025.entityView.styles',
			] );
		}
	}

}
