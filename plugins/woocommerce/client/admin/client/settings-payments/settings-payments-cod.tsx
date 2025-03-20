/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CheckboxControl,
	SelectControl,
	TextControl,
	TextareaControl,
	Button,
} from '@wordpress/components';
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PAYMENT_GATEWAYS_STORE_NAME,
	type PaymentSelectors,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';

/**
 * Component for managing Cash on Delivery payment gateway settings.
 */
export const SettingsPaymentsCod = () => {
	const [ enabled, setEnabled ] = useState( false );
	const [ enableForMethods, setEnableForMethods ] = useState( [] );
	const [ enableForVirtual, setEnableForVirtual ] = useState( false );
	const [ title, setTitle ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ instructions, setInstructions ] = useState( '' );

	const codSettings = useSelect(
		( select ) =>
			(
				select( PAYMENT_GATEWAYS_STORE_NAME ) as PaymentSelectors
			 ).getPaymentGateway( 'cod' ),
		[]
	);
	console.log( codSettings );

	useEffect( () => {
		if ( codSettings ) {
			setEnableForMethods(
				codSettings.settings?.enable_for_methods?.value
					? codSettings.settings.enable_for_methods.value.split( ',' )
					: []
			);
			setEnableForVirtual(
				codSettings.settings?.enable_for_virtual?.value === 'yes'
			);
			setEnabled( codSettings.enabled || false );
			setTitle(
				codSettings.settings?.title?.value || 'Cash on delivery'
			);
			setDescription( codSettings.description || '' );
			setInstructions( codSettings.settings?.instructions?.value || '' );
		}
	}, [ codSettings ] );

	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );

	const saveSettings = () => {
		updatePaymentGateway( 'cod', {
			enabled,
			description,
			settings: {
				title,
				instructions,
				enable_for_methods: enableForMethods.join( ',' ),
				enable_for_virtual: enableForVirtual ? 'yes' : 'no',
			},
		} )
			.then( () => {
				console.log( 'Settings updated successfully' );
				window.location.reload();
			} )
			.catch( ( error ) => {
				console.error( 'Error updating settings:', error );
			} );
	};

	if ( ! codSettings ) {
		return <p>Loading settings...</p>;
	}

	return (
		<PaymentSettingsLayout>
			<PaymentSettingsSection
				id={ 'cheque-settings' }
				title={ __( 'Enable and customise', 'woocommerce' ) }
				description={ __(
					'Choose how you want to present cheque payments to your customers during checkout.',
					'woocommerce'
				) }
			>
				<Card className="payment-settings-card__wrapper cheque-settings__wrapper">
					<CardBody className="bacs-settings__body">
						<div className={ 'form-field' }>
							<CheckboxControl
								onChange={ ( value ) => setEnabled( value ) }
								checked={ enabled }
								// @ts-expect-error The label prop can be a string, however, the final consumer of this prop accepts ReactNode.
								label={
									<span>
										{ __(
											'Enable cash on delivery',
											'woocommerce'
										) }
									</span>
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<TextControl
								label={ __( 'Title', 'woocommerce' ) }
								help={ __(
									'This controls the title which the user sees during checkout.',
									'woocommerce'
								) }
								value={ title }
								onChange={ ( value ) => setTitle( value ) }
							/>
						</div>

						<div className={ 'form-field' }>
							<TextareaControl
								label={ __( 'Description', 'woocommerce' ) }
								help={ __(
									'Payment method description that the customer will see on your checkout.',
									'woocommerce'
								) }
								value={ description }
								onChange={ ( value ) =>
									setDescription( value )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<TextareaControl
								label={ __( 'Instructions', 'woocommerce' ) }
								help={ __(
									'Instructions that will be added to the thank you page and emails.',
									'woocommerce'
								) }
								value={ instructions }
								onChange={ ( value ) =>
									setInstructions( value )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<SelectControl
								label={ __(
									'Enable for shipping methods',
									'woocommerce'
								) }
								help={ __(
									'Select which shipping methods cash on delivery is only available for. Leave blank to enable for all methods.',
									'woocommerce'
								) }
								value={ enableForMethods }
								multiple
								options={ Object.entries(
									codSettings.settings?.enable_for_methods
										?.options || {}
								).flatMap( ( [ groupLabel, groupOptions ] ) =>
									Object.entries( groupOptions ).map(
										( [ value, label ] ) => ( {
											label: label.replace(
												/&quot;/g,
												'"'
											), // Convert HTML entities to human-readable quotes
											value,
										} )
									)
								) }
								onChange={ ( selected ) =>
									setEnableForMethods( selected )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<CheckboxControl
								onChange={ ( value ) =>
									setEnableForVirtual( value )
								}
								checked={ enableForVirtual }
								help={ __(
									'Accept cash on delivery if the order is virtual',
									'woocommerce'
								) }
								// @ts-expect-error The label prop can be a string, however, the final consumer of this prop accepts ReactNode.
								label={
									<span>
										{ __(
											'Accept for virtual orders',
											'woocommerce'
										) }
									</span>
								}
							/>
						</div>
					</CardBody>
				</Card>
			</PaymentSettingsSection>
			<Card className={ 'payment-settings-card__wrapper ' }>
				<CardBody className={ 'form__actions' }>
					<Button variant={ 'primary' } onClick={ saveSettings }>
						{ __( 'Save changes', 'woocommerce' ) }
					</Button>
				</CardBody>
			</Card>
		</PaymentSettingsLayout>
	);
};

export default SettingsPaymentsCod;
