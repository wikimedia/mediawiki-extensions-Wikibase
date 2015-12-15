<?php

namespace Wikibase\View\Tests;

use MediaWikiLangTestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\View\EntityView;

/**
 * @covers Wikibase\View\EntityView
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Kinzler
 */
abstract class EntityViewTest extends MediaWikiLangTestCase {

	/**
	 * @return EntityView
	 */
	abstract protected function newEntityView();

	/**
	 * @param EntityId $id
	 *
	 * @return EntityDocument
	 */
	abstract protected function makeEntity( EntityId $id );

	/**
	 * @return EntityId
	 */
	abstract protected function getEntityId();

	/**
	 * @param EntityDocument $entity
	 * @param int $revId
	 * @param string $timestamp
	 *
	 * @return EntityRevision
	 */
	protected function newEntityRevision( EntityDocument $entity, $revId = 1234, $timestamp = '20131212000000' ) {
		$revision = new EntityRevision( $entity, $revId, $timestamp );
		return $revision;
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtml(
		EntityRevision $entityRevision,
		array $expectedPatterns
	) {
		$view = $this->newEntityView();
		$output = $view->getHtml( $entityRevision );

		foreach ( $expectedPatterns as $name => $pattern ) {
			$this->assertRegExp( $pattern, $output, $name );
		}
	}

	/**
	 * @return array An array of data sets for use by testGetHtml(). Each data set contains
	 *         two fields: an EntityRevision to show, and an array of regular expressions to check.
	 *         The keys of the regular expression array are used when reporting errors.
	 */
	public function provideTestGetHtml() {
		$id = $this->getEntityId();

		return array(
			'basic DOM parts' => array(
				$this->newEntityRevision( $this->makeEntity( $id ) ),
				array(
					'entity ID in DOM' => '!id="wb-[a-z]+-' . $id->getSerialization() . '"!',
					'TOC' => '!<div id="toc"></div>!',
				)
			),
		);
	}

}
