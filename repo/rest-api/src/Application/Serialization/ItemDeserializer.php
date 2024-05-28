<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\DuplicateAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;

/**
 * @license GPL-2.0-or-later
 */
class ItemDeserializer {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private StatementDeserializer $statementDeserializer;
	private SitelinkDeserializer $sitelinkDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		StatementDeserializer $statementDeserializer,
		SitelinkDeserializer $sitelinkDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->statementDeserializer = $statementDeserializer;
		$this->sitelinkDeserializer = $sitelinkDeserializer;
	}

	/**
	 * @throws BadgeNotAllowed
	 * @throws DuplicateAliasException
	 * @throws EmptyLabelException
	 * @throws EmptyDescriptionException
	 * @throws EmptyAliasException
	 * @throws EmptySitelinkException
	 * @throws InvalidLabelException
	 * @throws InvalidDescriptionException
	 * @throws InvalidAliasesInLanguageException
	 * @throws InvalidSitelinkBadgeException
	 * @throws InvalidFieldException
	 * @throws InvalidFieldTypeException
	 * @throws MissingFieldException
	 * @throws SitelinkTargetNotFound
	 */
	public function deserialize( array $serialization ): Item {
		return new Item(
			isset( $serialization[ 'id' ] ) ? new ItemId( $serialization[ 'id' ] ) : null,
			new Fingerprint(
				$this->labelsDeserializer->deserialize( (array)( $serialization[ 'labels' ] ?? [] ) ),
				$this->descriptionsDeserializer->deserialize( (array)( $serialization[ 'descriptions' ] ?? [] ) ),
				$this->aliasesDeserializer->deserialize( (array)( $serialization[ 'aliases' ] ?? [] ) )
			),
			$this->deserializeSitelinks( (array)( $serialization[ 'sitelinks' ] ?? [] ) ),
			$this->deserializeStatements( (array)( $serialization[ 'statements' ] ?? [] ) )
		);
	}

	private function deserializeStatements( array $statementsSerialization ): StatementList {
		$statementList = [];
		foreach ( $statementsSerialization as $statementGroups ) {
			foreach ( $statementGroups as $statement ) {
				$statementList[] = $this->statementDeserializer->deserialize( $statement );
			}
		}

		return new StatementList( ...$statementList );
	}

	private function deserializeSitelinks( array $sitelinksSerialization ): SiteLinkList {
		$sitelinkList = [];
		foreach ( $sitelinksSerialization as $siteId => $sitelink ) {
			$sitelinkList[] = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink );
		}

		return new SiteLinkList( $sitelinkList );
	}

}
