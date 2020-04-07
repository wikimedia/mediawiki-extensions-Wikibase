import { EntityState } from '@/store/entity';

export default function newEntityState( entity: Partial<EntityState> = {} ): EntityState {
	return {
		...{
			id: 'Q1',
			baseRevision: 0,
			statements: null,
		},
		...entity,
	};
}
