<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\ValuesFinder;

/**
 * Creates parser output for a statement list.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementsParserOutputGenerator {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	public function __construct( EntityTitleLookup $entityTitleLookup, PropertyDataTypeLookup $dataTypeLookup ) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * Assigns information about the given list of snaks to the parser output.
	 *
	 * @since 0.5
	 *
	 * @param ParserOutput $pout
	 * @param StatementList $statements
	 */
	public function assignToParserOutput( ParserOutput $pout, StatementList $statements ) {
		$pout->setProperty( 'wb-statements', $statements->count() );
		$this->assignSnaks( $pout, $statements->getAllSnaks() );
	}

	private function assignSnaks( ParserOutput $pout, array $snaks ) {
		// treat referenced entities as page links ------
		$entitiesFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $entitiesFinder->findSnakLinks( $snaks );

		foreach ( $usedEntityIds as $entityId ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		$valuesFinder = new ValuesFinder( $this->dataTypeLookup );

		// treat URL values as external links ------
		$usedUrls = $valuesFinder->findFromSnaks( $snaks, 'url' );

		foreach ( $usedUrls as $url ) {
			$value = $url->getValue();
			if ( is_string( $value ) ) {
				$pout->addExternalLink( $value );
			}
		}

		// treat CommonsMedia values as file transclusions ------
		$usedImages = $valuesFinder->findFromSnaks( $snaks, 'commonsMedia' );

		foreach ( $usedImages as $image ) {
			$value = $image->getValue();
			if ( is_string( $value ) ) {
				$pout->addImage( str_replace( ' ', '_', $value ) );
			}
		}
	}

}
