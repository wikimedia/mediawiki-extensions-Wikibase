<?php

namespace Wikibase\Client\Hooks;

use HTML;
use Title;

use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\SnakFormatter;
use ValueFormatters\FormatterOptions;

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
	 * @param string &$html
	 * @param Title $title
	 * @param EntityId $entityId
	 */
	public function addSchema( &$html, Title $title, EntityId $entityId ) {
		$entity = $this->getEntity( $entityId );
		if ( !$entity ) {
			return true;
		}

		// todo: should this use addInlineScript() which adds a nonce? It's not JavaScript, it's
		// JSON-LD, and will need a type attribute.
		$schema = [
			'@context' => 'http://schema.org',
			'@type' => $this->getType( $entity ),
			'name' => $title->getText(),
			'url' => $title->getFullURL(),
			'sameAs' => [
				$this->repoLinker->getCanonicalEntityUrl( $entityId )
			],
		];
		$description = $this->getDescription( $entity );
		if ( $description ) {
			$schema['description'] = $description;
		}

		$html .= Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );

		return true;
	}

	/**
	 * @param EntityDocument $entity
	 * @return string|null
	 */
	private function getType( EntityDocument $entity ) {
		$snaksFinder = new SnaksFinder();
		// todo: use config for [ 'P31', 'P279' ] and check both.
		$snaks = $snaksFinder->findSnaks( $entity, new PropertyId( 'P3' ) );
		if ( empty( $snaks ) ) {
			return null;
		}

		$snak = $snaks[0];
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$snakFormatterFactory = $wikibaseClient->getSnakFormatterFactory();
		$snakFormatter = $snakFormatterFactory->getSnakFormatter( SnakFormatter::FORMAT_PLAIN, new FormatterOptions() );
		return $snakFormatter->formatSnak( $snak );
	}

	/**
	 * @param EntityDocument $entity
	 * @return string|null
	 */
	private function getDescription( EntityDocument $entity ) {
		global $wgContLang;
		$langCode = $wgContLang->getCode();
		$fingerprint = $entity->getFingerprint();
		if ( !$fingerprint->hasDescription( $langCode ) ) {
			return null;
		}
		return $fingerprint->getDescription( $langCode )->getText();
	}

	/**
	 * @param EntityId $entityId
	 * @return EntityDocument|null
	 */
	private function getEntity( EntityId $entityId ) {
		try {
			$entityLookup = WikibaseClient::getDefaultInstance()
				->getStore()
				->getEntityLookup();
			return $entityLookup->getEntity( $entityId );
		} catch ( Exception $ex ) {
			return null;
		}
	}

}
