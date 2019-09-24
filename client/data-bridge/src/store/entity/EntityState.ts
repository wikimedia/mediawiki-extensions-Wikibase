import EntityId from '@/datamodel/EntityId';
import StatementState from '@/store/entity/statements/StatementsState';
import { NS_STATEMENTS } from '@//store/namespaces';

export default interface EntityState {
	id: EntityId;
	baseRevision: number;
}

export interface InitializedEntityState extends EntityState {
	[ NS_STATEMENTS ]: StatementState;
}
