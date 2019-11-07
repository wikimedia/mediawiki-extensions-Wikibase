import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import {
	STATEMENTS_CONTAINS_ENTITY,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import StatementsState from '@/store/entity/statements/StatementsState';
import EntityId from '@/datamodel/EntityId';

export const statementGetters: GetterTree<StatementsState, Application> = {
	[ STATEMENTS_CONTAINS_ENTITY ]: ( state: StatementsState ) => ( entityId: EntityId ): boolean => {
		return state[ entityId ] !== undefined;
	},

	[ STATEMENTS_PROPERTY_EXISTS ]: ( state: StatementsState ) => (
		entityId: EntityId,
		propertyId: EntityId,
	): boolean => {
		return state[ entityId ] !== undefined
			&& state[ entityId ][ propertyId ] !== undefined;
	},

	[ STATEMENTS_IS_AMBIGUOUS ]: ( state: StatementsState ) => (
		entityId: EntityId,
		propertyId: EntityId,
	): boolean => {
		return state[ entityId ] !== undefined
			&& state[ entityId ][ propertyId ] !== undefined
			&& state[ entityId ][ propertyId ].length > 1;
	},
};
