<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\User\TempUser\TempUserConfig;

/**
 * @license GPL-2.0-or-later
 */
class AnonymousEditWarningBuilder {

	private SpecialPageFactory $specialPageFactory;
	private TempUserConfig $tempUserConfig;

	public function __construct(
		SpecialPageFactory $specialPageFactory,
		TempUserConfig $tempUserConfig
	) {
		$this->specialPageFactory = $specialPageFactory;
		$this->tempUserConfig = $tempUserConfig;
	}

	public function buildAnonymousEditWarningHTML( string $returnToFullTitle ): string {
		$loginPage = $this->specialPageFactory->getPage( 'UserLogin' );
		$loginHref = $loginPage->getPageTitle()->getFullURL( 'returnto=' . $returnToFullTitle );
		$createAccountHref = $this->specialPageFactory
			->getPage( 'CreateAccount' )
			->getPageTitle()
			->getFullURL( 'returnto=' . $returnToFullTitle );
		$messageKey = 'wikibase-anonymouseditwarning';
		if ( $this->tempUserConfig->isEnabled() ) {
			$messageKey = 'wikibase-anonymouseditnotificationtempuser';
		}
		return $loginPage->msg( $messageKey, $loginHref, $createAccountHref )->parse();
	}
}
