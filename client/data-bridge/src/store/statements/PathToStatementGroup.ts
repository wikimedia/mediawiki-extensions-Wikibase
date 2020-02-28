import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import Statement from '@/datamodel/Statement';

export interface PathToStatementGroup {
	resolveStatementGroup( state: Record<EntityId, StatementMap> ): Statement[] | null;
}
