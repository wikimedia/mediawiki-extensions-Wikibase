import { storiesOf } from '@storybook/vue';
import ErrorPermission from '@/presentation/components/ErrorPermission';
import useStore from './useStore';

storiesOf( 'ErrorPermission', module )
	.addParameters( { component: ErrorPermission } )
	.addDecorator( useStore( {
		entityTitle: 'Q42',
	} ) )
	.add( 'two errors', () => ( {
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
	} ) )
	.add( 'one error', () => ( {
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
	} ) );
