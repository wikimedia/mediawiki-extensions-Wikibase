<?php

namespace Wikibase\Repo\Rdf;

use SiteList;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for entity SiteLinks.
 *
 * @license GPL-2.0-or-later
 */
class SiteLinksRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var SiteList
	 */
	private $siteLookup;

	/**
	 * @var string[]|null a list of desired sites, or null for all sites.
	 */
	private $sites;

	/**
	 * @var DedupeBag
	 */
	private $dedupeBag;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param SiteList $siteLookup
	 * @param string[]|null $sites
	 */
	public function __construct( RdfVocabulary $vocabulary, RdfWriter $writer, SiteList $siteLookup, array $sites = null ) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->siteLookup = $siteLookup;
		$this->sites = $sites === null ? null : array_flip( $sites );
		$this->dedupeBag = new NullDedupeBag();
	}

	public function setDedupeBag( DedupeBag $dedupeBag ) {
		$this->dedupeBag = $dedupeBag;
	}

	/**
	 * Site filter
	 *
	 * @param string $lang
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
		$id = $item->getId();
		$entityLName = $this->vocabulary->getEntityLName( $id );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $id );

		/** @var SiteLink $siteLink */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			if ( !$this->isSiteIncluded( $siteLink->getSiteId() ) ) {
				continue;
			}

			// FIXME: we should check the site exists using hasGlobalId here before asuming it does
			$site = $this->siteLookup->getSite( $siteLink->getSiteId() );
			if ( !$site ) {
				// Somehow we've got site that we don't know about - skip
				continue;
			}
			$baseUrl = str_replace( '$1', wfUrlencode( str_replace( ' ', '_', $siteLink->getPageName() ) ),
									$site->getLinkPath() );
			// XXX: ideally, we'd use https if the target site supports it.
			if ( !parse_url( $baseUrl, PHP_URL_SCHEME ) ) {
				$url = "http:" . $baseUrl;
			} else {
				$url = $baseUrl;
			}

			$group = $site->getGroup();
			$siteUrl = parse_url( $url, PHP_URL_SCHEME ) . '://' . parse_url( $url, PHP_URL_HOST ) . "/";
			$lang = $this->vocabulary->getCanonicalLanguageCode( $site->getLanguageCode() );

			$this->writer->about( $url )
				->a( RdfVocabulary::NS_SCHEMA_ORG, 'Article' )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'about' )
				->is( $this->vocabulary->entityNamespaceNames[$entityRepoName], $entityLName )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'inLanguage' )->text( $lang )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'isPartOf' )->is( $siteUrl )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )->text( $siteLink->getPageName(), $lang );

			foreach ( $siteLink->getBadges() as $badge ) {
				$badgeRepoName = $this->vocabulary->getEntityRepositoryName( $badge );
				$this->writer
					->say( RdfVocabulary::NS_ONTOLOGY, 'badge' )
					->is(
						$this->vocabulary->entityNamespaceNames[$badgeRepoName],
						$this->vocabulary->getEntityLName( $badge )
					);
			}

			/* Write group of the site only once.
			 * We are using URL as namespace to ensure it is not cut off.
			 * Since we do not have too may distinct sites, memory cost is small.
			 */
			if ( !$this->dedupeBag->alreadySeen( $group, $siteUrl ) ) {
				$this->writer->about( $siteUrl )
					->say( RdfVocabulary::NS_ONTOLOGY, 'wikiGroup' )->text( $group );
			}

		}
	}

	/**
	 * Add the entity's sitelinks to the RDF graph.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			$this->addSiteLinks( $entity );
		}
	}

}
