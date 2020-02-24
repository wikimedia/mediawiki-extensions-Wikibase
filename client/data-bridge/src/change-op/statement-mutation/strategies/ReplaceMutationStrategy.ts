import { PathToSnak } from '@/store/statements/PathToSnak';
import DataValue from '@/datamodel/DataValue';
import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
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
			throw new Error( SnakActionErrors.NO_SNAK_FOUND );
		}

		if ( targetValue.type !== 'string' ) {
			throw new Error( SnakActionErrors.WRONG_PAYLOAD_TYPE );
		}

		if ( typeof targetValue.value !== 'string' ) {
			throw new Error( SnakActionErrors.WRONG_PAYLOAD_VALUE_TYPE );
		}

		snak.snaktype = 'value';

		snak.datavalue = targetValue;

		return state;
	}
}
