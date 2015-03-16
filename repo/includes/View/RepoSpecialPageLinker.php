<?php

namespace Wikibase\Repo\View;

use SpecialPage;
use Wikibase\View\SpecialPageLinker;

/**
 * Description of RepoSpecialPageLinker
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class RepoSpecialPageLinker implements SpecialPageLinker {

	/**
	 * @param string $pageName
	 * @param string[] $params
	 */
	public function getLink( $pageName, $params = array() ) {
		$subPage = implode( '/', array_map( 'wfUrlencode', $params ) );
		$specialPageTitle = SpecialPage::getTitleFor( $pageName, $subPage );

		return $specialPageTitle->getLocalURL();
	}

}
