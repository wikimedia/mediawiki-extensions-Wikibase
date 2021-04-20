<?php

namespace Wikibase\Client\DataAccess\ParserFunctions;

use Parser;
use PPFrame;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Runner for the {{#property|…}} and {{#statements|…}} parser functions.
 *
 * @license GPL-2.0-or-later
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
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 *
	 * @return array Wikitext in element 0, flags in named elements
	 */
	public function runPropertyParserFunction(
		Parser $parser,
		PPFrame $frame,
		array $args,
		$type = DataAccessSnakFormatterFactory::TYPE_ESCAPED_PLAINTEXT
	) {
		$propertyLabelOrId = $frame->expand( $args[0] );
		unset( $args[0] );

		// Create a child frame, so that we can access arguments by name.
		$childFrame = $frame->newChild( $args, $parser->getTitle() );
		$entityId = $this->getEntityIdForStatementListProvider( $parser, $childFrame );

		if ( $entityId === null ) {
			return $this->buildResult( '' );
		}

		$renderer = $this->rendererFactory->newRendererFromParser( $parser, $type );
		$rendered = $renderer->render( $entityId, $propertyLabelOrId );
		return $this->buildResult( $rendered );
	}

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return EntityId|null
	 */
	private function getEntityIdForStatementListProvider( Parser $parser, PPFrame $frame ) {
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
		if (
			!$this->restrictedEntityLookup->entityHasBeenAccessed( $entityId ) &&
			!$parser->incrementExpensiveFunctionCount()
		) {
			// Just do nothing, that's what parser functions do when the limit has been
			// exceeded.
			return null;
		}

		return $entityId;
	}

	/**
	 * @param string $rendered Wikitext
	 *
	 * @return array Wikitext in element 0, flags in named elements
	 */
	private function buildResult( $rendered ) {
		return [
			$rendered,
			'noparse' => false, // parse wikitext
			'nowiki' => false,  // formatters take care of escaping as needed
		];
	}

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return array Wikitext in element 0, flags in named elements
	 */
	public static function renderEscapedPlainText( Parser $parser, PPFrame $frame, array $args ) {
		$runner = WikibaseClient::getPropertyParserFunctionRunner();
		return $runner->runPropertyParserFunction( $parser, $frame, $args );
	}

	/**
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 *
	 * @return array Wikitext in element 0, flags in named elements
	 */
	public static function renderRichWikitext( Parser $parser, PPFrame $frame, array $args ) {
		$runner = WikibaseClient::getPropertyParserFunctionRunner();
		return $runner->runPropertyParserFunction( $parser, $frame, $args, DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT );
	}

}
