/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/breadcrumbs',
	name: 'Store Breadcrumbs',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( "block can't be inserted in Post Editor", async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );

		await expect(
			editor.canvas.getByText(
				`Your site doesn’t include support for the "${ blockData.slug }" block`
			)
		).toBeVisible();
	} );

	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		editor,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'sorter',
			title: 'Sorter',
			content: 'howdy',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'howdy' ) ).toBeVisible();

		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editor.getBlockByName( blockData.slug );

		await expect( block ).toHaveText( 'Breadcrumbs / Navigation / Path' );
	} );
} );
