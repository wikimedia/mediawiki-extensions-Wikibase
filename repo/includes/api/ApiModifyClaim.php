<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * Base module for handling claims.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ApiModifyClaim extends Api implements ApiAutocomment {

	/**
	 * @since 0.4
	 *
	 * @param EntityContent $content The content to save
	 * @param int           $flags   Edit flags, e.g. EDIT_NEW
	 * @param string|null   $summary The summary to set. If null, the summary will be auto-generated.
	 *
	 * @return void
	 */
	protected function saveChanges( EntityContent $content, $summary = null ) {
		$params = $this->extractRequestParams();

		if ( $summary === null ) {
			$summary = Autocomment::buildApiSummary( $this, $params, $content );
		}

		$user = $this->getUser();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;

		$flags = EDIT_UPDATE;
		$flags |= ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

		$editEntity = new EditEntity( $content, $user, $baseRevisionId, $this->getContext() );

		$status = $editEntity->attemptSave(
			$summary,
			$flags,
			isset( $params['token'] ) ? $params['token'] : ''
		);

		if ( !$status->isOK() ) {
			$this->dieUsage( $status->getHTML( 'wikibase-api-save-failed' ), 'save-failed' );
		}

		$statusValue = $status->getValue();

		if ( isset( $statusValue['revision'] ) ) {
			$this->getResult()->addValue(
				'pageinfo',
				'lastrevid',
				(int)$statusValue['revision']->getId()
			);
		}
	}

	/**
	 * Checks if the required parameters are set and are valid and consistent.
	 *
	 * @since 0.2
	 */
	protected function checkParameterRequirements() {
		// noop
	}

	/**
	 * @since 0.2
	 *
	 * @return Snak
	 * @throws MWException
	 */
	protected function getSnakInstance() {
		$params = $this->extractRequestParams();

		$factory = new SnakFactory();

		return $factory->newSnak(
			$this->getPropertyId(),
			$params['snaktype'],
			isset( $params['value'] ) ? \FormatJson::decode( $params['value'], true ) : null
		);
	}

	/**
	 * @return EntityId
	 */
	protected function getPropertyId() {
		$params = $this->extractRequestParams();

		$libRegistry = new LibRegistry( Settings::singleton() );
		$parseResult = $libRegistry->getEntityIdParser()->parse( $params['property'] );

		if ( !$parseResult->isValid() ) {
			$this->dieUsage( $parseResult->getError()->getText(), 'illegal-property-id' );
		}

		return $parseResult->getValue();
	}

	/**
	 * @since 0.3
	 *
	 * @param Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();

		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializer->getSerialized( $claim )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => null,
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
