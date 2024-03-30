<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;

/**
 * @license GPL-2.0-or-later
 */
class ItemDeserializer {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private SitelinksDeserializer $sitelinksDeserializer;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		SitelinksDeserializer $sitelinksDeserializer,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->sitelinksDeserializer = $sitelinksDeserializer;
		$this->statementsDeserializer = $statementsDeserializer;
	}

	/**
	 * @throws InvalidFieldException
	 * @throws InvalidLabelException
	 * @throws UnexpectedFieldException
	 * @throws EmptyLabelException
	 * @throws EmptyDescriptionException
	 * @throws EmptyAliasException
	 * @throws DuplicateAliasException
	 * @throws MissingFieldException
	 * @throws InvalidFieldTypeException
	 * @throws EmptySitelinkException
	 * @throws InvalidSitelinkBadgeException
	 * @throws BadgeNotAllowed
	 * @throws SitelinkTargetNotFound
	 */
	public function deserialize( array $serialization ): Item {
		$expectedFields = [ 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ];
		foreach ( $expectedFields as $expectedField ) {
			$serialization[ $expectedField ] ??= [];
			if ( !$this->isArrayWithStringKeys( $serialization[$expectedField] ) ) {
				throw new InvalidFieldException( $expectedField, $serialization[$expectedField] );
			}
		}

		foreach ( array_keys( $serialization ) as $field ) {
			$ignoredFields = [ 'id', 'type' ];
			if ( !in_array( $field, array_merge( $expectedFields, $ignoredFields ) ) ) {
				throw new UnexpectedFieldException( $field );
			}
		}

		return new Item(
			isset( $serialization['id'] ) ? new ItemId( $serialization['id'] ) : null,
			new Fingerprint(
				$this->labelsDeserializer->deserialize( $serialization['labels'] ),
				$this->descriptionsDeserializer->deserialize( $serialization['descriptions'] ),
				$this->aliasesDeserializer->deserialize( $serialization['aliases'] )
			),
			$this->sitelinksDeserializer->deserialize( $serialization['sitelinks'] ),
			$this->statementsDeserializer->deserialize( $serialization['statements'] )
		);
	}

	/**
	 * @param mixed $field
	 */
	private function isArrayWithStringKeys( $field ): bool {
		return is_array( $field ) &&
			   array_keys( $field ) === array_filter( array_keys( $field ), 'is_string' );
	}

}
