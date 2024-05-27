<?php
/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

defined('ABSPATH') || exit;

class CAS_Admin_Screen_Account extends CAS_Admin
{
    /** @var Freemius */
    protected $freemius;

    public function __construct($freemius)
    {
        parent::__construct();
        $this->freemius = $freemius;
    }

    /**
     * @inheritDoc
     */
    public function get_screen()
    {
        return 'content-aware_page_wpcas-account';
    }

    /**
     * @inheritDoc
     */
    public function authorize_user()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function prepare_screen()
    {
        $this->freemius->add_filter('hide_account_tabs', '__return_true');
        $this->freemius->add_filter('hide_billing_and_payments_info', '__return_true');

        $path = plugin_dir_path(dirname(__FILE__)) . 'view/';
        $view = WPCAView::make($path . 'account_login.php', [
            'list' => [
                __('Manage Subscription', 'content-aware-sidebars'),
                __('Access Invoices', 'content-aware-sidebars'),
                __('View Licenses', 'content-aware-sidebars')
            ]
        ]);
        $this->freemius->add_action('after_account_details', [$view, 'render']);
    }

    /**
     * @inheritDoc
     */
    public function admin_hooks()
    {
    }

    /**
     * @inheritDoc
     */
    public function add_scripts_styles()
    {
    }
}
