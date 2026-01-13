<?php

declare( strict_types=1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\FileRepo\File\File;
use MediaWiki\Hook\SkinAfterBottomScriptsHook;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use PageImages\PageImages;
use Skin;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\SettingsArray;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class LinkedDataSchemaGenerator implements OutputPageParserOutputHook, SkinAfterBottomScriptsHook {

	private RevisionLookup $revisionLookup;
	private EntityIdParser $entityIdParser;
	private RepoLinker $repoLinker;

	/** @var int[] */
	private array $pageSchemaNamespaces;
	private ?PageImages $pageImages;

	public function __construct(
		RevisionLookup $revisionLookup,
		EntityIdParser $entityIdParser,
		RepoLinker $repoLinker,
		array $pageSchemaNamespaces,
		?PageImages $pageImages
	) {
		$this->revisionLookup = $revisionLookup;
		$this->entityIdParser = $entityIdParser;
		$this->repoLinker = $repoLinker;
		$this->pageSchemaNamespaces = $pageSchemaNamespaces;
		$this->pageImages = $pageImages;
	}

	public static function factory(
		RevisionLookup $revisionLookup,
		EntityIdParser $entityIdParser,
		RepoLinker $repoLinker,
		SettingsArray $settings,
		?PageImages $pageImages
	): self {
		return new self(
			$revisionLookup,
			$entityIdParser,
			$repoLinker,
			$settings->getSetting( 'pageSchemaNamespaces' ),
			$pageImages
		);
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

		return Html::rawElement( 'script', [ 'type' => 'application/ld+json' ], json_encode( $schema ) );
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
			$schema['datePublished'] = ConvertibleTimestamp::convert( TS::ISO_8601, $firstRevisionTimestamp );
		}

		if ( $revisionTimestamp ) {
			$schema['dateModified'] = ConvertibleTimestamp::convert( TS::ISO_8601, $revisionTimestamp );
		}

		if ( $imageFile ) {
			$schema['image'] = MediaWikiServices::getInstance()->getUrlUtils()
				->expand( $imageFile->getUrl(), PROTO_CANONICAL );
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
		if ( !$this->pageImages ) {
			return null;
		}

		return $this->pageImages->getImage( $title );
	}

	/**
	 * Injects a Wikidata inline JSON-LD script schema for search engine optimization.
	 *
	 * @param Skin $skin
	 * @param string &$html
	 *
	 * @return bool Always true.
	 */
	public function onSkinAfterBottomScripts( $skin, &$html ): bool {
		$outputPage = $skin->getOutput();
		$entityId = $this->parseEntityId( $outputPage->getProperty( 'wikibase_item' ) );
		$title = $outputPage->getTitle();
		if (
			!$entityId ||
			!$title ||
			!in_array( $title->getNamespace(), $this->pageSchemaNamespaces ) ||
			!$title->exists()
		) {
			return true;
		}

		$revisionTimestamp = $outputPage->getRevisionTimestamp();
		$firstRevisionTimestamp = $outputPage->getProperty( 'first_revision_timestamp' );
		$description = $outputPage->getProperty( 'wikibase_item_description' );
		$html .= $this->createSchemaElement(
			$title,
			$revisionTimestamp,
			$firstRevisionTimestamp,
			$entityId,
			$description
		);

		return true;
	}

	private function parseEntityId( ?string $prefixedId ): ?EntityId {
		if ( !$prefixedId ) {
			return null;
		}

		try {
			return $this->entityIdParser->parse( $prefixedId );
		} catch ( EntityIdParsingException ) {
			return null;
		}
	}

	/**
	 * @inheritDoc
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
