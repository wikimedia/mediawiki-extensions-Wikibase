import { SET_ALL_TAINTED } from '@/store/mutationTypes';
import actions from '@/store/actions';
import { STATEMENT_TAINTED_STATE_INIT } from '@/store/actionTypes';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';

describe( 'actions', () => {
	it( `commits to ${SET_ALL_TAINTED}`, async () => {
		const context = newMockStore( {} );
		const payload = { guids: [ 'foo' ] };
		await ( actions as Function )()[ STATEMENT_TAINTED_STATE_INIT ]( context, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_TAINTED, payload );
	} );
} );
