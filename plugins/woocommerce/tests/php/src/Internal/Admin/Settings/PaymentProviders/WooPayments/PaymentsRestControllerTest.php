<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders\WooPayments\WooPaymentsRestController;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * WooPaymentsRestController API controller test.
 *
 * @class WooPaymentsRestController
 */
class WooPaymentsRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/settings/payments/woopayments';

	/**
	 * @var WooPaymentsRestController
	 */
	protected WooPaymentsRestController $sut;

	/**
	 * @var MockObject|Payments
	 */
	private $mock_payments_service;

	/**
	 * @var MockObject|WooPaymentsService
	 */
	private $mock_woopayments_service;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->mock_payments_service = $this->getMockBuilder( Payments::class )->getMock();
		$this->mock_woopayments_service = $this->getMockBuilder( WooPaymentsService::class )->getMock();

		$this->sut = new WooPaymentsRestController();
		$this->sut->init( $this->mock_payments_service, $this->mock_woopayments_service );
		$this->sut->register_routes( true );
	}

	/**
	 * Test getting onboarding details by a user without the needed capabilities.
	 */
	public function test_get_onboarding_details_by_user_without_caps() {
		// Arrange.
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => false, // This is needed.
			'install_plugins'    => true, // This is not needed.
		);
		add_filter( 'user_has_cap', $filter_callback );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( rest_authorization_required_code(), $response->get_status() );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting payment providers by a user with the needed permissions.
	 */
	public function test_get_onboarding_details_by_manager() {
		// Arrange.
		$country_code = 'US';
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array(
			'manage_woocommerce' => true, // This is needed.
			'install_plugins'    => false, // This is not needed.
		);
		add_filter( 'user_has_cap', $filter_callback );

		$this->mock_onboarding_details( $country_code );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', $country_code );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'state', $data );
		$this->assertArrayHasKey( 'steps', $data );
		$this->assertArrayHasKey( 'context', $data );

		// Check that the step has all the fields.
		$step = $data['steps'][0];
		$this->assertArrayHasKey( 'id', $step );
		$this->assertArrayHasKey( 'path', $step );
		$this->assertArrayHasKey( 'required_steps', $step );
		$this->assertArrayHasKey( 'status', $step );
		$this->assertArrayHasKey( 'errors', $step );
		$this->assertArrayHasKey( 'actions', $step );
		$this->assertArrayHasKey( 'context', $step );
		// Check that we have all the actions.
		$this->assertArrayHasKey( 'start', $step['actions'] );
		$this->assertArrayHasKey( 'save', $step['actions'] );
		$this->assertArrayHasKey( 'check', $step['actions'] );
		$this->assertArrayHasKey( 'finish', $step['actions'] );
		$this->assertArrayHasKey( 'auth', $step['actions'] );
		$this->assertArrayHasKey( 'init', $step['actions'] );
		$this->assertArrayHasKey( 'kyc_session', $step['actions'] );
		$this->assertArrayHasKey( 'kyc_session_finish', $step['actions'] );
		$this->assertArrayHasKey( 'kyc_fallback', $step['actions'] );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test getting onboarding details without specifying a location.
	 *
	 * It should default to the providers stored location.
	 */
	public function test_get_payment_providers_with_no_location() {
		// Arrange.
		$country_code = 'LI'; // Liechtenstein.
		$this->mock_providers_country( $country_code );
		$this->mock_onboarding_details( $country_code );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		// Assert all the entries are in the response.
		$this->assertArrayHasKey( 'state', $data );
		$this->assertArrayHasKey( 'steps', $data );
		$this->assertArrayHasKey( 'context', $data );
	}

	/**
	 * Test getting onboarding details with invalid location.
	 */
	public function test_get_onboarding_details_with_invalid_location() {
		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', 'U' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', '12' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );

		// Act.
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/onboarding' );
		$request->set_param( 'location', 'USA' );
		$response = $this->server->dispatch( $request );

		// Assert.
		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Mock the onboarding details with the given country code.
	 */
	private function mock_onboarding_details( $country_code ) {
		$mock_onboarding_details = array(
			'state'   => array(
				'started'   => false,
				'completed' => false,
				'test_mode' => true,
				'dev_mode'  => true,
			),
			'steps'   => array(
				array(
					'id'             => 'step1',
					'path'           => '/step1',
					'required_steps' => array(),
					'status'         => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					'errors'         => array(
						'error_message_1',
						'error_message_2',
					),
					'actions'        => array(
						'start'  => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/start' ),
						),
						'save'   => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/save' ),
						),
						'check'   => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/check' ),
						),
						'finish' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/finish' ),
						),
						'auth'  => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/auth' ),
						),
						'init' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/init' ),
						),
						'kyc_session' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/kyc_session' ),
						),
						'kyc_session_finish' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step1/kyc_session/finish' ),
						),
						'kyc_fallback' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REDIRECT,
							'href' => 'https://example.com/kyc_fallback',
						),
					),
					'context'        => array(),
				),
				// Add a step that requires the previous step to be completed.
				array(
					'id'             => 'step2',
					'path'           => '/step2',
					'required_steps' => array( 'step1' ),
					'status'         => WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
					'errors'         => array(),
					'actions'        => array(
						'start'  => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/start' ),
						),
						'save'   => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/save' ),
						),
						'check'   => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/check' ),
						),
						'finish' => array(
							'type' => WooPaymentsService::ACTION_TYPE_REST,
							'href' => rest_url( self::ENDPOINT . '/step2/finish' ),
						),
						// No auth step for this step.
						// No init step for this step.
						// No kyc_session step for this step.
						// No kyc_session_finish step for this step.
						// No kyc_fallback step for this step.
					),
					'context'        => array(),
				),
			),
			'context' => array(
				'urls' => array(
					'overview_page' => 'https://example.com/overview',
				),
			),
		);


		$this->mock_woopayments_service
			->expects( $this->once() )
			->method( 'get_onboarding_details' )
			->with( $country_code, $this->anything() )
			->willReturn( $mock_onboarding_details );
	}

	/**
	 * Mock the providers country to return the given country code.
	 *
	 * @param $country_code
	 *
	 * @return void
	 */
	private function mock_providers_country( $country_code ) {
		$this->mock_payments_service
			->expects( $this->once() )
			->method( 'get_country' )
			->willReturn( $country_code );
	}
}
