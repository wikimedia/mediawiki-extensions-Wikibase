import {
	Statement,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';

export interface PathToStatementGroup {
	resolveStatementGroup( state: Record<EntityId, StatementMap> ): Statement[] | null;
}
