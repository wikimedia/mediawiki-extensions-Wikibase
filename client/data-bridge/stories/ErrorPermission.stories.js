import ErrorPermission from '@/presentation/components/ErrorPermission';
import useStore from './useStore';

export default {
	title: 'ErrorPermission',
	component: ErrorPermission,
	decorators: [
		useStore( {
			entityTitle: 'Q42',
		} ),
	],
};

export function twoErrors() {
	return {
		components: { ErrorPermission },
		data: () => ( {
			permissionErrors: [
				{
					type: 'protectedpage',
					info: {
						right: 'editprotected',
					},
				},
				{
					type: 'cascadeprotected',
					info: {
						pages: [
							'Important Page',
							'Super Duper Important Page',
						],
					},
				},
			],
		} ),
		template: '<ErrorPermission :permissionErrors="permissionErrors" />',
	};
}

export function oneError() {
	return {
		components: { ErrorPermission },
		data: () => ( {
			permissionErrors: [
				{
					type: 'protectedpage',
					info: {
						right: 'editprotected',
					},
				},
			],
		} ),
		template: '<ErrorPermission :permissionErrors="permissionErrors" />',
	};
}
