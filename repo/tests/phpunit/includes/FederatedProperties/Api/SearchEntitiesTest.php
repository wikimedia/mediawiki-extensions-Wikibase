<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

/**
 * @covers \Wikibase\Repo\Api\SearchEntities
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class SearchEntitiesTest extends FederatedPropertiesApiTestCase {

	public function testFederatedPropertiesFailure() {
		$this->setSourceWikiUnavailable();

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-search-api-error-message' ) );
		$this->doApiRequestWithToken( $params = [
			'action' => 'wbsearchentities',
			'search' => 'Foo',
			'type' => 'property',
			'language' => 'sv',
			'strictlanguage' => true
		] );
	}

}
