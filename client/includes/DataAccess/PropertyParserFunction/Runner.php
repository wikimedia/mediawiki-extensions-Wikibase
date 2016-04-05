<?php

namespace Wikibase\Client\DataAccess\PropertyParserFunction;

use Parser;
use PPFrame;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Runner for the {{#property}} parser function.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class Runner {

	/**
	 * @var StatementGroupRendererFactory
	 */
	private $rendererFactory;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var RestrictedEntityLookup
	 */
	private $restrictedEntityLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var bool
	 */
	private $allowArbitraryDataAccess;

	/**
	 * @param StatementGroupRendererFactory $rendererFactory
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityIdParser $entityIdParser
	 * @param RestrictedEntityLookup $restrictedEntityLookup
	 * @param string $siteId
	 * @param bool $allowArbitraryDataAccess
	 */
	public function __construct(
		StatementGroupRendererFactory $rendererFactory,
		SiteLinkLookup $siteLinkLookup,
		EntityIdParser $entityIdParser,
		RestrictedEntityLookup $restrictedEntityLookup,
		$siteId,
		$allowArbitraryDataAccess
	) {
		$this->rendererFactory = $rendererFactory;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityIdParser = $entityIdParser;
		$this->restrictedEntityLookup = $restrictedEntityLookup;
		$this->siteId = $siteId;
		$this->allowArbitraryDataAccess = $allowArbitraryDataAccess;
	}

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return array Wikitext
	 */
	public function runPropertyParserFunction( Parser $parser, PPFrame $frame, array $args ) {
		$propertyLabelOrId = $frame->expand( $args[0] );
		unset( $args[0] );

		// Create a child frame, so that we can access arguments by name.
		$childFrame = $frame->newChild( $args, $parser->getTitle() );
		$entityId = $this->getEntityIdForStatementListProvider( $parser, $childFrame, $args );

		if ( $entityId === null ) {
			return $this->buildResult( '' );
		}

		$renderer = $this->rendererFactory->newRendererFromParser( $parser );
		$rendered = $renderer->render( $entityId, $propertyLabelOrId );
		$result = $this->buildResult( $rendered );

		// Track usage of "other" (that is, not label/title/sitelinks) data from the item.
		$usageAcc = new ParserOutputUsageAccumulator( $parser->getOutput() );
		$usageAcc->addOtherUsage( $entityId );

		return $result;
	}

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return EntityId|null
	 */
	private function getEntityIdForStatementListProvider( Parser $parser, PPFrame $frame, array $args ) {
		$from = $frame->getArgument( 'from' );
		if ( $from && $this->allowArbitraryDataAccess ) {
			$entityId = $this->getEntityIdFromString( $parser, $from );
		} else {
			$title = $parser->getTitle();
			$entityId = $this->siteLinkLookup->getItemIdForLink( $this->siteId, $title->getPrefixedText() );
		}

		return $entityId;
	}

	/**
	 * Gets the entity and increments the expensive parser function count.
	 *
	 * @param Parser $parser
	 * @param string $entityIdString
	 *
	 * @return EntityId|null
	 */
	private function getEntityIdFromString( Parser $parser, $entityIdString ) {
		try {
			$entityId = $this->entityIdParser->parse( $entityIdString );
		} catch ( EntityIdParsingException $ex ) {
			// Just ignore this
			return null;
		}

		// Getting a foreign item is expensive (unless we already loaded it and it's cached)
		if ( !$this->restrictedEntityLookup->entityHasBeenAccessed( $entityId ) ) {
			$parser->incrementExpensiveFunctionCount();
		}

		return $entityId;
	}

	/**
	 * @param string $rendered
	 *
	 * @return array
	 */
	private function buildResult( $rendered ) {
		$result = array(
			$rendered,
			'noparse' => false, // parse wikitext
			'nowiki' => false,  // formatters take care of escaping as needed
		);

		return $result;
	}

	/**
	 * @since 0.4
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return array
	 */
	public static function render( Parser $parser, PPFrame $frame, array $args ) {
		$runner = WikibaseClient::getDefaultInstance()->getPropertyParserFunctionRunner();
		return $runner->runPropertyParserFunction( $parser, $frame, $args );
	}

}
