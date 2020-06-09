import {
	Snak,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';

export interface PathToSnak {
	resolveSnakInStatement( state: Record<EntityId, StatementMap> ): Snak | null;
}
