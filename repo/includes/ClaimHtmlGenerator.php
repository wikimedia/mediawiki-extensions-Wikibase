<?php

namespace Wikibase;

use Html;
use Language;
use MWException;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * Base class for generating the HTML for a Claim in Entity View.
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
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 */
class ClaimHtmlGenerator {

	/**
	 * @since 0.4
	 *
	 * @var SnakFormatter
	 */
	protected $snakFormatter;

	/**
	 * Constructor.
	 *
	 * @param SnakFormatter $snakFormatter
	 */
	public function __construct( SnakFormatter $snakFormatter ) {
		$this->snakFormatter = $snakFormatter;
	}

	/**
	 * Returns the Html for the main Snak.
	 *
	 * @param DataValue $value
	 * @return string
	 */
	protected function getMainSnakHtml( $value ) {
		$mainSnakHtml = wfTemplate( 'wb-snak',
			'wb-mainsnak',
			'', // Link to property. NOTE: we don't display this ever (instead, we generate it on
				// Claim group level) If this was a public function, this should be generated
				// anyhow since important when displaying a Claim on its own.
			'', // type selector, JS only
			( $value === '' ) ? '&nbsp;' : $value
		);

		return $mainSnakHtml;
	}

	/**
	 * Builds and returns the HTML representing a single WikibaseEntity's claim.
	 *
	 * @since 0.4
	 *
	 * @param EntityContent $entity the entity related to the claim
	 * @param Claim $claim the claim to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local
	 *		context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @param editSectionHtml has the html for the edit section
	 * @return string
	 */
	public function getHtmlForClaim(
		Claim $claim,
		$editSectionHtml = null
	) {
		wfProfileIn( __METHOD__ );

		try {
			$snakValueHtml = $this->snakFormatter->formatSnak( $claim->getMainSnak() );
		} catch ( FormattingException $ex ) {
			$snakValueHtml = '?'; // XXX: perhaps show error message?
		} catch ( PropertyNotFoundException $ex ) {
			$snakValueHtml = '?'; // XXX: perhaps show error message?
		}

		$mainSnakHtml = $this->getMainSnakHtml( $snakValueHtml );

		// @todo: Use 'wb-claim' or 'wb-statement' template accordingly
		// @todo: get rid of usage of global wfTemplate function
		$claimHtml = wfTemplate( 'wb-statement',
			'', // additional classes
			$claim->getGuid(),
			$mainSnakHtml,
			'', // TODO: Qualifiers
			$editSectionHtml,
			'', // TODO: References heading
			'' // TODO: References
		);

		wfProfileOut( __METHOD__ );
		return $claimHtml;
	}
}
