<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use MediaWiki\SpecialPage\SpecialPageFactory;

/**
 * @license GPL-2.0-or-later
 */
class AnonymousEditWarningBuilder {

	private SpecialPageFactory $specialPageFactory;

	public function __construct(
		SpecialPageFactory $specialPageFactory
	) {
		$this->specialPageFactory = $specialPageFactory;
	}

	public function buildAnonymousEditWarningHTML( string $returnToFullTitle ): string {
		$loginPage = $this->specialPageFactory->getPage( 'UserLogin' );
		$loginHref = $loginPage->getPageTitle()->getFullURL( 'returnto=' . $returnToFullTitle );
		$createAccountHref = $this->specialPageFactory
			->getPage( 'CreateAccount' )
			->getPageTitle()
			->getFullURL( 'returnto=' . $returnToFullTitle );
		return $loginPage->msg( 'wikibase-anonymouseditwarning', $loginHref, $createAccountHref )->parse();
	}
}
