<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * @license GPL-2.0-or-later
 */
class PropertyParserOutputUpdater implements EntityParserOutputUpdater {

	private StatementDataUpdater $statementDataUpdater;

	public function __construct(
		StatementDataUpdater $statementDataUpdater,
	) {
		$this->statementDataUpdater = $statementDataUpdater;
	}

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity ) {
		if ( $entity instanceof Property ) {
			$this->updateParserOutputForProperty( $parserOutput, $entity );
		}
	}

	public function updateParserOutputForProperty( ParserOutput $parserOutput, Property $property ) {
		foreach ( $property->getStatements() as $statement ) {
			$this->statementDataUpdater->processStatement( $statement );
		}

		$this->statementDataUpdater->updateParserOutput( $parserOutput );
		if ( Wbui2025FeatureFlag::wbui2025EnabledForParserOutput( $parserOutput ) ) {
			$parserOutput->addModules( [
				'wikibase.wbui2025.entityViewInit',
			] );
			$parserOutput->addModuleStyles( [
				'wikibase.wbui2025.entityView.styles',
			] );
		}
	}

}
