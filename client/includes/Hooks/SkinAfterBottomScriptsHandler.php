<?php

namespace Wikibase\Client\Hooks;

use ExtensionRegistry;
use File;
use Html;
use Title;

use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Term\FingerprintProvider;

/**
 * @license GPL-2.0-or-later
 */
class SkinAfterBottomScriptsHandler {
	/** @var WikibaseClient */
	private $client;
	/** @var RepoLinker */
	private $repoLinker;

	/**
	 * @param WikibaseClient $client
	 */
	public function __construct( WikibaseClient $client, RepoLinker $repoLinker ) {
		$this->client = $client;
		$this->repoLinker = $repoLinker;
	}

	/**
	 * @param Title $title
	 * @param string|null $revisionTimestamp
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function createSchemaElement(
		Title $title,
		$revisionTimestamp = null,
		EntityId $entityId
	) {
		$entityConceptUri = $this->repoLinker->getEntityConceptUri( $entityId );
		$imageFile = $this->queryPageImage( $title );
		$entityDocument = $this->lookupEntityDocument( $entityId );
		$description = $this->getDescription( $entityDocument );
		$schema = $this->createSchema(
			$title, $revisionTimestamp, $entityConceptUri, $imageFile, $description
		);

		$html = Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );
		return $html;
	}

	/**
	 * @param Title $title
	 * @param string|null $revisionTimestamp
	 * @param string $entityConceptUri
	 * @param File|null $imageFile
	 * @param string|null $description
	 *
	 * @return array
	 */
	public function createSchema(
		Title $title,
		$revisionTimestamp = null,
		$entityConceptUri,
		File $imageFile = null,
		$description = null
		) {
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'name' => $title->getText(),
			'url' => $title->getFullURL( '', false, PROTO_CANONICAL ),
			'sameAs' => $entityConceptUri,
			'mainEntity' => $entityConceptUri,
			'author' => [
				'@type' => 'Organization',
				'name' => wfMessage( 'wikibase-page-schema-author-name' )->text()
			],
			'publisher' => [
				'@type' => 'Organization',
				'name' => wfMessage( 'wikibase-page-schema-publisher-name' )->text(),
				'logo' => [
					'@type' => 'ImageObject',
					'url' => wfMessage( 'wikibase-page-schema-publisher-logo-url' )->text()
				]
			],
			'datePublished' => wfTimestamp( TS_ISO_8601, $title->getEarliestRevTime() )
		];

		if ( $revisionTimestamp ) {
			$schema['dateModified'] = wfTimestamp( TS_ISO_8601, $revisionTimestamp );
		}

		if ( $imageFile ) {
			$schema['image'] = wfExpandUrl( $imageFile->getUrl(), PROTO_CANONICAL );
		}

		if ( $description ) {
			$schema['headline'] = $description;
		}

		return $schema;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument|null
	 */
	private function lookupEntityDocument( EntityId $entityId ) {
		try {
			$entityLookup = $this->client->getStore()->getEntityLookup();
			return $entityLookup->getEntity( $entityId );
		} catch ( EntityLookupException $ex ) {
			return null;
		}
	}

	/**
	 * If available, query the canonical page image injected into the og:image meta tag. It's
	 * important that the schema image match the page meta image since the schema describes the page.
	 * @param Title $title
	 *
	 * @return File|null
	 */
	private function queryPageImage( Title $title ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'PageImages' ) ) {
			return null;
		}

		/** @suppress PhanUndeclaredStaticMethod Static call to undeclared method */
		return \PageImages::getPageImage( $title ) ?: null;
	}

	/**
	 * @param EntityDocument|null $entity
	 *
	 * @return string
	 */
	private function getDescription( EntityDocument $entity = null ) {
		if ( !$entity || !( $entity instanceof FingerprintProvider ) ) {
			return '';
		}

		global $wgContLang;
		$langCode = $wgContLang->getCode();
		$fingerprint = $entity->getFingerprint();
		if ( !$fingerprint->hasDescription( $langCode ) ) {
			return '';
		}
		return $fingerprint->getDescription( $langCode )->getText();
	}

}
