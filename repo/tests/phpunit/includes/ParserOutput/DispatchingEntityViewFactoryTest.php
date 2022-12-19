<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use Language;
use LogicException;
use MediaWiki\MediaWikiServices;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\View\EntityDocumentView;

/**
 * @covers \Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DispatchingEntityViewFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testInvalidConstructorArgument() {
		$this->expectException( InvalidArgumentException::class );
		new DispatchingEntityViewFactory(
			[ 'invalid' ]
		);
	}

	public function testUnknownEntityType() {
		$factory = new DispatchingEntityViewFactory(
			[]
		);

		$this->expectException( OutOfBoundsException::class );
		$factory->newEntityView(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			$this->createMock( EntityDocument::class )
		);
	}

	public function testNoEntityViewReturned() {
		$factory = new DispatchingEntityViewFactory(
			[
				'foo' => function() {
					return null;
				},
			]
		);

		$unknownEntity = $this->createMock( EntityDocument::class );
		$unknownEntity->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$this->expectException( LogicException::class );
		$factory->newEntityView(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			$unknownEntity
		);
	}

	public function testNewEntityView() {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$languageFallbackChain = new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) );
		$entity = $this->createMock( EntityDocument::class );
		$entity->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'foo' );
		$entityView = $this->createMock( EntityDocumentView::class );

		$factory = new DispatchingEntityViewFactory(
			[
				'foo' => function(
					Language $languageParam,
					TermLanguageFallbackChain $fallbackChainParam,
					EntityDocument $entityParam
				) use(
					$language,
					$languageFallbackChain,
					$entity,
					$entityView
				) {
					$this->assertSame( $language, $languageParam );
					$this->assertSame( $languageFallbackChain, $fallbackChainParam );
					$this->assertSame( $entity, $entityParam );

					return $entityView;
				},
			]
		);

		$newEntityView = $factory->newEntityView(
			$language,
			$languageFallbackChain,
			$entity
		);

		$this->assertSame( $entityView, $newEntityView );
	}

}
