<?php

namespace Wikibase\Repo\View;

use SpecialPage;
use Wikibase\View\SpecialPageLinker;

/**
 * A SpecialPageLinker implementation linking to special pages of the local MediaWiki installation.
 *
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class RepoSpecialPageLinker implements SpecialPageLinker {

	/**
	 * @param string $pageName
	 * @param string[] $subPageParams Parameters to be added as slash-separated sub pages
	 * @return string
	 */
	public function getLink( $pageName, array $subPageParams = array() ) {
		$subPage = implode( '/', array_map( 'wfUrlencode', $subPageParams ) );
		$specialPageTitle = SpecialPage::getTitleFor( $pageName, $subPage );

		return $specialPageTitle->getLocalURL();
	}

}
