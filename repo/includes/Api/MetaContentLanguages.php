<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiQuery;
use ApiQueryBase;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class MetaContentLanguages extends ApiQueryBase {

	private LanguageNameLookupFactory $languageNameLookupFactory;
	private WikibaseContentLanguages $wikibaseContentLanguages;

	public function __construct(
		ApiQuery $queryModule,
		string $moduleName,
		LanguageNameLookupFactory $languageNameLookupFactory,
		WikibaseContentLanguages $wikibaseContentLanguages
	) {
		parent::__construct( $queryModule, $moduleName, 'wbcl' );
		$this->languageNameLookupFactory = $languageNameLookupFactory;
		$this->wikibaseContentLanguages = $wikibaseContentLanguages;
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		$context = $params['context'];
		$contentLanguages = $this->wikibaseContentLanguages->getContentLanguages( $context );
		$languageCodes = $contentLanguages->getLanguages();

		$props = $params['prop'];
		$includeCode = in_array( 'code', $props );
		$includeAutonym = in_array( 'autonym', $props );
		$includeName = in_array( 'name', $props );

		$autonymLookup = $this->languageNameLookupFactory->getForAutonyms();
		$nameLookup = $this->languageNameLookupFactory->getForLanguageCode(
			$this->getLanguage()->getCode() );

		foreach ( $languageCodes as $languageCode ) {
			$path = [
				$this->getQuery()->getModuleName(),
				$this->getModuleName(),
				$languageCode,
			];

			if ( $includeCode ) {
				$result->addValue( $path, 'code', $languageCode );
			}

			if ( $includeAutonym ) {
				$autonym = $this->getNameForContext( $autonymLookup, $context, $languageCode );
				$result->addValue( $path, 'autonym', $autonym );
			}

			if ( $includeName ) {
				$name = $this->getNameForContext( $nameLookup, $context, $languageCode );
				$result->addValue( $path, 'name', $name );
			}
		}
	}

	private function getNameForContext(
		LanguageNameLookup $languageNameLookup,
		string $context,
		string $languageCode
	): string {
		if ( $context === WikibaseContentLanguages::CONTEXT_TERM ) {
			return $languageNameLookup->getNameForTerms( $languageCode );
		} else {
			return $languageNameLookup->getName( $languageCode );
		}
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ): string {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'context' => [
				ParamValidator::PARAM_DEFAULT => 'term',
				ParamValidator::PARAM_TYPE => $this->wikibaseContentLanguages->getContexts(),
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'code',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'code',
					'autonym',
					'name',
				],
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$pathUrl = 'action=' . $this->getQuery()->getModuleName() .
			'&meta=' . $this->getModuleName();
		$pathMsg = $this->getModulePath();

		return [
			"$pathUrl" => "apihelp-$pathMsg-example-1",
			"$pathUrl&wbclcontext=monolingualtext&wbclprop=code|autonym" => "apihelp-$pathMsg-example-2",
		];
	}

}
