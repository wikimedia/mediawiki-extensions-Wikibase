<?php

namespace Wikibase\Repo\Hooks;

use Html;
use IContextSource;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * Handler to format entities in the search results
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 * @author Daniel Kinzler
 */
class ShowSearchHitHandler {

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	public function __construct(
		EntityContentFactory $entityContentFactory,
		LanguageFallbackChain $languageFallbackChain,
		EntityIdLookup $entityIdLookup,
		EntityLookup $entityLookup
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->entityIdLookup = $entityIdLookup;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param IContextSource $context
	 * @return self
	 */
	private static function newFromGlobalState( IContextSource $context ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();

		return new self(
			$wikibaseRepo->getEntityContentFactory(),
			$languageFallbackChainFactory->newFromContext( $context ),
			$wikibaseRepo->getEntityIdLookup(),
			$wikibaseRepo->getEntityLookup()
		);
	}

	/**
	 * Format the output when the search result contains entities
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ShowSearchHit
	 * @see doShowSearchHit
	 *
	 * @param SpecialSearch $searchPage
	 * @param SearchResult $result
	 * @param array $terms
	 * @param string &$link
	 * @param string &$redirect
	 * @param string &$section
	 * @param string &$extract
	 * @param string &$score
	 * @param string &$size
	 * @param string &$date
	 * @param string &$related
	 * @param string &$html
	 */
	public static function onShowSearchHit( SpecialSearch $searchPage, SearchResult $result, array $terms,
		&$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html
	) {
		$self = self::newFromGlobalState( $searchPage->getContext() );
		$self->doShowSearchHit( $searchPage, $result, $terms, $link, $redirect, $section, $extract,
			$score, $size, $date, $related, $html );
	}

	public function doShowSearchHit( SpecialSearch $searchPage, SearchResult $result, array $terms,
		&$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html
	) {
		$title = $result->getTitle();
		$contentModel = $title->getContentModel();

		if ( !$this->entityContentFactory->isEntityContentModel( $contentModel ) ) {
			return;
		}

		$extract = ''; // TODO: set this to something useful.

		$entity = $this->getEntity( $title );
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			return;
		}

		$terms = $entity->getDescriptions()->toTextArray();
		$termData = $this->languageFallbackChain->extractPreferredValue( $terms );
		if ( $termData !== null ) {
			$this->addDescription( $link, $termData, $searchPage );
		}
	}

	private function addDescription( &$link, array $termData, SpecialSearch $searchPage ) {
		$description = $termData['value'];
		$attr = [ 'class' => 'wb-itemlink-description' ];
		if ( $termData['language'] !== $searchPage->getLanguage()->getCode() ) {
			$attr += [ 'dir' => 'auto', 'lang' => wfBCP47( $termData['language'] ) ];
		}
		$link .= $searchPage->msg( 'colon-separator' )->escaped();
		$link .= Html::element( 'span', $attr, $description );
	}

	/**
	 * @param Title $title
	 * @return EntityDocument|null
	 */
	private function getEntity( Title $title ) {
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( $entityId ) {
			return $this->entityLookup->getEntity( $entityId );
		}
		return null;
	}

}
