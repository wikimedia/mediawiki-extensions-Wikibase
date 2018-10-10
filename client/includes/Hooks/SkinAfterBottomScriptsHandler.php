<?php

namespace Wikibase\Client\Hooks;

use HTML;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;

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
	 * @param Skin $entityId
	 */
	public function addSchema( string &$html, Title $title, EntityId $entityId ) {
		// todo: should this use out->addInlineScript() which adds a nonce? It's not JavaScript, it's
		// JSON-LD, and will need a type attribute.
		$html .= Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( [
			'@context' => 'http://schema.org', // todo: HTTPS? Go Fish and Google use HTTP in their examples.
			'@type' => $entityId->getEntityType(), // todo: fixxxxx
			'name' => $title->getText(),
			'url' => $title->getFullURL(),
			'sameAs' => [
				$this->repoLinker->getCanonicalEntityUrl( $entityId )
			],
		] );
		$html .= Html::closeElement( 'script' );

		return true;
	}
}
