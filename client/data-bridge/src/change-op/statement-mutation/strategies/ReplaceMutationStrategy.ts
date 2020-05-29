import {
	DataValue,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import StatementMutationError from '@/change-op/statement-mutation/StatementMutationError';
import { PathToSnak } from '@/store/statements/PathToSnak';
import EntityId from '@/datamodel/EntityId';
import StatementMutationStrategy from './StatementMutationStrategy';

export default class ReplaceMutationStrategy implements StatementMutationStrategy {
	public apply<T extends Record<EntityId, StatementMap>>(
		targetValue: DataValue,
		path: PathToSnak,
		state: T,
	): T {
		// TODO other strategies may have similar needs, may move into a shared method (e.g. base class)
		const snak = path.resolveSnakInStatement( state );
		if ( snak === null ) {
			throw new Error( StatementMutationError.NO_SNAK_FOUND );
		}

		if ( snak.datavalue !== undefined && targetValue.type !== snak.datavalue.type ) {
			throw new Error( StatementMutationError.INCONSISTENT_PAYLOAD_TYPE );
		}

		snak.snaktype = 'value';

		snak.datavalue = targetValue;

		return state;
	}
}
