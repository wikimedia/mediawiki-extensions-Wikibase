import { Module } from 'vuex';
import Application from '@/store/Application';
import EntityState from '@/store/entity/EntityState';
import { mutations } from '@/store/entity/mutations';
import { getters } from '@/store/entity/getters';
import actions from '@/store/entity/actions';
import EntityRepository from '@/definitions/data-access/EntityRepository';
import createStatements from '@/store/entity/statements';
import {
	NS_STATEMENTS,
} from '@/store/namespaces';

export default function ( entityRepository: EntityRepository ): Module<EntityState, Application> {
	const state: EntityState = {
		id: '',
		baseRevision: 0,
	};

	return {
		namespaced: true,
		state,
		getters,
		mutations,
		actions: actions( entityRepository ),
		modules: {
			[ NS_STATEMENTS ]: createStatements(),
		},
	};
}
