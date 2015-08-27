<?php

namespace Wikibase\Repo\Specials;

use Html;
use Linker;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Base for special pages that show the result of a Query. Rewriting of QueryPage but
 * with abstraction of the storage system and without cache support.
 *
 * @since 0.3
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
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

		$this->setQueryPageServices(
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()
		);
	}

	public function setQueryPageServices( EntityTitleLookup $entityTitleLookup ) {
		$this->entityTitleLookup = $entityTitleLookup;
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
	 * Formats a row for display.
	 *
	 * @since 0.4 (as abstract function with same interface in 0.3)
	 *
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	protected function formatRow( EntityId $entityId ) {
		$title = $this->entityTitleLookup->getTitleForId( $entityId );
		return Linker::linkKnown( $title );
	}

	/**
	 * Return the result of the query
	 *
	 * @since 0.3
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
	 *
	 * @since 0.3
	 */
	protected function showQuery( array $query = array() ) {
		list( $limit, $offset ) = $this->getRequest()->getLimitOffset();
		$result = $this->getResult( $offset, $limit + 1 );

		if ( empty( $result ) ) {
			$this->getOutput()->addWikiMsg( 'specialpage-empty' );
			return;
		}

		$numRows = count( $result );

		$html = $this->msg( 'showingresults' )
			->numParams( min( $numRows, $limit ), $offset + 1 )
			->parseAsBlock();

		$paging = $this->getLanguage()->viewPrevNext(
			$this->getPageTitle(),
			$offset,
			$limit,
			$query,
			$numRows <= $limit
		);

		$html .= Html::rawElement( 'p', array(), $paging );
		$html .= $this->formatResult( array_slice( $result, 0, $limit, true ), $offset );
		$html .= Html::rawElement( 'p', array(), $paging );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param integer $offset paging offset
	 *
	 * @return string
	 */
	protected function formatResult( array $entityIds, $offset ) {
		$html = Html::openElement( 'ol', array( 'start' => $offset + 1, 'class' => 'special' ) );

		foreach ( $entityIds as $entityId ) {
			$html .= Html::rawElement( 'li', array(), $this->formatRow( $entityId ) );
		}

		$html .= Html::closeElement( 'ol' );
		return $html;
	}

}
