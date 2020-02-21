import DataValue from '@/datamodel/DataValue';
import { StatementState } from '@/store/statements';
import { MainSnakPath } from '@/store/statements/MainSnakPath';

export default interface StatementMutationStrategy {
	apply(
		targetValue: DataValue,
		path: MainSnakPath,
		statementState: StatementState,
	): StatementState;
}
