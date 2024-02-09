<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Wikibase\Repo\RestApi\Infrastructure\ValidatingRequestDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class TestValidatingRequestDeserializer extends ValidatingRequestDeserializer {

	public const ALLOWED_TAGS = [ 'allowed', 'also-allowed' ];
	public const EXISTING_STRING_PROPERTY = 'P123';
	public const ALLOWED_SITE_IDS = [ 'enwiki', 'dewiki', 'arwiki' ];
	public const ALLOWED_BADGES = [ 'Q777', 'Q888', 'Q999' ];
	public const INVALID_TITLE_REGEX = '/\?/';

	public function __construct() {
		parent::__construct( new TestValidatingRequestDeserializerServiceContainer() );
	}

}
