<?php

namespace Wikibase\Client\Hooks;

use File;
use HTML;
use Title;

use Wikibase\Client\RepoLinker;
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
	 * @param Title $title
	 * @param string|null $revisionTimestamp
	 * @param File|null $image
	 * @param EntityDocument|null $entity
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function createSchema(
		Title $title,
		$revisionTimestamp = null,
		File $image = null,
		EntityDocument $entity = null,
		EntityId $entityId
	) {
		$mainEntity = $this->repoLinker->getCanonicalEntityUrl( $entityId );
		$description = $this->getDescription( $entity );
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Article',
			'name' => $title->getText(),
			'url' => $title->getFullURL( '', false, PROTO_CANONICAL ),
			'sameAs' => $mainEntity,
			'mainEntity' => $mainEntity,
			'author' => [ '@type' => 'Organization', 'name' => 'Wikipedia' ],
			'publisher' => [
				'@type' => 'Organization',
				'name' => 'Wikimedia Foundation, Inc.',
				'logo' => [
					'@type' => 'ImageObject',
					'url' => $this->repoLinker->getBaseUrl() . '/extensions/Wikibase/client/assets/wikimedia.png'
				]
			],
			'datePublished' => wfTimestamp( TS_ISO_8601, $title->getEarliestRevTime() )
		];
		if ( $revisionTimestamp ) {
			$schema['dateModified'] = wfTimestamp( TS_ISO_8601, $revisionTimestamp );
		}
		if ( $image ) {
			$schema['image'] = wfExpandUrl( $image->getUrl(), PROTO_CANONICAL );
		}
		if ( $description ) {
			$schema['headline'] = $description;
		}

		$html = Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );
		return $html;
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
