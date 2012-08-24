<?php

namespace Wikibase\Test;
use Wikibase\EntityUpdateHandler as EntityUpdateHandler;

/**
 * Tests for the Wikibase\EntityUpdateHandler implementing classes.
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
class EntityUpdateHandlerTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( \Wikibase\StoreFactory::getStore( 'sqlstore' )->newEntityDeletionHandler() );

		return $this->arrayWrap( $instances );
	}



}
