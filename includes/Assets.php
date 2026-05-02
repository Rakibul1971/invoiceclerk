<?php
namespace InvoiceClerk\ManualSettlement;

defined( 'ABSPATH' ) || exit;

class Assets {
	/**
	 * The constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_all_scripts' ), 10 );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_scripts' ) );
		}
	}

	/**
	 * Register all Dokan scripts and styles.
	 *
	 * @return void
	 */
	public function register_all_scripts() {
		$this->register_styles();
		$this->register_scripts();
	}

	/**
	 * Register scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		$admin_script    = INVOICECLERK_PLUGIN_ADMIN_ASSET . '/js/script.js';
		$frontend_script = INVOICECLERK_PLUGIN_PUBLIC_ASSET . '/js/script.js';

		wp_register_script( 'invoiceclerk_daterangepicker', INVOICECLERK_PLUGIN_ADMIN_ASSET . '/js/daterangepicker.min.js', array( 'jquery', 'moment' ), '3.1', true );

		wp_register_script( 'invoiceclerk_admin_script', $admin_script, array( 'jquery', 'moment', 'invoiceclerk_daterangepicker' ), INVOICECLERK_PLUGIN_VERSION, true );
		wp_register_script( 'invoiceclerk_script', $frontend_script, array(), INVOICECLERK_PLUGIN_VERSION, true );
	}

	/**
	 * Register styles.
	 *
	 * @return void
	 */
	public function register_styles() {
		$admin_style    = INVOICECLERK_PLUGIN_ADMIN_ASSET . '/css/style.css';
		$frontend_style = INVOICECLERK_PLUGIN_PUBLIC_ASSET . '/css/style.css';

		wp_register_style( 'invoiceclerk_daterangepicker', INVOICECLERK_PLUGIN_ADMIN_ASSET . '/css/daterangepicker.css', array(), '3.1' );
		wp_register_style( 'invoiceclerk_admin_style', $admin_style, array( 'invoiceclerk_daterangepicker' ), INVOICECLERK_PLUGIN_VERSION );
		wp_register_style( 'invoiceclerk_style', $frontend_style, array(), INVOICECLERK_PLUGIN_VERSION );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'invoiceclerk_admin_style' );
		wp_enqueue_script( 'invoiceclerk_admin_script' );
		wp_localize_script(
			'invoiceclerk_admin_script',
			'InvoiceClerk_Admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'invoiceclerk_admin_nonce' ),
			)
		);
	}

	/**
	 * Enqueue front-end scripts.
	 *
	 * @return void
	 */
	public function enqueue_front_scripts() {
		wp_enqueue_script( 'invoiceclerk_script' );
		wp_localize_script(
			'invoiceclerk_script',
			'InvoiceClerk',
			array()
		);
	}
}
