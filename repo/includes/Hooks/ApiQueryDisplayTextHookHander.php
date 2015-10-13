<?php

namespace Wikibase\Repo\Hooks;

use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Katie Filbert
 */
class ApiQueryDisplayTextHookHandler {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new ApiQueryDisplayTextHookHandler(
			$wikibaseRepo->getTermLookup(),
			$wikibaseRepo->getEntityIdLookup()
		);
	}

	/**
	 * Static handler for the ApiQueryDisplayText hook.
	 *
	 * @param string[] &$displayTexts Key by page id
	 * @param Title[] $titles Key by page id
	 * @param string $langCode Language code
	 *
	 * @return bool
	 */
	public static function onApiQueryDisplayText( array &$displayTexts, array $titles, $langCode ) {
		$handler = self::newFromGlobalState();
		$displayTexts = $handler->addDisplayTexts( $displayTexts, $titles, $langCode );

		return true;
	}

	/**
	 * @param TermLookup $termLookup
	 */
	public function __construct( TermLookup $termLookup, EntityIdLookup $entityIdLookup ) {
		$this->termLookup = $termLookup;
		$this->entityIdLookup = $entityIdLookup;
	}

	public function addDisplayTexts( array $displayTexts, array $titles, $langCode ) {
		foreach ( $titles as $pageId => $title ) {
			$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
			$displayTexts[$pageId] = $this->termLookup->getLabel( $entityId, $langCode );
		}

		return $displayTexts;
	}

}
