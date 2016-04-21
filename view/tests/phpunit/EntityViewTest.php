<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\EntityView;

/**
 * @covers Wikibase\View\EntityView
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
abstract class EntityViewTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param EntityId $id
	 * @param Statement[] $statements
	 *
	 * @return EntityDocument
	 */
	abstract protected function makeEntity( EntityId $id, array $statements = [] );

	/**
	 * Generates a prefixed entity ID based on a numeric ID.
	 *
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 */
	abstract protected function makeEntityId( $numericId );

	/**
	 * @param Statement[] $statements
	 *
	 * @return EntityDocument
	 */
	protected function newEntityForStatements( array $statements ) {
		static $revId = 1234;
		$revId++;

		$entity = $this->makeEntity( $this->makeEntityId( $revId ), $statements );

		return $entity;
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtml(
		EntityView $view,
		EntityDocument $entity,
		$regexp
	) {
		$output = $view->getHtml( $entity );
		$this->assertRegexp( $regexp, $output );

		$entityId = $entity->getId()->getSerialization();
		$this->assertRegExp( '/id="wb-[a-z]+-' . $entityId . '"/', $output );
		$this->assertContains( '<div id="toc"></div>', $output );
	}

	abstract public function provideTestGetHtml();

}
