<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use MediaWiki\Page\PageReference;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\TitleFormatter;
use MediaWiki\User\TempUser\TempUserConfig;
use Wikimedia\Message\MessageValue;

/**
 * @license GPL-2.0-or-later
 */
class AnonymousEditWarningBuilder {

	private SpecialPageFactory $specialPageFactory;
	private TitleFormatter $titleFormatter;
	private TempUserConfig $tempUserConfig;

	public function __construct(
		SpecialPageFactory $specialPageFactory,
		TitleFormatter $titleFormatter,
		TempUserConfig $tempUserConfig
	) {
		$this->specialPageFactory = $specialPageFactory;
		$this->titleFormatter = $titleFormatter;
		$this->tempUserConfig = $tempUserConfig;
	}

	/** @deprecated since 1.45, use `buildAnonymousEditWarningMessage()` instead */
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

	public function buildAnonymousEditWarningMessage( PageReference $returnToPage ): MessageValue {
		$query = [
			'returnto' => $this->titleFormatter->getPrefixedDBkey( $returnToPage ),
		];
		$messageKey = $this->tempUserConfig->isEnabled()
			? 'wikibase-anonymouseditnotificationtempuser'
			: 'wikibase-anonymouseditwarning';
		$loginHref = $this->specialPageFactory
			->getPage( 'UserLogin' )
			->getPageTitle()
			->getFullURL( $query );
		$createAccountHref = $this->specialPageFactory
			->getPage( 'CreateAccount' )
			->getPageTitle()
			->getFullURL( $query );

		return new MessageValue(
			$messageKey,
			[
				$loginHref,
				$createAccountHref,
			]
		);
	}
}
