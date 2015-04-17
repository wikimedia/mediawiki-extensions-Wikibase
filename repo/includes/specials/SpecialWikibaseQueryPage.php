<?php

namespace Wikibase\Repo\Specials;

use Html;
use Linker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\TermBuffer;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * Base for special pages that show the result of a Query. Rewriting of QueryPage but
 * with abstraction of the storage system and without cache support.
 *
 * @since 0.3
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class SpecialWikibaseQueryPage extends SpecialWikibasePage {

	/**
	 * Max server side caching time in seconds.
	 *
	 * @since 0.5
	 *
	 * @type integer
	 */
	const CACHE_TTL_IN_SECONDS = 10;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var TermBuffer
	 */
	private $termBuffer;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/**
	 * @param string $name
	 * @param string $restriction
	 * @param bool   $listed
	 */
	public function __construct( $name = '', $restriction = '', $listed = true ) {
		parent::__construct( $name, $restriction, $listed );

		$this->entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$this->languageFallbackChainFactory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
		$this->termLookup = WikibaseRepo::getDefaultInstance()->getTermLookup();
		$this->termBuffer = WikibaseRepo::getDefaultInstance()->getTermBuffer();
		$this->entityIdFormatterFactory = WikibaseRepo::getDefaultInstance()->getEntityIdHtmlLinkFormatterFactory();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.5
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();
		$output->setSquidMaxage( static::CACHE_TTL_IN_SECONDS );
	}

	/**
	 * Return the result of the query as a list of entity ids
	 *
	 * @since 0.3
	 *
	 * @param integer $offset Start to include at number of entries from the start title
	 * @param integer $limit Stop at number of entries after start of inclusion
	 *
	 * @return EntityId[]
	 */
	protected abstract function getResult( $offset = 0, $limit = 0 );

	/**
	 * Output the query result
	 *
	 * @param array $query optional array of URL query parameter strings
	 *
	 * @since 0.3
	 */
	protected function showQuery( array $query = array() ) {
		list( $limit, $offset ) = $this->getRequest()->getLimitOffset();
		$result = $this->getResult( $offset, $limit );

		if ( empty( $result ) ) {
			$this->getOutput()->addWikiMsg( 'specialpage-empty' );
			return;
		}

		$numRows = count( $result );

		$html = $this->msg( 'showingresults' )
			->numParams( $numRows, $offset + 1 )
			->parseAsBlock();

		// Disable the "next" link when we reach the end
		$paging = $this->getLanguage()->viewPrevNext(
			$this->getPageTitle(),
			$offset,
			$limit,
			$query,
			$numRows < $limit
		);

		$html .= Html::rawElement( 'p', array(), $paging );
		$html .= $this->formatResult( $result, $offset );
		$html .= Html::rawElement( 'p', array(), $paging );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param int $offset paging offset
	 */
	private function formatResult( array $entityIds, $offset ) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$this->getLanguage(),
			LanguageFallbackChainFactory::FALLBACK_SELF
				| LanguageFallbackChainFactory::FALLBACK_VARIANTS
				| LanguageFallbackChainFactory::FALLBACK_OTHERS
		);
		$languages = $languageFallbackChain->getFetchLanguageCodes();
		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$this->termLookup,
			$languageFallbackChain
		);
		$formatter = $this->entityIdFormatterFactory->getEntityIdFormater( $labelDescriptionLookup );
		$this->termBuffer->prefetchTerms( $entityIds, array( 'label' ), $languages );

		$html = Html::openElement( 'ol', array( 'start' => $offset + 1, 'class' => 'special' ) );
		foreach ( $entityIds as $entityId ) {
			$html .= Html::rawElement( 'li', array(), $formatter->formatEntityId( $entityId ) );
		}
		$html .= Html::closeElement( 'ol' );

		return $html;
	}

}
