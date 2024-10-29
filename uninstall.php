<?php
/**
 * Uninstall Plugins List Comments
 *
 * @package     BBP-manage-subscriptions
 * @subpackage  Uninstall
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License * 
 */

// Bail if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

// Remove global options.
delete_option('bbpms-rem-ohbother');
delete_option('bbpms-tick-notify');
delete_option('bbpms-tick-ohbother');
delete_option('bbpms-new-ohbother');
delete_option('bbpms-tick-unreshtml');
delete_option('bbpms-new-unres-html');
delete_option('bbpms-subscr-right');
delete_option('bbpms-closed-nogrey');
delete_option('bbpms-rem-defstyle');
delete_option('bbpms-reply-inverse');
delete_option('bbpms-rem-subf');

	
/* Check to remove user options if possible
update_user_meta($userid, 'bbpms-perpage'
update_user_meta($userid, 'bbpms-showusers'
update_user_meta($userid, 'bbpms-hidden-roles'
update_user_meta($userid, 'bbpms-hidden-forum-ids'
*/
?>