<?php

namespace Wikibase\Repo\Tests\Actions;

use MediaWiki\Config\HashConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Article;
use MediaWiki\Page\WikiPage;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Actions\HistoryEntityAction;

/**
 * @covers \Wikibase\Repo\Actions\HistoryEntityAction
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class HistoryEntityActionTest extends \PHPUnit\Framework\TestCase {

	/** @see \LanguageQqx */
	private const DUMMY_LANGUAGE = 'qqx';

	private function getWikiPage( Title $title ): WikiPage {
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		return $wikiPage;
	}

	private function getArticle(): Article {
		$title = Title::makeTitle( NS_MAIN, 'Page title' );
		$article = $this->createMock( Article::class );
		$article->method( 'getTitle' )
			->willReturn( $title );
		$article->method( 'getPage' )
			->willReturn( $this->getWikiPage( $title ) );

		return $article;
	}

	/**
	 * @param MockObject $output
	 *
	 * @return IContextSource
	 */
	private function getContext( MockObject $output ) {
		$context = $this->createMock( IContextSource::class );
		$context->method( 'getConfig' )
			->willReturn( new HashConfig( [
				MainConfigNames::UseFileCache => false,
				MainConfigNames::Localtimezone => 'UTC',
				MainConfigNames::ShowUpdatedMarker => true,
				MainConfigNames::LogRestrictions => [],
				MainConfigNames::UserrightsInterwikiDelimiter => '@',
				MainConfigNames::MiserMode => true,
				MainConfigNames::ActionFilteredLogs => [],
				MainConfigNames::MaxExecutionTimeForExpensiveQueries => 0,
				MainConfigNames::Send404Code => true,
			] ) );
		$context->method( 'getRequest' )
			->willReturn( new WebRequest() );
		$context->method( 'getUser' )
			->willReturn( new User() );
		$context->method( 'msg' )
			->willReturnCallback( function( ...$args ) {
				return wfMessage( ...$args )
					->inLanguage( self::DUMMY_LANGUAGE );
			} );

		$context->method( 'getOutput' )
			->willReturn( $output );

		$context->method( 'getLanguage' )
			->willReturn( MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( self::DUMMY_LANGUAGE ) );

		$context->method( 'getTitle' )
			->willReturn( $this->getArticle()->getTitle() );

		$output->expects( $this->once() )
			->method( 'getContext' )
			->willReturn( $context );

		return $context;
	}

	public static function pageTitleProvider() {
		return [
			'fallback to parent' => [
				null,
				null,
				'(history-title: Page title)',
			],
			'without label' => [
				new ItemId( 'Q1' ),
				null,
				'(wikibase-history-title-without-label: Q1)',
			],
			'with label' => [
				new ItemId( 'Q2' ),
				new Term( self::DUMMY_LANGUAGE, 'Label' ),
				'(wikibase-history-title-with-label: Q2, Label)',
			],
		];
	}

	/**
	 * @dataProvider pageTitleProvider
	 */
	public function testGetPageTitle( ?ItemId $entityId, ?Term $label, $expected ) {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->willReturn( $entityId );

		$labelLookup = $this->createMock( LabelDescriptionLookup::class );
		$labelLookup->method( 'getLabel' )
			->willReturn( $label );

		$pageTitle = null;
		$output = $this->createMock( OutputPage::class );
		$output->method( 'setPageTitleMsg' )
			->willReturnCallback( function( $m ) use( &$pageTitle ) {
				$pageTitle = $m->escaped();
			} );

		$action = new HistoryEntityAction(
			$this->getArticle(),
			$this->getContext( $output ),
			$entityIdLookup,
			$labelLookup
		);
		$action->show();
		$this->assertEquals( $expected, $pageTitle );
	}

}
