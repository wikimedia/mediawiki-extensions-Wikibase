<?php

namespace Wikibase\Api;

use ApiBase;
use MWException;

use Wikibase\EntityContent;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\Statement;
use Wikibase\Settings;
use Wikibase\Summary;

use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * API module for setting the rank of a statement
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
 * @since 0.3
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetStatementRank extends \Wikibase\ApiModifyClaim {

	// TODO: automcomment
	// TODO: example
	// TODO: rights
	// TODO: conflict detection

	public function __construct( $mainModule, $moduleName, $modulePrefix = '' ) {
		//NOTE: need to declare this constructor, so old PHP versions don't use the
		//      setStatementRank() function as the constructor.
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$content = $this->getEntityContentForClaim( $params['statement'] );

		$statement = $this->setStatementRank(
			$content->getEntity(),
			$params['statement'],
			$params['rank']
		);

		$this->saveChanges( $content );

		$this->outputStatement( $statement );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param string $statementGuid
	 * @param string $rank
	 *
	 * @return Statement
	 */
	protected function setStatementRank( Entity $entity, $statementGuid, $rank ) {
		$claims = new \Wikibase\Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $statementGuid ) ) {
			$this->dieUsage( 'No such statement', 'setstatementrank-statement-not-found' );
		}

		$statement = $claims->getClaimWithGuid( $statementGuid );

		if ( ! ( $statement instanceof Statement ) ) {
			$this->dieUsage(
				'The referenced claim is not a statement and thus does not have a rank',
				'setstatementrank-not-a-statement'
			);
		}

		$statement->setRank( ClaimSerializer::unserializeRank( $rank ) );

		$entity->setClaims( $claims );

		return $statement;
	}

	/**
	 * @see  ApiSummary::getTextForComment()
	 */
	public function getTextForComment( array $params, $plural = 1 ) {
		return Summary::formatAutoComment(
			$this->getModuleName(),
			array( 1 )
		);
	}

	/**
	 * @see  ApiSummary::getTextForSummary()
	 */
	public function getTextForSummary( array $params ) {
		return Summary::formatAutoSummary(
			Summary::pickValuesFromParams( $params, 'statement' )
		);
	}

	/**
	 * @since 0.3
	 *
	 * @param Statement $statement
	 */
	protected function outputStatement( Statement $statement ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $statement );

		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'statement',
			$serializer->getSerialized( $statement )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'statement' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'rank' => array(
				ApiBase::PARAM_TYPE => ClaimSerializer::getRanks(),
				ApiBase::PARAM_REQUIRED => true,
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'statement' => 'A GUID identifying the statement for which to set the rank',
			'rank' => 'The new value to set for the rank',
		) );
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for setting the rank of a Wikibase statement.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			// TODO
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetstatementrank';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

	/**
	 * @see \ApiBase::needsToken()
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
	}

	/**
	 * @see \ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
