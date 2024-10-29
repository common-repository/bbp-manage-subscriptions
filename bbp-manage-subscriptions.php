<?php
/*
Plugin Name: bbP Manage Subscriptions
Description: A table to manage bbPress subscriptions in your WordPress Admin area.
Plugin URI: https://wordpress.org/plugins/bbp-manage-subscriptions/
Author: Pascal Casier
Author URI: http://casier.eu/wp-dev/
Text Domain: bbp-manage-subscriptions
Version: 1.2.0
License: GPL2
*/

//
// REMOVE IN NEXT VERSION - START
//

$bbpms_switched = get_option('bbpms-switched');
if (!$bbpms_switched) {

// Remove Oh Bother and Unrestricted HTML messages completely
function my_queue_bbp_scripts() {
	if( function_exists( 'is_bbpress' ) ) {
		if( is_bbpress() ) {
			$bbpms_rem_ohbother = get_option('bbpms-rem-ohbother', false);
			if ($bbpms_rem_ohbother) {
				wp_enqueue_style( 'bbpmsremohbother', plugin_dir_url( __FILE__ ).'css/remohbother.css');
			}
			$bbpms_subscr_right = get_option('bbpms-subscr-right', false);
			if ($bbpms_subscr_right) {
				wp_enqueue_style( 'bbpmssubscribetoright', plugin_dir_url( __FILE__ ).'css/subscribetoright.css');
			}
			$bbpms_closed_nogrey = get_option('bbpms-closed-nogrey', false);
			if ($bbpms_closed_nogrey) {
				wp_enqueue_style( 'bbpmsclosednogrey', plugin_dir_url( __FILE__ ).'css/closednogrey.css');
			}
			$bbpms_rem_subf = get_option('bbpms-rem-subf', false);
			if ($bbpms_rem_subf) {
				wp_enqueue_style( 'bbpmsremsubf', plugin_dir_url( __FILE__ ).'css/removesubforumlist.css');
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'my_queue_bbp_scripts' );

function bbpms_change_translate_text( $translated_text ) {
	if ( $translated_text == 'Oh bother! No topics were found here!') {
		$bbpms_tick_ohbother = get_option('bbpms-tick-ohbother', false);
		$NewOhBother = get_option('bbpms-new-ohbother', false);
		if (($bbpms_tick_ohbother) and ($NewOhBother)) {
			$translated_text = $NewOhBother;
		}
	}
	if ( $translated_text == 'Your account has the ability to post unrestricted HTML content.') {
		$bbpms_tick_unreshtml = get_option('bbpms-tick-unreshtml', false);
		$NewUnresHTML = get_option('bbpms-new-unres-html', false);
		if (($bbpms_tick_unreshtml) and ($NewUnresHTML)) {
			$translated_text = $NewUnresHTML;
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'bbpms_change_translate_text', 20 );

// Desc not asc sort for replies in a topic
function bbpms_has_replies( $args ) { 
	if( function_exists( 'is_bbpress' ) ) {
		if( is_bbpress() ) {
			$bbpms_reply_inverse = get_option('bbpms-reply-inverse', false);
			if ($bbpms_reply_inverse) {
				if ( bbp_is_single_topic() && !bbp_is_single_user() ) { 
					$args['orderby'] .= 'post_modified';
					$args['order'] .= 'DESC';
				}
			}
		}
	}
	return $args; 
} 
add_filter('bbp_before_has_replies_parse_args', 'bbpms_has_replies' );

// Remove bbpress CSS from all pages expect where forums are
function my_unqueue_bbp_scripts() {
	if( ! is_bbpress() ) {
		$bbpms_rem_defstyle = get_option('bbpms-rem-defstyle', false);
		if ($bbpms_rem_defstyle) {
			wp_dequeue_style('bbp-default');
			wp_dequeue_style('bbp-default-rtl');
		}
	}
}
add_action( 'bbp_enqueue_scripts', 'my_unqueue_bbp_scripts', 15 );


// Changes the logic on replying to topics to check the box for notify of replies, rather than have it blank
function bbpms_auto_check_subscribe( $checked, $topic_subscribed  ) {
	$bbpms_tick_notify = get_option('bbpms-tick-notify', false);
	if ($bbpms_tick_notify) {
		// option exists, so always tick the 'Notify me of follow-up replies via email' 
		return checked( true, true, false );
        } else {
        	// option does not exist so keep the value as before (by default this is not ticked)
		return checked( $topic_subscribed, true, false );
        }
}
add_filter( 'bbp_get_form_topic_subscribed', 'bbpms_auto_check_subscribe', 10, 2 );

}
//
// REMOVE IN NEXT VERSION - END
//


if (!is_admin()) {
	//echo 'Cheating ? You need to be admin to view this !';
	return;
} // is_admin

// Check if bbpress is installed and running
// Check if get_plugins() function exists. This is required on the front end of the
// site, since it is in a file that is normally only loaded in the admin.
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( !is_plugin_active( 'bbpress/bbpress.php' ) ) {
	//plugin is not active
	echo 'bbPress plugin is not active (or not found in ' . ABSPATH . PLUGINDIR . '/bbpress/bbpress.php) !';
	return;
} 

// add plugin upgrade notification
add_action('in_plugin_update_message-bbp-toolkit/bbp-toolkit.php', 'bbpmsshowUpgradeNotification', 10, 2);
function bbpmsshowUpgradeNotification($currentPluginMetadata, $newPluginMetadata){
   // check "upgrade_notice"
   if (isset($newPluginMetadata->upgrade_notice) && strlen(trim($newPluginMetadata->upgrade_notice)) > 0){
        echo '<p style="background-color: #d54e21; padding: 10px; color: #f9f9f9; margin-top: 10px"><strong>Important Upgrade Notice:</strong> ';
        echo esc_html($newPluginMetadata->upgrade_notice), '</p>';
   }
}

// Check if action needs to be done without loading the rest of the page
if ( isset($_GET['action']) ) {
	if ($_GET['action'] == "add_subscr") {
		if ( !function_exists( 'bbp_add_user_subscription' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
			require_once ABSPATH . PLUGINDIR . '/bbpress/bbpress.php';
			require_once ABSPATH . PLUGINDIR . '/bbpress/includes/users/functions.php';
		}
		bbp_add_user_subscription($_GET['userid'], $_GET['forumid']);
		$new_header = $_GET;
		unset($new_header['action']);
		unset($new_header['userid']);
		unset($new_header['forumid']);
		$QS = http_build_query($new_header);
		header('Location: ?' . $QS);
	}
	if ($_GET['action'] == "del_subscr") {
		if ( !function_exists( 'bbp_remove_user_subscription' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
			require_once ABSPATH . PLUGINDIR . '/bbpress/bbpress.php';
			require_once ABSPATH . PLUGINDIR . '/bbpress/includes/users/functions.php';
		}
		bbp_remove_user_subscription($_GET['userid'], $_GET['forumid']);
		$new_header = $_GET;
		unset($new_header['action']);
		unset($new_header['userid']);
		unset($new_header['forumid']);
		$QS = http_build_query($new_header);
		header('Location: ?' . $QS);
	}
}


// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
add_action( 'admin_menu', 'add_menu_bbPMS_list_table_page' );

class bbPMS_List_Table extends WP_List_Table
{
     /**
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     */
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'user',     //singular name of the listed records
            'plural'    => 'users',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

     /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    function prepare_items()
    {
	global $wp_roles;
	$userid = get_current_user_id();
	// check if bbp-private-groups is installed
	if ( is_plugin_active( 'bbp-private-groups/bbp-private-groups.php' ) ) {
		 $bbppg = true;
		 $bbppg_groups = get_option('rpg_groups');
		 if (!$bbppg_groups) {
		 	$bbppg = false;
		 }
	} else {
		$bbppg = false;
	}
	
	// Get roles on this system and remove Pending
	$all_roles = $wp_roles->roles;
	unset($all_roles['pending']);
	$roles_to_show = array_keys($all_roles);
	
	//Get all data about the forums and store in array
	$forums_all_data = get_forum_data();
    	
    		
        $columns = $this->get_columns($roles_to_show, $forums_all_data['all_ids_array'], $bbppg, $bbppg_groups);
        $hidden = $this->get_hidden_columns($userid);
        $sortable = $this->get_sortable_columns($roles_to_show, $forums_all_data['all_ids_with_prefix_array']);
	
        $data = $this->table_data($roles_to_show, $forums_all_data['all_ids_array'], $userid, $bbppg, $bbppg_groups);
        usort( $data, array( &$this, 'sort_data' ) );

	// get the options set by the current user
        $perPage = get_user_meta($userid, 'bbpms-perpage', true);
        // if no value set, use the default
        if ( empty ( $perPage ) || $perPage < 1 || !is_numeric($perPage)) {
		$perPage = 15;
	}

        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
 
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalItems/$perPage)
        ) );
 
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
 
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
        
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    function get_columns($roles_to_show = false, $all_ids_array = false, $is_bbppg = false, $bbppg_groups = false)
    {
    	if (!$roles_to_show) {
    		// To remove warnings from wp 4.3 like:
    		// Warning: Missing argument 1 for bbPMS_List_Table::get_columns(), called in /home/agseptes/public_html/wp-admin/includes/class-wp-list-table.php on line 887
    		// 	and defined in /home/agseptes/public_html/wp-content/plugins/bbp-manage-subscriptions/bbp-manage-subscriptions.php on line 154
    		// Warning: Missing argument 1 for bbPMS_List_Table::get_columns(), called in /home/agseptes/public_html/wp-admin/includes/class-wp-list-table.php on line 861
    		//	and defined in /home/agseptes/public_html/wp-content/plugins/bbp-manage-subscriptions/bbp-manage-subscriptions.php on line 154
    		// it seems get_columns is called from other functions, check if this is about the primary column names in next version!
    		// Also added '= false' to all parameters 
    		$columns = array();
    		return $columns;
    	}
    	
        $columns = array(
            'display_name'	=> 'Name'
        );

	foreach ($roles_to_show as $myrole) {
		$newarray = array ( $myrole => $myrole );
		$columns = array_merge($columns, $newarray);
	}

	foreach ($all_ids_array as $forum_id) {
		$forum_id_with_prefix = 'F' . $forum_id;
		$forum_title = bbp_get_forum_title($forum_id);
		$forum_parent_id = bbp_get_forum_parent_id($forum_id);
		if ($forum_parent_id) {
			$forum_title = $forum_title . '<br>(Parent: ' . bbp_get_forum_title($forum_parent_id) . ')';
		}
		// if bbp-private-groups is active, add the bbp-private-groups in the title (in italic)
		if ($is_bbppg) {
			$agroups = get_post_meta( $forum_id, '_private_group', false );
			$tgroups = array();
			foreach ($agroups as $agroup) {
				array_push($tgroups, $bbppg_groups[$agroup]);
			}
			if ($agroups) {
				$forum_title = $forum_title . '<br>[<i>bbp-private-groups :<br>' . implode(",",$tgroups) . '</i>]';
			}
		}
		$newarray = array ( $forum_id_with_prefix => $forum_title);
       		$columns = array_merge($columns, $newarray);		
	} // foreach

	if ($is_bbppg) {
		while ($mygroup = current($bbppg_groups)) {
			$title = $mygroup . '<br>[<i>bbp-private-groups</i>]';
			$skey = 'bbppg' . key($bbppg_groups);
			$newarray = array ( $skey => $title );
	       		$columns = array_merge($columns, $newarray);
	       		next($bbppg_groups);		
		}
	}
        return $columns;
    }
 
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    function get_hidden_columns($userid)
    {
	// At least hide the ID and role=pending
	$columns_to_hide = array('ID', 'pending');
	
        $colstohide = get_user_meta($userid, 'bbpms-hidden-roles', true);
        if ( !empty ( $colstohide ) ) {
	        $array_roles = explode(",",$colstohide);
		foreach ($array_roles as $coltohide) {
			array_push ($columns_to_hide, $coltohide);
		}
	}

        $colstohide = get_user_meta($userid, 'bbpms-hidden-forum-ids', true);
        if ( !empty ( $colstohide ) ) {
	        $array_forum_ids = explode(",",$colstohide);
		foreach ($array_forum_ids as $coltohide) {
			array_push ($columns_to_hide, 'F'.$coltohide);
		}
	}
	
        return $columns_to_hide;
    }
 
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    function get_sortable_columns($roles_to_show, $forums_to_show)
    {
    
	$columns = array(
		'display_name'	=> array('display_name', false),
	);

	foreach ($roles_to_show as $myrole) {
		$newarray = array ( $myrole => array ($myrole,true) );
		$columns = array_merge($columns, $newarray);
	}
	foreach ($forums_to_show as $myforum) {
		$newarray = array ( $myforum => array ($myforum,true) );
		$columns = array_merge($columns, $newarray);
	}
	return $columns;
    }
 
    /**
     * Get the table data
     *
     * @return Array
     */
    function table_data($roles_to_show, $forums_to_show, $userid, $is_bbppg, $bbppg_groups)
    {
	global $wpdb;
	$cap_with_prefix = $wpdb->prefix . 'capabilities';
	$all_data = array();
	$all_users = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name', ) );
	$i = 0;
	foreach ($all_users as $a_user) {
		$caps = get_user_meta($a_user->ID, $cap_with_prefix, true);
		$roles = array_keys((array)$caps);
		$subscriptions = bbp_get_user_subscribed_forum_ids($a_user->ID);
		$bbppg_user = get_user_meta($a_user->ID, 'private_group', true);

		// Only show the visible roles ?
		$what_to_show = get_user_meta($userid, 'bbpms-showusers', true);
		if ($what_to_show == 'nohidden') {
			$hidden_roles = explode(",",get_user_meta($userid, 'bbpms-hidden-roles', true));
			$roles_to_show = array_diff($roles_to_show, $hidden_roles);
		}

		$show_user = false;
		foreach ($roles as $r) {
			if (in_array($r, $roles_to_show)) {
				$show_user = true;
			}
		}
		if ($show_user) {
			$all_data[$i]['display_name'] = $a_user->display_name;
			$all_data[$i]['ID'] = $a_user->ID;
			foreach ($roles as $r) {
				if (in_array($r, $roles_to_show)) {
					$all_data[$i][$r] = '<button class="role-button">HasRole</button>';
				}
			}
			// Get all forums and fill with default value
			foreach ($forums_to_show as $myforum) {
				$fname = 'F' . $myforum;
				$QS = http_build_query(array_merge($_GET, array("action"=>"add_subscr", "forumid"=>$myforum, "userid"=>($a_user->ID))));
				$all_data[$i][$fname] = '<a href="?'.$QS.'"><button class="forum-no-button">No subscr</button></a>';
			} // foreach
			//Overwrite the fields with the subscriptions of this user
			if ( !empty( $subscriptions ) ) {
				foreach ($subscriptions as $subscr) {
					$fname = 'F' . $subscr;
					$QS = http_build_query(array_merge($_GET, array("action"=>"del_subscr", "forumid"=>$subscr, "userid"=>($a_user->ID))));
					$all_data[$i][$fname] = '<a href="?'.$QS.'"><button class="forum-button">Subscribed</button></a>';
				}
			}
			if ($is_bbppg) {
/*				// Get all bbp-private-groups and fill with default value
				foreach ($bbppg_groups as $key=>$mygroup) {
					$fgroup = 'bbppg' . $key;
					$all_data[$i][$fgroup] = '<button class="bbppg-no-button">No member</button>';
				} // foreach */
				// Fill the fields with the bbp-private-groups of this user
				if ($bbppg_user) {
					foreach ($bbppg_groups as $key=>$mygroup) {
						if (strpos($bbppg_user,$key) !== false) {
							$fgroup = 'bbppg' . $key;
							$all_data[$i][$fgroup] = '<button class="bbppg-button">IsMember</button>';
						}
					}
				}
			}

			$i++;
		}
	}
        return $all_data;
    }
 
    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    function column_default( $item, $column_name )
    {
		if (isset($item[ $column_name ])) {
			$return = $item[ $column_name ];
		} else {
			$return = '';
		}
		
        switch( $column_name ) {
            default:
                return $return;
        }
    }
 
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'display_name';
        $order = 'desc';
 
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
 
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
 
 
        $result = strnatcmp( $a[$orderby], $b[$orderby] );
 
        if($order === 'asc')
        {
            return $result;
        }
 
        return -$result;
    }

} //class

function add_menu_bbPMS_list_table_page() {
	$hook = add_menu_page( 'bbP Manage Subscriptions', 'bbP Manage Subscriptions', 'manage_options', 'bbp-manage-subscriptions.php', 'list_table_page' );
	add_action( 'admin_head-'.$hook, 'admin_header' );
	$confHook = add_submenu_page( 'bbp-manage-subscriptions.php', 'Settings', 'Settings', 'manage_options', 'bbp-manage-subscriptions-settings.php', 'show_settings' );
	add_action("admin_head-$confHook", 'admin_header');
} //add_menu_bbPMS_list_table_page


function admin_header() {
	wp_enqueue_script('bbpmsadminjs', plugin_dir_url( __FILE__ ).'js/bbpms-config.js');
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	unset($all_roles['pending']);
	$roles_to_show = array_keys($all_roles);
	$temp = get_forum_data();
	$forums_to_show = $temp['all_ids_with_prefix_array'];
	$bbppg_to_show = $temp['all_bbppg_array'];

	echo '<style type="text/css">';
	echo '.wp-list-table { width:auto !important; }';
	echo '.wp-list-table .column-display_name { width: 30px; white-space: nowrap; padding: 3px 5px;}';
	
	foreach ($roles_to_show as $myrole) {
		echo '.wp-list-table tr:nth-child(odd) .column-' . $myrole . ' { text-align: center; background-color: #b1ced9; }';
		echo '.wp-list-table tr:nth-child(even) .column-' . $myrole . ' { text-align: center; background-color: #cbdee6; }';
	}
	foreach ($forums_to_show as $myforum) {
		echo '.wp-list-table tr:nth-child(odd) .column-' . $myforum . ' { text-align: center; background-color: #a4c6d3; }';
		echo '.wp-list-table tr:nth-child(even) .column-' . $myforum . ' { text-align: center; background-color: #bed6e0; }';
	}
	foreach ($bbppg_to_show as $key=>$mybbppg) {
		echo '.wp-list-table tr:nth-child(odd) .column-bbppg' . $key . ' { text-align: center; background-color: #b1ced9; }';
		echo '.wp-list-table tr:nth-child(even) .column-bbppg' . $key . ' { text-align: center; background-color: #cbdee6; }';
	}
	echo '.wp-list-table .forum-button {
	  -webkit-border-radius: 28;
	  -moz-border-radius: 28;
	  border-radius: 28px;
	  font-family: Arial;
	  color: #ffffff;
	  font-size: 12px;
	  background: #3498db;
	  padding: 3px 7px 3px 7px;
	  text-decoration: none;
	}';
	echo '.wp-list-table .bbppg-button {
	  -webkit-border-radius: 28;
	  -moz-border-radius: 28;
	  border-radius: 28px;
	  font-family: Arial;
	  color: #ffffff;
	  font-size: 12px;
	  background: #34d981;
	  padding: 3px 7px 3px 7px;
	  text-decoration: none;
	}';
	echo '.wp-list-table .role-button {
	  -webkit-border-radius: 28;
	  -moz-border-radius: 28;
	  border-radius: 28px;
	  font-family: Arial;
	  color: #ffffff;
	  font-size: 12px;
	  background: #34d981;
	  padding: 3px 7px 3px 7px;
	  text-decoration: none;
	}';
	echo '.wp-list-table .forum-no-button {
	  -webkit-border-radius: 28;
	  -moz-border-radius: 28;
	  border-radius: 28px;
	  font-family: Arial;
	  color: #ffffff;
	  font-size: 10px;
	  background: #D8D8D8;
	  padding: 3px 7px 3px 7px;
	  text-decoration: none;
	}';
	
	echo '</style>';
}    
	
