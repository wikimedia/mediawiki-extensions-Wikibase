<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikimedia\Assert\Assert;

/**
 * TODO: add class description
 *
 * @license GPL-2.0+
 */
class SiteLinksChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * TODO: the original implementation can hopefully be cleaned up a bit
	 * NOTE: this is a trickier one since it is very intermingled with EditEntity/ModifyEntity and
	 *       it needs to know about the Item
	 *
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		Assert::parameterType( 'array', $changeRequest['sitelinks'], '$changeRequest[\'sitelinks\']' );
	}

}
