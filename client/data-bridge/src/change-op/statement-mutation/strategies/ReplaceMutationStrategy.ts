import { PathToSnak } from '@/store/statements/PathToSnak';
import DataValue from '@/datamodel/DataValue';
import { StatementState } from '@/store/statements';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';
import StatementMutationStrategy from './StatementMutationStrategy';

export default class ReplaceMutationStrategy implements StatementMutationStrategy {
	public apply(
		targetValue: DataValue,
		path: PathToSnak,
		state: StatementState,
	): StatementState {
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
