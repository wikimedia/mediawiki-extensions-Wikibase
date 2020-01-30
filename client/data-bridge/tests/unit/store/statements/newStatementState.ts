import { StatementState } from '@/store/statements';
import StatementMap from '@/datamodel/StatementMap';

export default function newStatementState(
	statements?: { [ key: string ]: StatementMap },
): StatementState {
	const state: StatementState = {};
	if ( statements ) {
		Object.assign( state, statements );
	}

	return state;
}
