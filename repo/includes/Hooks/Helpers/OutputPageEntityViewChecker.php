<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Hooks\Helpers;

use OutputPage;
use Wikibase\Repo\Content\EntityContentFactory;

/**
 * @license GPL-2.0-or-later
 */
class OutputPageEntityViewChecker {

	/** @var EntityContentFactory */
	private $entityContentFactory;

	public function __construct( EntityContentFactory $entityContentFactory ) {
		$this->entityContentFactory = $entityContentFactory;
	}

	public function hasEntityView( OutputPage $out ): bool {
		return $this->isEntityArticlePage( $out ) || $this->hasEntityId( $out );
	}

	private function isEntityArticlePage( OutputPage $out ): bool {
		$title = $out->getTitle();

		return $out->isArticle() && $title &&
			$this->entityContentFactory->isEntityContentModel( $title->getContentModel() );
	}

	private function hasEntityId( OutputPage $out ): bool {
		return array_key_exists( 'wbEntityId', $out->getJsConfigVars() );
	}

}
