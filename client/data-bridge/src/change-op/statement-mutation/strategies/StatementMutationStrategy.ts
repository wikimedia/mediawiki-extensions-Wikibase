import DataValue from '@/datamodel/DataValue';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
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
