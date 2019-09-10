import EntityId from '@/datamodel/EntityId';

interface WikibaseEntityId {
	'entity-type': string;
	'numeric-id'?: number;
	id: EntityId;
}

export default WikibaseEntityId;
