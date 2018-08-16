<?php

namespace Wikibase\Repo\Tests;

use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
trait WikibaseRepoAccess {

	/**
	 * @var WikibaseRepo
	 */
	private $wikibaseRepo;

	public function getWikibaseRepo() {
		if ( !$this->wikibaseRepo ) {
			$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
		}

		return $this->wikibaseRepo;
	}

	/**
	 * @after
	 */
	protected function tearDownWikibaseRepo() {
		if ( $this->wikibaseRepo ) {
			$this->wikibaseRepo->getStore()->clearInMemoryCache();
		}
	}

}
