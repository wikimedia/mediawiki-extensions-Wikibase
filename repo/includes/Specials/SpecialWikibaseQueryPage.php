<?php

namespace Wikibase\Repo\Specials;

use Html;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Base for special pages that show the result of a Query. Rewriting of QueryPage but
 * with abstraction of the storage system and without cache support.
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
abstract class SpecialWikibaseQueryPage extends SpecialWikibasePage {

	/**
	 * Max server side caching time in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL_IN_SECONDS = 10;

	/**
	 * The offset in use
	 *
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * The limit in use
	 *
	 * @var integer
	 */
	protected $limit = 0;

	/**
	 * The number of rows returned by the query. Reading this variable
	 * only makes sense in functions that are run after the query has been
	 * done.
	 *
	 * @var integer
	 */
	protected $numRows;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @param string $name
	 * @param string $restriction
	 * @param bool   $listed
	 */
	public function __construct( $name = '', $restriction = '', $listed = true ) {
		parent::__construct( $name, $restriction, $listed );

		$this->entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();
		$output->setCdnMaxage( static::CACHE_TTL_IN_SECONDS );
	}

	/**
	 * Formats a row for display.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	protected function formatRow( EntityId $entityId ) {
		$title = $this->entityTitleLookup->getTitleForId( $entityId );
		return $this->getLinkRenderer()->makeKnownLink( $title );
	}

	/**
	 * Return the result of the query
	 *
	 * @param integer $offset Start to include at number of entries from the start title
	 * @param integer $limit Stop at number of entries after start of inclusion
	 *
	 * @return EntityId[]
	 */
	abstract protected function getResult( $offset = 0, $limit = 0 );

	/**
	 * Output the query result
	 *
	 * @param array $query optional array of URL query parameter strings
	 */
	protected function showQuery( array $query = [] ) {
		$paging = false;
		$out = $this->getOutput();

		if ( $this->limit == 0 && $this->offset == 0 ) {
			list( $this->limit, $this->offset ) = $this->getRequest()->getLimitOffset();
		}

		$entityIds = $this->getResult( $this->offset, $this->limit + 1 );

		$this->numRows = count( $entityIds );

		$out->addHTML( Html::openElement( 'div', [ 'class' => 'mw-spcontent' ] ) );

		if ( $this->numRows > 0 ) {
			$out->addHTML( $this->msg( 'showingresults' )->numParams(
				// do not format the one extra row, if exist
				min( $this->numRows, $this->limit ),
				$this->offset + 1 )->parseAsBlock() );
			// Disable the "next" link when we reach the end
			$paging = $this->getLanguage()->viewPrevNext(
				$this->getTitleForNavigation(),
				$this->offset,
				$this->limit,
				$query,
				$this->numRows <= $this->limit
			);
			$out->addHTML( Html::rawElement( 'p', [], $paging ) );
		} else {
			// No results to show, so don't bother with "showing X of Y" etc.
			// -- just let the user know and give up now
			$out->addWikiMsg( 'specialpage-empty' );
		}

		$this->outputResults(
			$entityIds,
			// do not format the one extra row, if it exist
			min( $this->numRows, $this->limit ),
			$this->offset
		);

		if ( $paging ) {
			$out->addHTML( Html::rawElement( 'p', [], $paging ) );
		}

		$out->addHTML( Html::closeElement( 'div' ) );
	}

	/**
	 * Format and output report results using the given information plus OutputPage
	 *
	 * @param EntityId[] $entityIds
	 * @param integer $num number of available result rows
	 * @param integer $offset paging offset
	 */
	protected function outputResults( array $entityIds, $num, $offset ) {
		if ( $num > 0 ) {
			$html = Html::openElement( 'ol', [ 'start' => $offset + 1, 'class' => 'special' ] );
			for ( $i = 0; $i < $num; $i++ ) {
				$row = $this->formatRow( $entityIds[$i] );
				$html .= Html::rawElement( 'li', [], $row );
			}
			$html .= Html::closeElement( 'ol' );

			$this->getOutput()->addHTML( $html );
		}
	}

	/**
	 * Return the Title of the special page with full subpages informations in order to be used for navigation.
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getPageTitle();
	}

}
