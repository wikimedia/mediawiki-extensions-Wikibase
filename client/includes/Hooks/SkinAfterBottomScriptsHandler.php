<?php

namespace Wikibase\Client\Hooks;

use ExtensionRegistry;
use File;
use HTML;
use Title;

use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\FingerprintProvider;

/**
 * @license GPL-2.0-or-later
 */
class SkinAfterBottomScriptsHandler {
	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	public function __construct( RepoLinker $repoLinker ) {
		$this->repoLinker = $repoLinker;
	}

	/**
	 * @param WikibaseClient $client
	 * @param Title $title
	 * @param string|null $revisionTimestamp
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function createSchemaElement(
		WikibaseClient $client,
		Title $title,
		$revisionTimestamp = null,
		EntityId $entityId
	) {
		$mainEntityURL = $this->repoLinker->getCanonicalEntityUrl( $entityId );
		$imageFile = $this->queryPageImage( $title );
		$entityDocument = $this->lookupEntityDocument( $client, $entityId );
		$description = $this->getDescription( $entityDocument );
		$schema = $this->createSchema(
			$client, $title, $revisionTimestamp, $mainEntityURL, $imageFile, $description
		);

		$html = Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );
		return $html;
	}

	/**
	 * @param WikibaseClient $client
	 * @param Title $title
	 * @param string|null $revisionTimestamp
	 * @param String $mainEntityURL
	 * @param File|null $image
	 * @param String|null $description
	 *
	 * @return array
	 */
	public function createSchema(
		WikibaseClient $client,
		Title $title,
		$revisionTimestamp = null,
		$mainEntityURL,
		File $imageFile = null,
		$description = null
		) {
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'name' => $title->getText(),
			'url' => $title->getFullURL( '', false, PROTO_CANONICAL ),
			'sameAs' => $mainEntityURL,
			'mainEntity' => $mainEntityURL,
			'author' => [
				'@type' => 'Organization',
				'name' => $client->getSettings()->getSetting( 'pageSchemaAuthor' )
			],
			'publisher' => [
				'@type' => 'Organization',
				'name' => $client->getSettings()->getSetting( 'pageSchemaPublisher' ),
				'logo' => [
					'@type' => 'ImageObject',
					'url' => $this->getLogoURL()
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
	 * @return string
	 */
	private function getLogoURL() {
		return $this->repoLinker->getBaseUrl() . '/extensions/Wikibase/client/assets/wikimedia.png';
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument|null
	 */
	private function lookupEntityDocument( WikibaseClient $client, EntityId $entityId ) {
		try {
			$entityLookup = $client->getStore()->getEntityLookup();
			return $entityLookup->getEntity( $entityId );
		} catch ( Exception $ex ) {
			// EntityLookupException or Exception.
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
		$image = \PageImages::getPageImage( $title );
		return $image ? $image : null;
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
