<?php

namespace Wikibase\Test;
use Wikibase\EntityDeletionHandler as EntityDeletionHandler;

/**
 * Tests for the Wikibase\EntityDeletionHandler implementing classes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeletionHandlerTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( \Wikibase\StoreFactory::getStore( 'sqlstore' )->newEntityDeletionHandler() );

		return $this->arrayWrap( $instances );
	}



}
