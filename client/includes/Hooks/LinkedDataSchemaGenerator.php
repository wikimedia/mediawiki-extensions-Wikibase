<?php

namespace Wikibase\Client\Hooks;

use File;
use MediaWiki\Html\Html;
use MediaWiki\Language\Language;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Title\Title;
use PageImages\PageImages;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;

/**
 * @license GPL-2.0-or-later
 */
class LinkedDataSchemaGenerator implements OutputPageParserOutputHook {
	/** @var string */
	private $langCode;
	/** @var RepoLinker */
	private $repoLinker;
	/** @var TermLookup */
	private $termLookup;

	public function __construct(
		Language $language,
		RepoLinker $repoLinker,
		TermLookup $termLookup
	) {
		$this->langCode = $language->getCode();
		$this->repoLinker = $repoLinker;
		$this->termLookup = $termLookup;
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
		?string $revisionTimestamp,
		?string $firstRevisionTimestamp,
		EntityId $entityId
	): string {
		$entityConceptUri = $this->repoLinker->getEntityConceptUri( $entityId );
		$imageFile = $this->queryPageImage( $title );
		$description = $this->getDescription( $entityId );
		$schema = $this->createSchema(
			$title, $revisionTimestamp, $firstRevisionTimestamp, $entityConceptUri, $imageFile, $description
		);

		$html = Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );
		return $html;
	}

	public function createSchema(
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

	private function getDescription( EntityId $entityId ): string {
		try {
			$description = $this->termLookup->getDescription( $entityId, $this->langCode );
		} catch ( TermLookupException $exception ) {
			return '';
		}

		return $description ?: '';
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
	}
}
