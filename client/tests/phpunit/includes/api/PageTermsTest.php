<?php

namespace Wikibase\Test;

use ApiMain;
use ApiQuery;
use FauxRequest;
use Language;
use RequestContext;
use Title;
use Wikibase\Client\Api\PageTerms;
use Wikibase\Store\EntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Client\Api\PageTerms
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PageTermsTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( 'wgLang', Language::factory( 'en' ) );
		$this->setMwGlobals( 'wgContLang', Language::factory( 'en' ) );
	}

	/**
	 * @param array $params
	 * @param Title[] $titles
	 *
	 * @return ApiQuery
	 */
	private function getQueryModule( array $params, array $titles ) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );

		$pageSet = $this->getMockBuilder( 'ApiPageSet' )
			->setConstructorArgs( array( $main ) )
			->getMock();

		$pageSet->expects( $this->any() )
			->method( 'getGoodTitles' )
			->will( $this->returnValue( $titles ) );

		$query = $this->getMockBuilder( 'ApiQuery' )
			->setConstructorArgs( array( $main, $params['action'] ) )
			->setMethods( array( 'getPageSet' ) )
			->getMock();

		$query->expects( $this->any() )
			->method( 'getPageSet' )
			->will( $this->returnValue( $pageSet ) );

		return $query;
	}

	/**
	 * @param string[] $names
	 *
	 * @return Title[]
	 */
	private function makeTitles( array $names ) {
		$titles = array();

		foreach ( $names as $name ) {
			if ( !preg_match( '/^\D+/', $name ) ) {
				continue;
			}

			$title = Title::makeTitle( NS_MAIN, $name );

			$pid = (int)preg_replace( '/^\D+/', '', $name );
			$title->resetArticleID( $pid );

			$titles[$pid] = $title;
		}

		return $titles;
	}

	/**
	 * @param int[] $pageIds
	 *
	 * @return EntityId[]
	 */
	private function makeEntityIds( $pageIds ) {
		$entityIds = array();

		foreach ( $pageIds as $pid ) {
			$entityIds[$pid] = $this->newEntityId( $pid );
		}

		return $entityIds;
	}

	/**
	 * @param array[] $terms
	 *
	 * @return TermIndex
	 */
	private function getTermIndex( array $terms ) {
		$termObjectsByEntityId = array();

		foreach ( $terms as $pid => $termGroups ) {
			$entityId = $this->newEntityId( $pid );
			$key = $entityId->getSerialization();
			$termObjectsByEntityId[$key] = $this->makeTermsFromGroups( $entityId, $termGroups );
		}

		$self = $this;

		$termIndex = $this->getMock( 'Wikibase\TermIndex' );
		$termIndex->expects( $this->any() )
			->method( 'getTermsOfEntities' )
			->will( $this->returnCallback(
				function( array $entityIds, array $termTypes = null, array $languagesCodes = null ) use ( $termObjectsByEntityId, $self ) {
					return $self->getTermsOfEntities( $termObjectsByEntityId, $entityIds, $termTypes, $languagesCodes );
				}
			) );

		return $termIndex;
	}

	/**
	 * @note Public only because older PHP versions don't allow it to be called
	 *       from a closure otherwise.
	 *
	 * @param array[] $termObjectsByEntityId
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntities( array $termObjectsByEntityId, array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$result = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();

			if ( !isset( $termObjectsByEntityId[$key] ) ) {
				continue;
			}

			/** @var Term $term */
			foreach ( $termObjectsByEntityId[$key] as $term ) {
				if ( ( is_array( $termTypes ) && !in_array( $term->getType(), $termTypes ) )
					|| ( is_array( $languageCodes ) && !in_array( $term->getLanguage(), $languageCodes ) )
				) {
					continue;
				}

				$result[] = $term;
			}
		}

		return $result;
	}

	/**
	 * @param EntityId $entityId
	 * @param array[] $termGroups
	 *
	 * @return Term[]
	 */
	private function makeTermsFromGroups( EntityId $entityId, $termGroups ) {
		$terms = array();

		foreach ( $termGroups as $type => $group ) {
			foreach ( $group as $lang => $text ) {
				$terms[] = new Term( array(
					'termType' => $type,
					'termLanguage' => $lang,
					'termText' => $text,
					'entityType' => $entityId->getEntityType(),
					'entityId' => $entityId->getNumericId()
				) );
			}
		}

		return $terms;
	}

	public function newEntityId( $pageId ) {
		if ( $pageId > 1000 ) {
			return new PropertyId( "P$pageId" );
		} else {
			return new ItemId( "Q$pageId" );
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup( $entityIds ) {
		$idLookup = $this->getMock( 'Wikibase\Store\EntityIdLookup' );
		$idLookup->expects( $this->any() )
			->method( 'getEntityIds' )
			->will( $this->returnValue( $entityIds ) );

		return $idLookup;
	}

	/**
	 * @param array $params
	 * @param array[] $terms
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, array $terms ) {
		$titles = $this->makeTitles( explode( '|', $params['titles'] ) );
		$entityIds = $this->makeEntityIds( array_keys( $terms ) );

		$module = new PageTerms(
			$this->getTermIndex( $terms ),
			$this->getEntityIdLookup( $entityIds ),
			$this->getQueryModule( $params, $titles ),
			'pageterms'
		);

		$module->execute();

		$result = $module->getResult();
		$data = $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
		return $data;
	}

	public function pageTermsProvider() {
		$terms = array(
			11 => array(
				'label' => array(
					'en' => 'Vienna',
					'de' => 'Wien',
				),
				'description' => array(
					'en' => 'Capitol city of Austria',
					'de' => 'Hauptstadt Österreichs',
				),
			),
			22 => array(
				'label' => array(
					'en' => 'Moscow',
					'de' => 'Moskau',
				),
				'description' => array(
					'en' => 'Capitol city of Russia',
					'de' => 'Hauptstadt Russlands',
				),
			),
			3333 => array(
				'label' => array(
					'en' => 'Population',
					'de' => 'Einwohnerzahl',
				),
				'description' => array(
					'en' => 'number of inhabitants',
					'de' => 'Anzahl der Bewohner',
				),
			),
		);

		return array(
			'by title' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22|No3333',
				),
				$terms,
				array(
					11 => array(
						'terms' => array(
							'label' => array( 'Vienna' ),
							'description' => array( 'Capitol city of Austria' ),
						)
					),
					22 => array(
						'terms' => array(
							'label' => array( 'Moscow' ),
							'description' => array( 'Capitol city of Russia' ),
						)
					),
					3333 => array(
						'terms' => array(
							'label' => array( 'Population' ),
							'description' => array( 'number of inhabitants' ),
						)
					),
				)
			),
			'descriptions only' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'wbptterms' => 'description',
				),
				$terms,
				array(
					11 => array(
						'terms' => array(
							'description' => array( 'Capitol city of Austria' ),
						)
					),
					22 => array(
						'terms' => array(
							'description' => array( 'Capitol city of Russia' ),
						)
					),
				)
			),
			'with uselang' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'uselang' => 'de',
				),
				$terms,
				array(
					11 => array(
						'terms' => array(
							'label' => array( 'Wien' ),
							'description' => array( 'Hauptstadt Österreichs' ),
						)
					),
					22 => array(
						'terms' => array(
							'label' => array( 'Moskau' ),
							'description' => array( 'Hauptstadt Russlands' ),
						)
					),
				)
			),
			'title without entity' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|SomeTitleWithoutEntity',
				),
				$terms,
				array(
					11 => array(
						'terms' => array(
							'label' => array( 'Vienna' ),
							'description' => array( 'Capitol city of Austria' ),
						)
					),
				)
			),
			'continue' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'wbptcontinue' => '20',
				),
				$terms,
				array(
					22 => array(
						'terms' => array(
							'label' => array( 'Moscow' ),
							'description' => array( 'Capitol city of Russia' ),
						)
					),
				)
			),
		);
	}

	/**
	 * @dataProvider pageTermsProvider
	 */
	public function testPageTerms( $params, $terms, $expected ) {
		$result = $this->callApiModule( $params, $terms );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertEquals( $expected, $result['query']['pages'] );
	}

}
