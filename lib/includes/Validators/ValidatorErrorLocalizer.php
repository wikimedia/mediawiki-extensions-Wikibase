<?php
 /**
 *
 * Copyright © 20.06.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Validators;


use ValueValidators\Error;

/**
 * Class ValidatorErrorLocalizer
 * @package Wikibase\Validators
 */
class ValidatorErrorLocalizer {

	public function getErrorMessage( Error $error ) {
		// Give grep a chance to find the usages:
		// wikibase-validator-bad-type, wikibase-validator-too-long, wikibase-validator-too-short,
		// wikibase-validator-malformed-value, wikibase-validator-bad-entity-id,
		// wikibase-validator-bad-entity-type, wikibase-validator-no-such-entity,
		// wikibase-validator-no-such-property, wikibase-validator-bad-value,
		// wikibase-validator-bad-value-type
		$key = 'wikibase-validator-' . $error->getCode();
		$params = $error->getParameters();

		//TODO: look for non-string in $params and run them through an appropriate formatter

		return wfMessage( $key, $params );
	}
}