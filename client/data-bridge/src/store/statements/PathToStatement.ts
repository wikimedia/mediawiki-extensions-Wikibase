import EntityId from '@/datamodel/EntityId';
import Statement from '@/datamodel/Statement';
import StatementMap from '@/datamodel/StatementMap';

export interface PathToStatement {
	resolveStatement( state: Record<EntityId, StatementMap> ): Statement | null;
}
