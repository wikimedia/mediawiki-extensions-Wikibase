<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Http\HttpRequestFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\SettingsArray;
use Wikibase\View\CacheableEntityTermsView;

/**
 * @license GPL-2.0-or-later
 */
class TermboxView implements CacheableEntityTermsView {

	private $fallbackChain;

	private $requestFactory;

	private $settings;

	public function __construct(
		LanguageFallbackChain $fallbackChain,
		HttpRequestFactory $requestFactory,
		SettingsArray $settings
	) {
		$this->fallbackChain = $fallbackChain;
		$this->requestFactory = $requestFactory;
		$this->settings = $settings;
	}

	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	) {
		$request = $this->requestFactory->create(
			$this->settings->getSetting( 'ssrServerUrl' ),
			[ /* TODO attach required data */ ]
		);
		$request->execute();

		return $request->getContent();
	}

	/**
	 * FIXME This actually sets the html within h1#firstHeading.
	 * The correct title could either be set here, or it gets hidden, similarly to how the Lemma title is handled
	 * for WikibaseLexeme.
	 */
	public function getTitleHtml( EntityId $entityId = null ) {
		return '';
	}

	/**
	 * @see \Wikibase\View\ViewPlaceHolderEmitter
	 */
	public function getPlaceholders(
		EntityDocument $entity,
		$languageCode
	) {
		return [];
	}

}
