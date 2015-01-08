<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityRevision;
use Wikibase\Repo\View\EntityView;

/**
 * @covers Wikibase\Repo\View\EntityView
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
abstract class EntityViewTest extends \MediaWikiLangTestCase {

	/**
	 * @param EntityId $id
	 * @param Statement[] $statements
	 *
	 * @return Entity
	 */
	protected abstract function makeEntity( EntityId $id, array $statements = array() );

	/**
	 * Generates a prefixed entity ID based on a numeric ID.
	 *
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 */
	protected abstract function makeEntityId( $numericId );

	/**
	 * @param Statement[] $statements
	 *
	 * @return EntityRevision
	 */
	protected function newEntityRevisionForStatements( array $statements ) {
		static $revId = 1234;
		$revId++;

		$entity = $this->makeEntity( $this->makeEntityId( $revId ), $statements );

		$timestamp = wfTimestamp( TS_MW );
		$revision = new EntityRevision( $entity, $revId, $timestamp );

		return $revision;
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtml(
		EntityView $view,
		EntityRevision $entityRevision,
		array $entityInfo,
		$editable,
		$regexp
	) {
		$output = $view->getHtml( $entityRevision, $entityInfo, $editable );
		$this->assertRegexp( $regexp, $output );

		$entityId = $entityRevision->getEntity()->getId()->getSerialization();
		$this->assertRegExp( '/id="wb-[a-z]+-' . $entityId . '"/', $output );
	}

	public abstract function provideTestGetHtml();

}
