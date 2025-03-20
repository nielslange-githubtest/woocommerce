/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CheckboxControl,
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
import './settings-payments-body.scss';
import './settings-payments-form.scss';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';

/**
 * Component for managing BACS (Direct Bank Transfer) payment gateway settings.
 */
export const SettingsPaymentsBacs = () => {
	const bacsSettings = useSelect(
		( select ) =>
			(
				select( PAYMENT_GATEWAYS_STORE_NAME ) as PaymentSelectors
			 ).getPaymentGateway( 'bacs' ),
		[]
	);
	console.log( bacsSettings );

	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );

	// State for form fields
	const [ enabled, setEnabled ] = useState( false );
	const [ title, setTitle ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ instructions, setInstructions ] = useState( '' );

	useEffect( () => {
		if ( bacsSettings ) {
			setEnabled( bacsSettings.enabled || false );
			setTitle(
				bacsSettings.settings?.title?.value || 'Direct bank transfer'
			);
			setDescription( bacsSettings.description || '' );
			setInstructions( bacsSettings.settings?.instructions?.value || '' );
		}
	}, [ bacsSettings ] );

	const saveSettings = () => {
		updatePaymentGateway( 'bacs', {
			enabled,
			description,
			settings: {
				title,
				instructions,
			},
			accountDetails: [],
		} )
			.then( () => {
				console.log( 'Settings updated successfully' );
				window.location.reload();
			} )
			.catch( ( error ) => {
				console.error( 'Error updating settings:', error );
			} );
	};

	if ( ! bacsSettings ) {
		return <p>Loading settings...</p>;
	}

	return (
		<PaymentSettingsLayout>
			<PaymentSettingsSection
				id={ 'bacs-settings' }
				title={ __( 'Enable and customise', 'woocommerce' ) }
				description={ __(
					'Choose how you want to present bank transfer to your customers during checkout.',
					'woocommerce'
				) }
			>
				<Card className="payment-settings-card__wrapper bacs-settings__wrapper">
					<CardBody className="bacs-settings__body">
						<div className={ 'form-field' }>
							<CheckboxControl
								onChange={ ( value ) => setEnabled( value ) }
								checked={ enabled }
								// @ts-expect-error The label prop can be a string, however, the final consumer of this prop accepts ReactNode.
								label={
									<span>
										{ __(
											'Enable direct bank transfers',
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
					</CardBody>
				</Card>
			</PaymentSettingsSection>
			<PaymentSettingsSection
				id={ 'bacs-account-details' }
				title={ __( 'Account details', 'woocommerce' ) }
				description={ __(
					'Choose which bank account you would like to receive direct bank transfers into.',
					'woocommerce'
				) }
			>
				<Card
					className={
						'payment-settings-card__wrapper bacs-account-details__wrapper'
					}
				>
					<CardBody className={ 'bacs-account-details__body' }>
						<p>Table goes here</p>
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

export default SettingsPaymentsBacs;
