<?php

namespace Wikibase\Client\Hooks;

use HTML;
use Title;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Client\RepoLinker;

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
	 * @param EntityDocument $entity
	 */
	public function addSchema( &$html, Title $title, EntityId $entityId ) {
		$schema = [
			'@type' => 'schema:Article',
			'name' => $title->getText(),
			'url' => $title->getFullURL(),
			'sameAs' => [ $this->repoLinker->getCanonicalEntityUrl( $entityId ) ],
		];

		$html .= Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );

		return true;
	}
}
