/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
const SidebarSettings = ( { RichTextWithButton } ) => {
	return (
		<>
			<RichTextWithButton
				attributeName="subject"
				attributeValue="Value"
				updateProperty={() => console.log('updateProperty')}
				label={__('Subject', 'woocommerce')}
				help="Help"
				placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
			/>
		</>
	);
};

export function modifySidebar() {
	addFilter(
		'woocommerce_email_editor_setting_sidebar_extension_component',
		NAME_SPACE,
		( RichTextWithButton ) => {
			return () => (
				<SidebarSettings RichTextWithButton={RichTextWithButton} />
			);
		}
	);
}
