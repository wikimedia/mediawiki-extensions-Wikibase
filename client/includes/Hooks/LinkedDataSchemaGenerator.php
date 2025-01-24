<?php

namespace Wikibase\Client\Hooks;

use File;
use MediaWiki\Html\Html;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use PageImages\PageImages;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class LinkedDataSchemaGenerator implements OutputPageParserOutputHook {
	/** @var RevisionLookup */
	private $revisionLookup;
	/** @var RepoLinker */
	private $repoLinker;

	public function __construct(
		RevisionLookup $revisionLookup,
		RepoLinker $repoLinker
	) {
		$this->revisionLookup = $revisionLookup;
		$this->repoLinker = $repoLinker;
	}

	/**
	 * @param Title $title
	 * @param string|null $revisionTimestamp
	 * @param string|null $firstRevisionTimestamp
	 * @param EntityId $entityId
	 * @param string|null $description
	 *
	 * @return string
	 */
	public function createSchemaElement(
		Title $title,
		?string $revisionTimestamp,
		?string $firstRevisionTimestamp,
		EntityId $entityId,
		?string $description
	): string {
		if ( !$firstRevisionTimestamp ) {
			// Revision may not be found during page move, in which case we can look up again from revisionLookup
			$revisionRecord = $this->revisionLookup->getFirstRevision( $title );
			$firstRevisionTimestamp = $revisionRecord ? $revisionRecord->getTimestamp() : null;
		}

		$entityConceptUri = $this->repoLinker->getEntityConceptUri( $entityId );
		$imageFile = $this->queryPageImage( $title );
		$schema = $this->createSchema(
			$title, $revisionTimestamp, $firstRevisionTimestamp, $entityConceptUri, $imageFile, $description
		);

		$html = Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );
		return $html;
	}

	private function createSchema(
		Title $title,
		?string $revisionTimestamp,
		?string $firstRevisionTimestamp,
		string $entityConceptUri,
		?File $imageFile,
		?string $description
	): array {
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
		];

		if ( $firstRevisionTimestamp ) {
			$schema['datePublished'] = wfTimestamp( TS_ISO_8601, $firstRevisionTimestamp );
		}

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

	/**
	 * Add output page properties to be consumed in the LinkedDataSchemaGenerator
	 *
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		$firstRevisionTimestamp = $parserOutput->getExtensionData( 'first_revision_timestamp' );
		if ( $firstRevisionTimestamp !== null ) {
			$outputPage->setProperty( 'first_revision_timestamp', $firstRevisionTimestamp );
		}

		$description = $parserOutput->getExtensionData( 'wikibase_item_description' );
		if ( $description !== null ) {
			$outputPage->setProperty( 'wikibase_item_description', $description );
		}
	}
}