/**
* Display the list table page
*
* @return Void
*/
function list_table_page() {
	$ListTable = new bbPMS_List_Table();
	$ListTable->prepare_items();
	?>
		<div class="wrap">
		<h1>bbP Manage Subscriptions</h1>
		<?php $ListTable->display(); ?>
 		</div>
	<?php
}

/**
* Get all forum_data
*
* @return Array
*  ['all_data']
*  ['all_ids_string']
*  ['all_ids_with_prefix_string']
*  ['all_ids_array']
*  ['all_ids_with_prefix_array']
*/
function get_forum_data() {
	$all_forums_data = array();
	$all_forums_ids = array();
	$all_forums_ids_with_prefix = array();
	$i = 0;
	if ( bbp_has_forums() ) {
		while ( bbp_forums() ) {
			bbp_the_forum();
			$forum_id = bbp_get_forum_id();
			$all_forums_data['all_data'][$i]['id'] = $forum_id;
			$all_forums_data['all_data'][$i]['title'] = bbp_get_forum_title($forum_id);
			array_push($all_forums_ids, $forum_id);
			array_push($all_forums_ids_with_prefix, 'F'.$forum_id);
			// Add bbp-private-groups if plugin is active
			if ( is_plugin_active( 'bbp-private-groups/bbp-private-groups.php' ) ) {
				$agroups = get_post_meta( $forum_id, '_private_group', false );
				$all_forums_data['all_data'][$i]['bbppg'] = implode(",",$agroups);
			}
			// Check for subforums (first level only)
			if ($sublist = bbp_forum_get_subforums($forum_id)) {
				$all_subforums = array();
				foreach ( $sublist as $sub_forum ) {
					$mysubforum = array ( 'id' => $sub_forum->ID, 'title' => bbp_get_forum_title( $sub_forum->ID ));
					array_push($all_subforums, $mysubforum);
					array_push($all_forums_ids, $sub_forum->ID);
					array_push($all_forums_ids_with_prefix, 'F'.$sub_forum->ID);
				}
				$all_forums_data['all_data'][$i]['subforums'] = $all_subforums;
			}					
			$i++;
		} // while()
		$all_forums_data['all_ids_string'] = implode(',',$all_forums_ids);
		$all_forums_data['all_ids_with_prefix_string'] = implode(',',$all_forums_ids_with_prefix);
		$all_forums_data['all_ids_array'] = $all_forums_ids;
		$all_forums_data['all_ids_with_prefix_array'] = $all_forums_ids_with_prefix;
		// check if bbp-private-groups is installed
		$all_forums_data['all_bbppg_array'] = array();
		if ( is_plugin_active( 'bbp-private-groups/bbp-private-groups.php' ) ) {
			// Create array with all groups
			$bbppg = true;
			$bbppg_groups = get_option('rpg_groups');
			if ($bbppg_groups) {
				$all_forums_data['all_bbppg_array'] = $bbppg_groups;
			}  
		}
	} // if()
	return $all_forums_data;
}

