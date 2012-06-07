<?php

namespace Wikibase\Test;
use Wikibase\ChangeNotifier as ChangeNotifier;
use Wikibase\Change as Change;
use Wikibase\Changes as Changes;
use Wikibase\ItemChange as ItemChange;

/**
 * Tests for the Wikibase\ChangeNotifier class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseChange
 * @group WikibaseChangeNotifier
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeNotifierTest extends \MediaWikiTestCase {

	public function tearDown() {
		parent::tearDown();

		// Make sure that any open transactions get closed before running new tests.
		ChangeNotifier::singleton()->commit();
	}

	public function testSingleton() {
		$this->assertInstanceOf( '\Wikibase\ChangeNotifier', ChangeNotifier::singleton() );
		$this->assertTrue( ChangeNotifier::singleton() === ChangeNotifier::singleton() );
	}

	public function testBegin() {
		$this->assertFalse( ChangeNotifier::singleton()->isInTranscation() );
		ChangeNotifier::singleton()->begin();
		$this->assertTrue( ChangeNotifier::singleton()->isInTranscation() );
	}

	public function testCommit() {
		$this->assertFalse( ChangeNotifier::singleton()->isInTranscation() );

		$result = ChangeNotifier::singleton()->commit();
		$this->assertInstanceOf( '\Status', $result );
		$this->assertFalse( ChangeNotifier::singleton()->isInTranscation() );

		ChangeNotifier::singleton()->begin();
		ChangeNotifier::singleton()->commit();
		$this->assertFalse( ChangeNotifier::singleton()->isInTranscation() );
	}

	public function testHandleChange() {
		$changeNotifier = ChangeNotifier::singleton();

		$change = new ItemChange( Changes::singleton() );

		$result = $changeNotifier->handleChange( $change );

		$this->assertInstanceOf( '\Status', $result );
		$this->assertTrue( $result->isGood() );

		$change = new ItemChange( Changes::singleton() );

		$changeNotifier->begin();

		$result = $changeNotifier->handleChange( $change );

		$this->assertInstanceOf( '\Status', $result );
		$this->assertTrue( $result->isGood() );

		$result = $changeNotifier->commit();

		$this->assertInstanceOf( '\Status', $result );
		$this->assertTrue( $result->isGood() );
	}

	public function testHandleChanges() {
		$result = ChangeNotifier::singleton()->handleChanges( array(
			new ItemChange( Changes::singleton() ),
			new ItemChange( Changes::singleton() )
		) );

		$this->assertInstanceOf( '\Status', $result );
		$this->assertTrue( $result->isGood() );
	}

}