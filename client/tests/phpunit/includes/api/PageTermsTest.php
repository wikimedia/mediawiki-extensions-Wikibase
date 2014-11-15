<?php

namespace Wikibase\Test;

use ApiMain;
use ApiQuery;
use FauxRequest;
use Title;
use Wikibase\Client\Store\EntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\PageTerms;
use Wikibase\Term;
use Wikibase\TermIndex;
use Wikibase\Test\Api\ApiModuleTestHelper;

/**
 * @covers Wikibase\PageTerms
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

	/**
	 * @var MockRepository
	 */
	private $repo = null;

	/**
	 * @var EntityModificationTestHelper
	 */
	private $entityModificationTestHelper = null;

	/**
	 * @var ApiModuleTestHelper
	 */
	private $apiModuleTestHelper = null;

	protected function setUp() {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();
		$this->apiModuleTestHelper = new ApiModuleTestHelper();

		$this->repo = $this->entityModificationTestHelper->getRepository();

		$this->entityModificationTestHelper->putEntities( array(
			'Q1' => array(),
			'Q2' => array(),
			'P1' => array( 'datatype' => 'string' ),
			'P2' => array( 'datatype' => 'string' ),
		) );

		$this->entityModificationTestHelper->putRedirects( array(
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		) );
	}

	/**
	 * @param array $params
	 * @param Title[] $titles
	 *
	 * @return ApiQuery
	 */
	private function getQueryModule( array $params, array $titles ) {
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

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
	 * @param int[] $pageIds
	 *
	 * @return Title[]
	 */
	private function makeTitles( $pageIds ) {
		$titles = array();

		foreach ( $pageIds as $pid ) {
			$title = Title::makeTitle( NS_MAIN, 'No' . $pid );
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
			$entityIds[$pid] = new ItemId( "Q$pid" );
		}

		return $entityIds;
	}

	/**
	 * @param array $terms
	 *
	 * @return TermIndex
	 */
	private function getTermIndex( array $terms ) {
		$termObjectsByEntityId = array();

		foreach ( $terms as $pid => $termGroups ) {
			$key = "Q$pid";
			$termObjectsByEntityId[$key] = $this->makeTermsFromGroups( new ItemId( $key ), $termGroups );
		}

		$this_ = $this;

		$termIndex = $this->getMock( 'Wikibase\TermIndex' );
		$termIndex->expects( $this->any() )
			->method( 'getTermsOfEntities' )
			->will( $this->returnCallback(
				function ( array $entityIds, $entityType, $language = null ) use( $termObjectsByEntityId, $this_ ) {
					return $this_->getTermsOfEntities( $termObjectsByEntityId, $entityIds, $entityType, $language );
				}
			) );

		return $termIndex;
	}

	/**
	 * @param array $termObjectsByEntityId
	 * @param EntityId[] $entityIds
	 * @param string $entityType
	 * @param string|null $language
	 *
	 * @return Term[]
	 */
	private function getTermsOfEntities( $termObjectsByEntityId, $entityIds, $entityType, $language = null ) {
		$result = array();

		foreach ( $entityIds as $id ) {
			$key = $id->getSerialization();

			if ( !isset( $termObjectsByEntityId[$key] ) ) {
				continue;
			}

			/** @var Term $term */
			foreach ( $termObjectsByEntityId[$key] as $term ) {
				if ( $term->getLanguage() === $language ) {
					$result[] = $term;
				}
			}
		}

		return $result;
	}

	/**
	 * @param EntityId $entityId
	 * @param array $termGroups
	 *
	 * @return array
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

	/**
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup() {
		$idLookup = $this->getMock( 'Wikibase\Client\Store\EntityIdLookup' );
		$idLookup->expects( $this->any() )
			->method( 'getEntityIds' )
			->will( $this->returnCallback( function( array $titles ) {
				$entityIds = array();

				/** @var Title $title */
				foreach ( $titles as $title ) {
					$pid = $title->getArticleId();
					$entityIds[$pid] = new ItemId( "Q$pid" );
				}

				return $entityIds;
			} ) );

		return $idLookup;
	}

	/**
	 * @param array $params
	 * @param array $terms
	 *
	 * @return array
	 */
	private function callApiModule( array $params, array $terms ) {
		$titles = $this->makeTitles( array_keys( $terms ) );
		$entityIds = $this->makeEntityIds( array_keys( $terms ) );

		$module = new PageTerms(
			$this->getTermIndex( $terms ),
			$this->getEntityIdLookup( $entityIds ),
			$this->getQueryModule( $params, $titles ),
			'pageterms'
		);

		$module->execute();

		$result = $module->getResult();
		return $result->getData();
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
		);

		return array(
			'by title' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
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
				)
			),
			'descriptions only' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'ptterms' => 'description',
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
			'continue' => array(
				array(
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'ptcontinue' => '20',
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

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertEquals( $expected, $result['query']['pages'] );
	}

}
