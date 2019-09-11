import StatementState from '@/store/entity/statements/StatementsState';
import { NS_STATEMENTS } from '@//store/namespaces';

export default interface EntityState {
	id: string;
	baseRevision: number;
}

export interface InitializedEntityState extends EntityState {
	[ NS_STATEMENTS ]: StatementState;
}
