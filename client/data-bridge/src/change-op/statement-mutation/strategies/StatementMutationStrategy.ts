import {
	DataValue,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';
import { PathToStatement } from '@/store/statements/PathToStatement';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { PathToStatementGroup } from '@/store/statements/PathToStatementGroup';

export default interface StatementMutationStrategy {
	apply<T extends Record<EntityId, StatementMap>>(
		targetValue: DataValue,
		path: PathToStatement & PathToSnak & PathToStatementGroup,
		state: T,
	): T;
}
