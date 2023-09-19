<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Wikibase\Repo\RestApi\Infrastructure\ValidatingRequestDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class TestValidatingRequestDeserializer extends ValidatingRequestDeserializer {

	public const ALLOWED_TAGS = [ 'allowed', 'also-allowed' ];
	public const EXISTING_STRING_PROPERTY = 'P123';

	public function __construct() {
		parent::__construct( new TestValidatingRequestDeserializerServiceContainer() );
	}

}
