import { storiesOf } from '@storybook/vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
storiesOf( 'IconMessageBox', module )
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
					Just to inform you... Just to inform you... Just to inform you...Just to inform you... Just to inform you...Just to inform you... Just to inform you... Just to inform you...Just to inform you...Just to inform you...Just to inform you... Just to inform you... Just to inform you...Just to inform you...Just to inform you...Just to inform you... Just to inform you... Just to inform you...Just to inform you... Just to inform you...Just to inform you...Just to inform you... Just to inform you...Just to inform you... Just to inform you...Just to inform you... Just to inform you... Just to inform you...Just to inform you... Just to inform you...Just to inform you... Just to inform you... Just to inform you...Just to inform you... Just to inform you...Just to inform you... Just to inform you...  Just to inform you...Just to inform you... Just to inform you...Just to inform you...Just to inform you... 
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
