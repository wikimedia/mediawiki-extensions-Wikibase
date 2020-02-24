import DataValue from '@/datamodel/DataValue';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import { MainSnakPath } from '@/store/statements/MainSnakPath';

export default interface StatementMutationStrategy {
	apply<T extends Record<EntityId, StatementMap>>(
		targetValue: DataValue,
		path: MainSnakPath,
		state: T,
	): T;
}
