<?php

namespace Wikibase;

use SiteList;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\RDF\RdfWriter;

/**
 * RDF mapping for entity SiteLinks.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SiteLinksRdfBuilder {

	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY = 'entity'; // concept uris
	const NS_SCHEMA_ORG = 'schema'; // schema.org vocabulary

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var string[]|null a list of desired sites, or null for all sites.
	 */
	private $sites;

	/**
	 * @var SiteList
	 */
	private $siteLookup;

	/**
	 * @param RdfWriter $writer
	 * @param SiteList $siteLookup
	 * @param string[]|null $sites
	 */
	public function __construct( RdfWriter $writer, SiteList $siteLookup, array $sites = null ) {
		$this->writer = $writer;
		$this->siteLookup = $siteLookup;
		$this->sites = $sites === null ? null : array_flip( $sites );
	}

	/**
	 * Returns a local name for the given entity using the given prefix.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getEntityLName( EntityId $entityId ) {
		return ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Site filter
	 *
	 * @param $lang
	 *
	 * @return bool
	 */
	private function isSiteIncluded( $lang ) {
		return $this->sites === null || isset( $this->sites[$lang] );
	}
	
	/**
	 * Adds the site links of the given item to the RDF graph.
	 *
	 * @param Item $item
	 */
	public function addSiteLinks( Item $item ) {
		$entityLName = $this->getEntityLName( $item->getId() );

		/** @var SiteLink $siteLink */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			if ( !$this->isSiteIncluded( $siteLink->getSiteId() ) ) {
				continue;
			}

			$site = $this->siteLookup->getSite( $siteLink->getSiteId() );

			// XXX: ideally, we'd use https if the target site supports it.
			$baseUrl = str_replace( '$1', rawurlencode($siteLink->getPageName()), $site->getLinkPath() );
			// $site->getPageUrl( $siteLink->getPageName() );
			if( !parse_url( $baseUrl, PHP_URL_SCHEME ) ) {
				$url = "http:".$baseUrl;
			} else {
				$url = $baseUrl;
			}

			$this->writer->about( $url )
				->a( self::NS_SCHEMA_ORG, 'Article' )
				->say( self::NS_SCHEMA_ORG, 'about' )->is( self::NS_ENTITY, $entityLName )
				->say( self::NS_SCHEMA_ORG, 'inLanguage' )->text( $site->getLanguageCode() );

			foreach ( $siteLink->getBadges() as $badge ) {
				$this->writer
					->say( self::NS_ONTOLOGY, 'badge' )
						->is( self::NS_ENTITY, $this->getEntityLName( $badge ) );
			}
		}

	}

}
