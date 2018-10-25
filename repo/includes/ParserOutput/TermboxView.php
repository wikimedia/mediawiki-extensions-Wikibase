<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Http\HttpRequestFactory;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\SettingsArray;
use Wikibase\View\CacheableEntityTermsView;

/**
 * @license GPL-2.0-or-later
 */
class TermboxView implements CacheableEntityTermsView {

	private $requestFactory;

	private $termListSerializer;

	private $settings;

	public function __construct(
		HttpRequestFactory $requestFactory,
		TermListSerializer $termListSerializer,
		SettingsArray $settings
	) {
		$this->requestFactory = $requestFactory;
		$this->termListSerializer = $termListSerializer;
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
			[ /* TODO serialize terms */ ]
		);
		$request->execute();

		return $request->getContent();
	}

	public function getTitleHtml( EntityId $entityId = null ) {
		return '';
	}

	public function preparePlaceHolders(
		ParserOutput $parserOutput,
		EntityDocument $entity,
		$languageCode
	) {
	}

}
