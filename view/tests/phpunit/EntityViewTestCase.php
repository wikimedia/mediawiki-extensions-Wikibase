<?php

namespace Wikibase\View\Tests;

use MediaWikiTestCaseTrait;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\View\EntityView
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
abstract class EntityViewTestCase extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @param EntityId $id
	 * @param Statement[] $statements
	 *
	 * @return EntityDocument
	 */
	abstract protected static function makeEntity( EntityId $id, array $statements = [] );

	/**
	 * Generates a prefixed entity ID based on a numeric ID.
	 *
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 */
	abstract protected static function makeEntityId( $numericId );

	/**
	 * @param Statement[] $statements
	 *
	 * @return EntityDocument
	 */
	protected static function newEntityForStatements( array $statements ) {
		static $revId = 1234;
		$revId++;

		$entity = static::makeEntity( static::makeEntityId( $revId ), $statements );

		return $entity;
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtml(
		callable $viewFactory,
		EntityDocument $entity,
		$regexp,
		$revision = null
	) {
		$view = $viewFactory( $this );
		$output = $view->getContent( $entity, $revision );

		$this->assertSame( [], $output->getPlaceholders() );

		$html = $output->getHtml();
		$this->assertMatchesRegularExpression( $regexp, $html );

		$entityId = $entity->getId()->getSerialization();
		$this->assertMatchesRegularExpression( '/id="wb-[a-z]+-' . $entityId . '"/', $html );
		$this->assertStringContainsString( '<div id="toc"></div>', $html );
	}

	abstract public static function provideTestGetHtml();

}
