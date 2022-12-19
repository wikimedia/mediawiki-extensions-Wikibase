<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use ApiUsageException;
use HashBagOStuff;
use MediaWiki\MainConfigNames;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Api\FormatEntities;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\FormatEntities
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class FormatEntitiesTest extends ApiTestCase {

	/**
	 * @var string Fake value for $wgServer
	 */
	private const BASE_URL = 'http://a.test';

	private function saveEntity( EntityDocument $entity ) {
		$store = WikibaseRepo::getEntityStore();

		$store->saveEntity( $entity, 'testing', $this->getTestUser()->getUser(), EDIT_NEW );
	}

	public function testApiRequest() {
		// non-EmptyBagOStuff cache needed for the CachingPrefetchingTermLookup for properties
		$this->setService( 'LocalServerObjectCache', new HashBagOStuff() );

		$item = NewItem::withLabel( 'en', 'test item' )->build();
		$this->saveEntity( $item );
		$itemId = $item->getId()->getSerialization();
		$fingerprint = new Fingerprint( new TermList( [ new Term( 'en', 'test property' ) ] ) );
		$property = new Property( null, $fingerprint, 'string' );
		$this->saveEntity( $property );
		$propertyId = $property->getId()->getSerialization();

		$params = [
			'action' => 'wbformatentities',
			'ids' => "$itemId|$propertyId",
		];

		[ $resultArray ] = $this->doApiRequest( $params );
		$this->assertArrayHasKey( 'wbformatentities', $resultArray );
		$results = $resultArray['wbformatentities'];

		$this->assertArrayHasKey( $itemId, $results );
		$this->assertStringMatchesFormat(
			'<a title="%S' . $itemId . '" href="http%s">test item</a>',
			$results[$itemId]
		);

		$this->assertArrayHasKey( $propertyId, $results );
		$this->assertStringMatchesFormat(
			'<a title="%S' . $propertyId . '" href="http%s">test property</a>',
			$results[$propertyId]
		);
	}

	public function testApiRequest_noSuchEntity() {
		$params = [
			'action' => 'wbformatentities',
			'ids' => 'X',
		];

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'Could not find an entity with the ID "X".' );
		$this->doApiRequest( $params );
	}

	/**
	 * returns the input HTML snippet and the expected result
	 */
	public function provideHtmlSnippets() {
		$foo = self::BASE_URL . '/foo';

		return [
			'plain text' => [
				'text',
				'text',
			],
			'no link' => [
				'<span class="other-message">oops</span>',
				'<span class="other-message">oops</span>',
			],
			'simple absolute link' => [
				'<a href="http://another.test/">link</a>',
				'<a href="http://another.test/">link</a>',
			],
			'simple relative link' => [
				'<a href="/foo">link</a>',
				"<a href=\"$foo\">link</a>",
			],
			'extra attributes' => [
				'<a title="title" href="/foo" rel="noopener">link</a>',
				"<a title=\"title\" href=\"$foo\" rel=\"noopener\">link</a>",
			],
			'single-quoted attributes' => [
				"<a title='title' href='/foo' rel='noopener'>link</a>",
				"<a title=\"title\" href=\"$foo\" rel=\"noopener\">link</a>",
			],
			'unquoted attributes' => [
				'<a title=title href=/foo rel=noopener>link</a>',
				"<a title=\"title\" href=\"$foo\" rel=\"noopener\">link</a>",
			],
			'empty attributes' => [
				'<a hidden href="/foo">link</a>',
				"<a hidden=\"\" href=\"$foo\">link</a>",
			],
			'mixed attributes' => [
				"<a title=\"title\" href='/foo' rel=noopener hidden>link</a>",
				"<a title=\"title\" href=\"$foo\" rel=\"noopener\" hidden=\"\">link</a>",
			],
			'spaced attributes' => [
				'<a href  =  "/foo">link</a>',
				"<a href=\"$foo\">link</a>",
			],
			'custom attribute names' => [
				'<a data-extra=foo href="/foo">link</a>',
				"<a data-extra=\"foo\" href=\"$foo\">link</a>",
			],
			'text before' => [
				'some text before the <a href="/foo">link</a>',
				"some text before the <a href=\"$foo\">link</a>",
			],
			'elements inside and after' => [
				'<a href="/foo"><bdi>foo</bdi></a>&nbsp;<sup>hi</sup>',
				"<a href=\"$foo\"><bdi>foo</bdi></a>&nbsp;<sup>hi</sup>",
			],
			'elements before' => [
				'<sub>howdy</sub><a href="/foo">link</a>',
				"<sub>howdy</sub><a href=\"$foo\">link</a>",
			],
			'two links' => [
				'<a href="/foo">link 1</a>, <a href="/foo">link 2</a>',
				"<a href=\"$foo\">link 1</a>, <a href=\"$foo\">link 2</a>",
			],
		];
	}

	/**
	 * @dataProvider provideHtmlSnippets
	 */
	public function testMakeLinksAbsolute( $html, $expected ) {
		$this->overrideConfigValues( [
			MainConfigNames::Server => self::BASE_URL,
			MainConfigNames::CanonicalServer => self::BASE_URL,
		] );

		$actual = TestingAccessWrapper::newFromClass( FormatEntities::class )
			->makeLinksAbsolute( $html );

		$this->assertSame( $expected, $actual );
	}

}
