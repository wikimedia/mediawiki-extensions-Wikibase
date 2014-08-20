<?php

namespace Wikibase;

use ParserOutput;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * Factory to render an entity to the parser output.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutput extends ParserOutput {

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	private $configBuilder;

	/**
	 * @var SerializationOptions
	 */
	private $options;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	public function __construct(
		ParserOutputJsConfigBuilder $configBuilder,
		SerializationOptions $options,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $dataTypeLookup
	) {
		parent::__construct();
		$this->configBuilder = $configBuilder;
		$this->options = $options;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * @since 0.5
	 *
	 * @param Entity $entity
	 */
	public function addEntityConfigVars( Entity $entity ) {
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$configVars = $this->configBuilder->build( $entity, $this->options, $isExperimental );
		$this->addJsConfigVars( $configVars );
	}

	/**
	 * @since 0.5
	 *
	 * @param Snak[] $snaks
	 */
	public function addSnakLinks( array $snaks ) {
		// treat referenced entities as page links
		$entitiesFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $entitiesFinder->findSnakLinks( $snaks );

		foreach ( $usedEntityIds as $entityId ) {
			$this->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		// treat URL values as external links
		$urlFinder = new ReferencedUrlFinder( $this->dataTypeLookup );
		$usedUrls = $urlFinder->findSnakLinks( $snaks );

		foreach ( $usedUrls as $url ) {
			$this->addExternalLink( $url );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param SiteLinkList $siteLinkList
	 */
	public function addSiteLinkList( SiteLinkList $siteLinkList ) {
		foreach ( $siteLinkList as $siteLink ) {
			// @todo record sitelinks as interwikilinks
			$this->addBadges( $siteLink );
		}
	}

	private function addBadges( SiteLink $siteLink ) {
		// treat badges as page links
		foreach ( $siteLink->getBadges() as $badge ) {
			$this->addLink( $this->entityTitleLookup->getTitleForId( $badge ) );
		}
	}

}
