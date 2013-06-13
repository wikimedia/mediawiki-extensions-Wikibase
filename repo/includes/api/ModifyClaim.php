<?php
namespace Wikibase\Api;

use ApiBase, MWException;
use DataValues\TimeValue;
use InvalidArgumentException;
use UsageException;
use Wikibase\Claim;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\Snak;
use Wikibase\Statement;
use Wikibase\Summary;

/**
 * Base class for modifying claims, with common functionality
 * for creating summaries.
 *
 * @todo decide if this is really needed or not
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
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
abstract class ModifyClaim extends ApiWikibase {

	/**
	 * Create a summary
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 * @param string $action
	 *
	 * @return Summary
	 */
	protected function createSummary( Snak $snak, $action ) {
		if ( !is_string( $action ) ) {
			throw new \MWException( 'action is invalid or unknown type.' );
		}

		$summary = new Summary( $this->getModuleName() );
		$summary->setAction( $action );
		$summary->addAutoSummaryArgs( $snak->getPropertyId(), $snak->getDataValue() );

		return $summary;
	}

	/**
	 * Validates the given Snak.
	 *
	 * @note: This is a NASTY HACK, we shouldn't know about the stuff we are checking here.
	 *        We should be using proper Validators, and proper handling of deserialization failures.
	 *
	 * @param Snak $snak the snak to validate
	 *
	 * @throws \UsageException if the snak isn't valid
	 */
	public static function validateSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$dataValue = $snak->getDataValue();

			if ( $dataValue instanceof TimeValue ) {
				$time = $dataValue->getTime();

				if ( !preg_match( '!^[-+]\d{1,16}-(0\d|1[012])-([012]\d|3[01])T([01]\d|2[0123]):[0-5]\d:([0-5]\d|6[012])Z$!', $time ) ) {
					throw new UsageException( '$time needs to be a valid ISO 8601 date', 'claim-invalid-snak' );
				}
			}
		}
	}

	/**
	 * Validates all Snaks in the given claim.
	 *
	 * @note: This is a NASTY HACK, we shouldn't know about the stuff we are checking here.
	 *        We should be using proper Validators, and proper handling of deserialization failures.
	 *
	 * @param Claim $claim the snak to validate
	 *
	 * @throws \UsageException if the snak isn't valid
	 */
	public static function validateClaimSnaks( Claim $claim ) {
		$snak = $claim->getMainSnak();
		self::validateSnak( $snak );

		foreach ( $claim->getQualifiers() as $snak ) {
			self::validateSnak( $snak );
		}

		if ( $claim instanceof Statement ) {
			/* @var Reference $ref */
			foreach ( $claim->getReferences() as $ref ) {
				foreach ( $ref->getSnaks() as $snak ) {
					self::validateSnak( $snak );
				}
			}
		}
	}
}
