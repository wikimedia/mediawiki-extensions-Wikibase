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
	 * @param Title $title
	 * @param EntityId $entityId
	 * @return string
	 */
	public function createSchema( Title $title, EntityId $entityId ) {
		$schema = [
			'@type' => 'schema:Article',
			'name' => $title->getText(),
			'url' => $title->getFullURL( '', false, PROTO_CANONICAL ),
			'sameAs' => [ $this->repoLinker->getCanonicalEntityUrl( $entityId ) ],
		];

		$html = Html::openElement( 'script', [ 'type' => 'application/ld+json' ] );
		$html .= json_encode( $schema );
		$html .= Html::closeElement( 'script' );
		return $html;
	}

}
