<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use Parser;
use PPFrame;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Runner for the {{#property}} parser function.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class Runner {

	/**
	 * @var PropertyClaimsRendererFactory
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
	 * @var string
	 */
	private $siteId;

	/**
	 * @var bool
	 */
	private $allowArbitraryDataAccess;

	/**
	 * @param PropertyCLaimsRendererFactory $rendererFactory
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityIdParser $entityIdParser
	 * @param string $siteId
	 * @param bool $allowArbitraryDataAccess
	 */
	public function __construct(
		PropertyClaimsRendererFactory $rendererFactory,
		SiteLinkLookup $siteLinkLookup,
		EntityIdParser $entityIdParser,
		$siteId,
		$allowArbitraryDataAccess
	) {
		$this->rendererFactory = $rendererFactory;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityIdParser = $entityIdParser;
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
			try {
				$entityId = $this->entityIdParser->parse( $from );
				// Accessing a foreign item is expensive.
				// XXX: Only increment once per item? How?
				$parser->incrementExpensiveFunctionCount();
			} catch ( EntityIdParsingException $ex ) {
				// Just ignore this
				return null;
			}
		} else {
			$title = $parser->getTitle();
			$siteLink = new SiteLink( $this->siteId, $title->getFullText() );
			$entityId = $this->siteLinkLookup->getItemIdForSiteLink( $siteLink );
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
