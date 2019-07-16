import EntityState from '@/store/entity/EntityState';
import lockState from '../lockState';

export default function newEntityState( entity: any = null ): EntityState {
	let state = {
		id: 'Q1',
		baseRevision: 0,
	};

	if ( entity !== null ) {
		state = { ...state, ...entity };
		lockState( state );
	}
	return state;
}
