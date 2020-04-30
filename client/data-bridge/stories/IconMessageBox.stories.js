import { storiesOf } from '@storybook/vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
storiesOf( 'IconMessageBox', module )
	.addParameters( { component: IconMessageBox } )
	.add( 'error', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="error">
					Something went wrong!
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'error long', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="error">
					{{ new Array( 42 ).fill( 'Something went wrong!' ).join( ' ' ) }}
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'error inline', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="error" :inline="true">
					Something went wrong!
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'warning', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="warning">
					I think you ought to know...
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'warning long', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="warning">
					{{ new Array( 42 ).fill( 'I think you ought to know...' ).join( ' ' ) }}
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'warning inline', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="warning" :inline="true">
					I think you ought to know...
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'notice', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="notice">
					Just to inform you...
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'notice long', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="notice">
					{{ new Array( 42 ).fill( 'Just to inform you…' ).join( ' ' ) }}
				</IconMessageBox>
			</div>`,
	} ) )
	.add( 'notice inline', () => ( {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="notice" :inline="true">
					Just to inform you...
				</IconMessageBox>
			</div>`,
	} ) );
