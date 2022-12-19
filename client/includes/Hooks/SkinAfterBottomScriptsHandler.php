<?php

namespace Wikibase\Client\Hooks;

use ExtensionRegistry;
use File;
use Html;
use MediaWiki\Revision\RevisionLookup;
use PageImages\PageImages;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;

/**
 * @license GPL-2.0-or-later
 */
class SkinAfterBottomScriptsHandler {
	/** @var string */
	private $langCode;
	/** @var RepoLinker */
	private $repoLinker;
	/** @var TermLookup */
	private $termLookup;
	/** @var RevisionLookup */
	private $revisionLookup;

	public function __construct(
		string $langCode,
		RepoLinker $repoLinker,
		TermLookup $termLookup,
		RevisionLookup $revisionLookup
	) {
		$this->langCode = $langCode;
		$this->repoLinker = $repoLinker;
		$this->termLookup = $termLookup;
		$this->revisionLookup = $revisionLookup;
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
		$revisionTimestamp,
		EntityId $entityId
	) {
		$entityConceptUri = $this->repoLinker->getEntityConceptUri( $entityId );
		$imageFile = $this->queryPageImage( $title );
		$description = $this->getDescription( $entityId );
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
		$revisionTimestamp,
		$entityConceptUri,
		File $imageFile = null,
		$description = null
		) {
		$revisionRecord = $this->revisionLookup->getFirstRevision( $title );
		$schemaTimestamp = $revisionRecord ? $revisionRecord->getTimestamp() : null;
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'name' => $title->getText(),
			'url' => $title->getFullURL( '', false, PROTO_CANONICAL ),
			'sameAs' => $entityConceptUri,
			'mainEntity' => $entityConceptUri,
			'author' => [
				'@type' => 'Organization',
				'name' => wfMessage( 'wikibase-page-schema-author-name' )->text(),
			],
			'publisher' => [
				'@type' => 'Organization',
				'name' => wfMessage( 'wikibase-page-schema-publisher-name' )->text(),
				'logo' => [
					'@type' => 'ImageObject',
					'url' => wfMessage( 'wikibase-page-schema-publisher-logo-url' )->text(),
				],
			],
			'datePublished' => wfTimestamp( TS_ISO_8601, $schemaTimestamp ),
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

		return PageImages::getPageImage( $title ) ?: null;
	}

	private function getDescription( EntityId $entityId ): string {
		try {
			$description = $this->termLookup->getDescription( $entityId, $this->langCode );
		} catch ( TermLookupException $exception ) {
			return '';
		}

		return $description ?: '';
	}

}
