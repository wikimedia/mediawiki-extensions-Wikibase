import EntityState from '@/store/entity/EntityState';

export default function newEntityState( entity: any = null ): EntityState {
	let state = {
		id: 'Q1',
		baseRevision: 0,
		statements: null,
	};

	if ( entity !== null ) {
		state = { ...state, ...entity };
	}
	return state;
}
