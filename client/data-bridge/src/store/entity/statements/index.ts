import { Module } from 'vuex';
import Application from '@/store/Application';
import StatementsState from '@/store/entity/statements/StatementsState';

export default function (): Module<StatementsState, Application> {
	const state: StatementsState = {};

	return {
		namespaced: true,
		state,
	};
}
