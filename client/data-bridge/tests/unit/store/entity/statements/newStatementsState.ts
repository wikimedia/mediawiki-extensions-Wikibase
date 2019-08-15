import StatementsState from '@/store/entity/statements/StatementsState';
import StatementMap from '@/datamodel/StatementMap';

export default function newStatementsState(
	statements?: { [ key: string ]: StatementMap },
): StatementsState {
	const state: StatementsState = {};
	if ( statements ) {
		Object.assign( state, statements );
	}

	return state;
}