function settings_header_css() {
	// enter style needed for setttings page here
	echo '<style type="text/css">';
	echo '</style>';
}

function show_settings() {

	$userid = get_current_user_id();
	
	// Check if options need to be saved, so if coming from form
	if ( isset($_POST['switchtoolkit']) ) {
		add_option('bbpms-switched', 'activate');
		delete_option('bbpms-tick-notify');
		delete_option('bbpms-rem-ohbother');
		delete_option('bbpms-tick-ohbother');
		delete_option('bbpms-tick-unreshtml');
		delete_option('bbpms-subscr-right');
		delete_option('bbpms-closed-nogrey');
		delete_option('bbpms-rem-defstyle');
		delete_option('bbpms-new-ohbother');
		delete_option('bbpms-new-unres-html');
		delete_option('bbpms-reply-inverse');
		delete_option('bbpms-rem-subf');
		remove_action( 'wp_enqueue_scripts', 'my_queue_bbp_scripts' );
		remove_filter( 'gettext', 'bbpms_change_translate_text', 20 );
		remove_filter('bbp_before_has_replies_parse_args', 'bbpms_has_replies' );
		remove_action( 'bbp_enqueue_scripts', 'my_unqueue_bbp_scripts', 15 );
		remove_filter( 'bbp_get_form_topic_subscribed', 'bbpms_auto_check_subscribe', 10);
	}
	
	if ( isset($_POST['optssave']) ) {
		update_user_meta($userid, 'bbpms-perpage', $_POST['usersperpage']);
		update_user_meta($userid, 'bbpms-showusers', $_POST['showusers']);

		$array_roles = explode(",",$_POST['all_roles']);
		$roles_to_hide = array();
		foreach ($array_roles as $myrole) {
			if( !empty($_POST["role-$myrole"]) ) {
				// Checkbox was checked
				array_push($roles_to_hide, $myrole);
			}
		}
		update_user_meta($userid, 'bbpms-hidden-roles', implode(",",$roles_to_hide));
		
		$array_forum_ids = explode(",",$_POST['all_forum_ids']);
		$forum_ids_to_hide = array();
		foreach ($array_forum_ids as $myforumid) {
			if( !empty($_POST["forum-$myforumid"]) ) {
				// Checkbox was checked
				array_push($forum_ids_to_hide, $myforumid);
			}
		}
		update_user_meta($userid, 'bbpms-hidden-forum-ids', implode(",",$forum_ids_to_hide));
	}

	$bbpms_tick_notify = get_option('bbpms-tick-notify');
	$bbpms_rem_ohbother = get_option('bbpms-rem-ohbother');
	$bbpms_tick_ohbother = get_option('bbpms-tick-ohbother');
	$bbpms_tick_unreshtml = get_option('bbpms-tick-unreshtml');
	$bbpms_subscr_right = get_option('bbpms-subscr-right');
	$bbpms_closed_nogrey = get_option('bbpms-closed-nogrey');
	$bbpms_rem_defstyle = get_option('bbpms-rem-defstyle');
	$NewOhBother = get_option('bbpms-new-ohbother');
	$NewUnresHTML = get_option('bbpms-new-unres-html');
	$bbpms_reply_inverse = get_option('bbpms-reply-inverse');
	$bbpms_rem_subf = get_option('bbpms-rem-subf');


	// get the per_page options set by the current user
        $perPage = get_user_meta($userid, 'bbpms-perpage', true);
        // if no value set, use the default
        if ( empty ( $perPage ) || $perPage < 1 || !is_numeric($perPage)) {
		$perPage = 15;
	}
	
	// check new Oh Bother and Unrestricted HTML message
	if ( empty ( $NewOhBother ) ) {
		$NewOhBother = "Oh bother! No topics were found here!";
	}
	if ( empty ( $NewUnresHTML ) ) {
		$NewUnresHTML = "Your account has the ability to post unrestricted HTML content.";
	}
	
        $showwhat = get_user_meta($userid, 'bbpms-showusers', true);
        // if no value set, use the default
        if ( empty ( $showwhat )) {
		$showwhat = 'all';
	}

	// Get array with forum_id and forum_title
	$all_forums = array();
	$all_forum_ids = array();
	$i = 0;
	if ( bbp_has_forums() ) {
		while ( bbp_forums() ) {
			bbp_the_forum();
			$forum_id = bbp_get_forum_id();
			$all_forums[$i]['id'] = $forum_id;
			$all_forums[$i]['title'] = bbp_get_forum_title($forum_id);
			array_push($all_forum_ids, $forum_id);
			if ($sublist = bbp_forum_get_subforums($forum_id)) {
				$all_subforums = array();
				foreach ( $sublist as $sub_forum ) {
					$mysubforum = array ( 'id' => $sub_forum->ID, 'title' => bbp_get_forum_title( $sub_forum->ID ));
					array_push ($all_subforums, $mysubforum);
					array_push($all_forum_ids, $sub_forum->ID);
				}
				$all_forums[$i]['subforums'] = $all_subforums;
			}					
			$i++;
		} // while()
	} // if()

	// Get forums that user already saved to hide
        $hidden_forum_ids = get_user_meta($userid, 'bbpms-hidden-forum-ids', true);

	// Get all roles
	global $wp_roles;	
	$all_roles = $wp_roles->roles;
	unset($all_roles['pending']);
	$all_roles = array_keys($all_roles);
	
	// Get roles that user already saved to hide
        $hidden_roles = get_user_meta($userid, 'bbpms-hidden-roles', true);

	echo '<div class="wrap">';
	echo '<h1>bbP Manage Subscriptions Settings</h1>';
	
	// Check if switch to bbP Toolkit was done
	$bbpms_switched = get_option('bbpms-switched');
	if (!$bbpms_switched) {
		$bbptoolkitexists = is_plugin_active( 'bbp-toolkit/bbp-toolkit.php' );
		if ($bbptoolkitexists) {
			// Tookit exists but not yet switched, so show the options but read-only
			// And ask to switch
			echo '<div style="border-style: solid; border-width: 5px; border-color: red; padding: 10px 10px; font-size: 120%;">';
			echo '<b>URGENT</b> : bbP Toolkit seems installed. Click below to switch so you can manage the options in that plugin<br>';
			echo '&nbsp;&nbsp;<form action="" method="post"><input type="submit" name="switchtoolkit" value="Switch management" />&nbsp;&nbsp;(this will remove all the below settings)</form><br>';
			echo 'This plugin (bbP Manage Subscriptions) will only handle the management of forum subscriptions from next release onwards!<br>';
			echo 'The below settings cannot be changed anymore in this plugin but remain as they were set to not break your forum.';
			echo '</div>';
		} else {
			// Toolkit not installed or not active
			echo '<p style="border-style: solid; border-width: 5px; border-color: red; padding: 10px 10px; font-size: 120%;">';
			echo '<b>URGENT</b> : Install <a href="https://wordpress.org/plugins/bbp-toolkit/">bbP toolkit</a> !NOW! to continue manage global options.<br>';
			echo 'This plugin (bbP Manage Subscriptions) will only handle the management of forum subscriptions from next release onwards!<br>';
			echo 'The below settings cannot be changed anymore in this plugin but remain as they were set to not break your forum.';
			echo '</p>';
		}
	} else {
		// Switch was done, no more need for the options
	}

	echo '<form action="" method="post">';
	
	if (!$bbpms_switched) {
		echo '<h3>bbPress global behaviour</h3>';
				
		echo '<p><input type="checkbox" name="bbpms-tick-notify" id="bbpms-tick-notify" value="bbpms-tick-notify" ';
		if ($bbpms_tick_notify) { echo 'checked'; }
		echo '><label for="bbpms-tick-notify">Auto tick the <b>Notify me of follow-up replies via email</b></label></p>';
		
		echo '<p><input type="checkbox" name="bbpms-rem-ohbother" id="bbpms-rem-ohbother" value="bbpms-rem-ohbother" onclick="showdivohbother(\'bbpms-ohbother\')" '; 
		if ($bbpms_rem_ohbother) { echo 'checked'; }
		echo '><label for="bbpms-rem-ohbother">Completely remove message and box for empty forum <b>Oh bother! No topics were found here!</b>, the message <b>You must be logged in to create new topics.</b> and for admins the <b>Your account has the ability to post unrestricted HTML content.</b></label></p>';
		echo '<div id="bbpms-ohbother" style="display:';
		if ($bbpms_rem_ohbother) { echo 'none'; } else { echo 'block'; }
		echo '">';
			echo '<p style="text-indent:30px;"><input type="checkbox" name="bbpms-tick-ohbother" id="bbpms-tick-ohbother" value="bbpms-tick-ohbother" ';
			if ($bbpms_tick_ohbother) { echo 'checked'; }
			echo '>';
			echo '<label>Replace text <b>Oh bother! No topics were found here!</b> with : </label>';
			echo '<input type="text" name="bbpms-new-ohbother" id="bbpms-new-ohbother" value="' . $NewOhBother . '" size="60" />';
			echo '<label> (Activate, empty field and save to get the original message)</label></p>';
		
			echo '<p style="text-indent:30px;"><input type="checkbox" name="bbpms-tick-unreshtml" id="bbpms-tick-unreshtml" value="bbpms-tick-unreshtml" ';
			if ($bbpms_tick_unreshtml) { echo 'checked'; }
			echo '>';
			echo '<label>Replace text <b>Your account has the ability to post unrestricted HTML content.</b> with : </label>';
			echo '<input type="text" name="bbpms-new-unres-html" id="bbpms-new-unres-html" value="' . $NewUnresHTML . '" size="60" />';
			echo '<label> (Activate, empty field and save to get the original message)</label></p>';
		echo '</div>';
		
		echo '<p><input type="checkbox" name="bbpms-subscr-right" id="bbpms-subscr-right" value="bbpms-subscr-right" ';
		if ($bbpms_subscr_right) { echo 'checked'; }
		echo '><label for="bbpms-subscr-right">Move the <b>Subscribe</b> option of a forum to the right, not next to breadcrums</label></p>';
		
		echo '<p><input type="checkbox" name="bbpms-closed-nogrey" id="bbpms-closed-nogrey" value="bbpms-closed-nogrey" ';
		if ($bbpms_closed_nogrey) { echo 'checked'; }
		echo '><label for="bbpms-closed-nogrey">Do <b>not</b> grey out closed topics</label></p>';
		
		echo '<p><input type="checkbox" name="bbpms-reply-inverse" id="bbpms-reply-inverse" value="bbpms-reply-inverse" ';
		if ($bbpms_reply_inverse) { echo 'checked'; }
		echo '><label for="bbpms-reply-inverse">Most recent reply on top (inverse the sorting of replies to a topic).</label></p>';
		
		echo '<p><input type="checkbox" name="bbpms-rem-subf" id="bbpms-rem-subf" value="bbpms-rem-subf" ';
		if ($bbpms_rem_subf) { echo 'checked'; }
		echo '><label for="bbpms-rem-subf">Do not show the table with the list of subforums, only show the current forum and the topics.</label></p>';
		
		echo '<p><input type="checkbox" name="bbpms-rem-defstyle" id="bbpms-rem-defstyle" value="bbpms-rem-defstyle" ';
		if ($bbpms_rem_defstyle) { echo 'checked'; }
		echo '><label for="bbpms-rem-defstyle">Performance: Remove bbpress css style from all pages except forum pages</label></p>';
	}
			?>

			<h3>Show users</h3>
			<p><input type="text" name="usersperpage" id="usersperpage" value="<?php echo $perPage ?>" maxlength="3" size="3" /><label for="usersperpage">Users per page</label></p>
			<p><select name="showusers" id="showusers">
			<?php
			echo '<option value="all" ';
				if ($showwhat == 'all') {echo 'selected';}
				echo '>Show all users</option>';
			echo '<option value="nohidden" ';
				if ($showwhat == 'nohidden') {echo 'selected';}
				echo '>Show users from visible roles only</option>';
			echo '</select></p>
				';
			echo '<h3>Roles to hide</h3>
				';
			echo '<p><input type="hidden" name="all_roles" value="'.implode(',',$all_roles).'"></p>
				';
			echo '<p><input type="hidden" name="all_forum_ids" value="'.implode(',',$all_forum_ids).'"></p>
				';
				
			foreach ($all_roles as $myrole) {
				echo '<p><input type="checkbox" name="role-'.$myrole.'" id="role-'.$myrole.'" value="'.$myrole.'" ';
				if (strpos($hidden_roles, $myrole) !== FALSE) { echo 'checked'; }
				echo '><label for="role-'.$myrole.'">'.$myrole.'</label></p>
					';
			}
			echo '<h3>Forums to hide</h3>
				';
			foreach ($all_forums as $myforum) {
				echo '<p><input type="checkbox" name="forum-'.$myforum['id'].'" id="forum-'.$myforum['id'].'" value="'.$myforum['id'].'" ';
				if (strpos($hidden_forum_ids, strval($myforum['id'])) !== FALSE) { echo 'checked'; }
				echo '><label for="forum-'.$myforum['id'].'">'.$myforum['title'].'</label></p>
					';
				if ($myforum['subforums']) {
					foreach ($myforum['subforums'] as $mysubforum) {
						echo '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="forum-'.$mysubforum['id'].'" id="forum-'.$mysubforum['id'].'" value="'.$mysubforum['id'].'" ';
						if (strpos($hidden_forum_ids, strval($mysubforum['id'])) !== FALSE) { echo 'checked'; }
						echo '><label for="forum-'.$mysubforum['id'].'">'.$mysubforum['title'].'</label></p>
							';
					}
				}
			}

			?>

			<p><input type="submit" name="optssave" value="Save settings" /></p>
			
		</form>
		</div>
	<?php
}
?>