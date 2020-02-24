import EntityId from '@/datamodel/EntityId';
import Snak from '@/datamodel/Snak';
import StatementMap from '@/datamodel/StatementMap';

export interface PathToSnak {
	resolveSnakInStatement( state: Record<EntityId, StatementMap> ): Snak | null;
}
