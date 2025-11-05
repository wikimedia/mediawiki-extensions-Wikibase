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
