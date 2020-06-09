import {
	Statement,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';

export interface PathToStatement {
	resolveStatement( state: Record<EntityId, StatementMap> ): Statement | null;
}
