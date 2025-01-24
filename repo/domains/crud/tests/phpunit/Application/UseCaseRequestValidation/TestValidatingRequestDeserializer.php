<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\Repo\Domains\Crud\Infrastructure\ValidatingRequestDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class TestValidatingRequestDeserializer extends ValidatingRequestDeserializer {

	public const ALLOWED_TAGS = [ 'test tag', 'another-tag' ];
	public const EXISTING_STRING_PROPERTY = 'P3975';
	public const ALLOWED_SITE_IDS = [ 'enwiki-siteid', 'dewiki-siteid', 'arwiki-siteid' ];
	public const ALLOWED_BADGES = [ 'Q777', 'Q888', 'Q999' ];
	public const INVALID_TITLE_REGEX = '/\?/';

	public function __construct() {
		parent::__construct( new TestValidatingRequestDeserializerServiceContainer() );
	}

}
