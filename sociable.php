<?php
/*
Plugin Name: Sociable .ro
Plugin URI: 
Description: Pluginul Sociable .ro. Include agregatoarele din Romania si serviciile de bookmarking social internationale ce pot fi folosite pentru continut in limba romana. 
Version: 1.0
Author: Paun Eugen
Author URI: http://twitter.com/unmicdrac

Copyright 2006 Peter Harkins (ph@malaprop.org)
Copyright 2008-2009 Joost de Valk (joost@yoast.com)
Copyright 2009-present Blogplay.com (info@blogplay.com)
Varianta in limba romana noiembrie 2009 Paun Eugen (paun.eugen@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Determine the location
 */
$sociablepluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

/**
 * For backwards compatibility, esc_attr was added in 2.8
 */
if (! function_exists('esc_attr')) {
	function esc_attr( $text ) {
		return attribute_escape( $text );
	}
}

/**
 * This function makes sure Sociable is able to load the different language files from
 * the i18n subfolder of the Sociable directory
 **/
function sociable_init_locale(){
	global $sociablepluginpath;
	load_plugin_textdomain('sociable', false, 'i18n');
}
add_filter('init', 'sociable_init_locale');


/**
 * @global array Contains all sites that Sociable supports, array items have 4 keys:
 * required favicon - the favicon for the site, a 16x16px PNG, to be found in the images subdirectory
 * required url - submit URL of the site, containing at least PERMALINK
 * description - description, used in several spots, but most notably as alt and title text for the link
 */
$sociable_known_sites = Array(

	'email' => Array(
		'favicon' => 'email_link.png',
		'url' => 'mailto:?subject=TITLE&amp;body=PERMALINK',
		'awesm_channel' => 'mailto',
		'supportsIframe' => false,
	),

	'Facebook' => Array(
		'favicon' => 'facebook.png',
		'awesm_channel' => 'facebook-post',
		'url' => 'http://www.facebook.com/share.php?u=PERMALINK&amp;t=TITLE',
	),
		
	'FTW.ro' => Array(
		'favicon' => 'ftw.png',
		'url' => 'http://www.ftw.ro/submit?url=PERMALINK&amp;title=TITLE',
		),
		
	'Problogger.ro' => Array(
		'favicon' => 'problogger.png',
		'url' => 'http://www.problogger.ro/submit?url=PERMALINK&amp;title=TITLE',
		),

	'Google' => Array (
		'favicon' => 'googlebookmark.png',
		'url' => 'http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=PERMALINK&amp;title=TITLE&amp;annotation=EXCERPT',
		'description' => 'Google Bookmarks',
		),
	
	'LinkedIn' => Array(
		'favicon' => 'linkedin.png',
		'url' => 'http://www.linkedin.com/shareArticle?mini=true&amp;url=PERMALINK&amp;title=TITLE&amp;source=BLOGNAME&amp;summary=EXCERPT',
		),

	'Posterous' => Array(
		'favicon' => 'posterous.png',
		'url' => 'http://posterous.com/share?linkto=PERMALINK&amp;title=TITLE&amp;selection=EXCERPT',
		),
	
	'PDF' => Array(
		'favicon' => 'pdf.png',
		'url' => 'http://www.printfriendly.com/print?url=PERMALINK&amp;partner=sociable',
		),
	
	'Print' => Array(
		'favicon' => 'printfriendly.png',
		'url' => 'http://www.printfriendly.com/print?url=PERMALINK&amp;partner=sociable',
		),
		
	'Proddit' => Array(
		'favicon' => 'proddit.png',
		'url' => 'http://proddit.com/submit?url=PERMALINK&amp;title=TITLE',
		),
	
	'RSS' => Array(
		'favicon' => 'rss.png',
		'url' => 'FEEDLINK',
		),
	
	'Technorati' => Array(
		'favicon' => 'technorati.png',
		'url' => 'http://technorati.com/faves?add=PERMALINK',
	),

	'Tumblr' => Array(
		'favicon' => 'tumblr.png',
		'url' => 'http://www.tumblr.com/share?v=3&amp;u=PERMALINK&amp;t=TITLE&amp;s=EXCERPT',
		'supportsIframe' => false,
	),
	
	'Twitter' => Array(
		'favicon' => 'twitter.png',
		'awesm_channel' => 'twitter',
		'url' => 'http://twitter.com/home?status=TITLE%20-%20PERMALINK',
		'supportsIframe' => false,
	),

	'Yahoo! Bookmarks' => Array(
		'favicon' => 'yahoomyweb.png',
		'url' => 'http://bookmarks.yahoo.com/toolbar/savebm?u=PERMALINK&amp;t=TITLE&opener=bm&amp;ei=UTF-8&amp;d=EXCERPT',
		'supportsIframe' => false,
	),

	'Adauga la favorite' => Array(
	 	'favicon' => 'addtofavorites.png',
	 	'url' => 'javascript:AddToFavorites();',
	 	'supportsIframe' => false,
	 ),
    
);

/**
 * Returns the Sociable links list.
 *
 * @param array $display optional list of links to return in HTML
 * @global $sociable_known_sites array the list of sites that Sociable uses
 * @global $sociablepluginpath string the path to the plugin
 * @global $wp_query object the WordPress query object
 * @return string $html HTML for links list.
 */
function sociable_html($display=array()) {
	global $sociable_known_sites, $sociablepluginpath, $wp_query, $post; 

	if (get_post_meta($post->ID,'_sociableoff',true)) {
		return "";
	}

	/**
	 * Make it possible for other plugins or themes to add buttons to Sociable
	 */
	$sociable_known_sites = apply_filters('sociable_known_sites',$sociable_known_sites);

	$active_sites = get_option('sociable_active_sites');

	// If a path is specified where Sociable should find its images, use that, otherwise, 
	// set the image path to the images subdirectory of the Sociable plugin.
	// Image files need to be png's.
	$imagepath = get_option('sociable_imagedir');
	if ($imagepath == "")
		$imagepath = $sociablepluginpath.'images/';		

	// if no sites are specified, display all active
	// have to check $active_sites has content because WP
	// won't save an empty array as an option
	if (empty($display) and $active_sites)
		$display = $active_sites;
	// if no sites are active, display nothing
	if (empty($display))
		return "";

	// Load the post's and blog's data
	$blogname 	= urlencode(get_bloginfo('name')." ".get_bloginfo('description'));
	$blogrss	= get_bloginfo('rss2_url'); 
	$post 		= $wp_query->post;
	
	// Grab the excerpt, if there is no excerpt, create one
	$excerpt	= urlencode(strip_tags(strip_shortcodes($post->post_excerpt)));
	if ($excerpt == "") {
		$excerpt = urlencode(substr(strip_tags(strip_shortcodes($post->post_content)),0,250));
	}
	// Clean the excerpt for use with links
	$excerpt	= str_replace('+','%20',$excerpt);
	$permalink 	= urlencode(get_permalink($post->ID));
	$title 		= str_replace('+','%20',urlencode($post->post_title));
	
	$rss 		= urlencode(get_bloginfo('ref_url'));

	// Start preparing the output
	$html = "\n<div class=\"sociable\">\n";
	
	// If a tagline is set, display it above the links list
	$tagline = get_option("sociable_tagline");
	if ($tagline != "") {
		$html .= "<div class=\"sociable_tagline\">\n";
		$html .= stripslashes($tagline);
		$html .= "\n</div>";
	}
	
	/**
	 * Start the list of links
	 */
	$html .= "\n<ul>\n";

	$i = 0;
	$totalsites = count($display);
	foreach($display as $sitename) {
		/**
		 * If they specify an unknown or inactive site, ignore it.
		 */
		if (!in_array($sitename, $active_sites))
			continue;

		$site = $sociable_known_sites[$sitename];

		$url = $site['url'];
		$url = str_replace('TITLE', $title, $url);
		$url = str_replace('RSS', $rss, $url);
		$url = str_replace('BLOGNAME', $blogname, $url);
		$url = str_replace('EXCERPT', $excerpt, $url);
		$url = str_replace('FEEDLINK', $blogrss, $url);
		
		if (isset($site['description']) && $site['description'] != "") {
			$description = $site['description'];
		} else {
			$description = $sitename;
		}

		if (get_option('sociable_awesmenable') == true &! empty($site['awesm_channel']) ) {
			/**
			 * if awe.sm is enabled and it is an awe.sm supported site, use awe.sm
			 */
			$permalink = str_replace('&', '%2526', $permalink); 
			$destination = str_replace('PERMALINK', 'TARGET', $url);
			$destination = str_replace('&amp;', '%26', $destination);
			$channel = urlencode($site['awesm_channel']);

			$parentargument = '';
			if ($_GET['awesm']) {
				/**
				 * if the page was arrived at through an awe.sm URL, make that the parent
				 */ 
				$parent = $_GET['awesm'];
				$parentargument = '&p=' . $parent;
			} 

			if (strpos($channel, 'direct') != false) {
				$url = $sociablepluginpath.'awesmate.php?c='.$channel.'&t='.$permalink.'&d='.$destination.'&dir=true'.$parentargument;
			} else {
				$url = $sociablepluginpath.'awesmate.php?c='.$channel.'&t='.$permalink.'&d='.$destination.$parentargument;	
			}
		} else {
			/**
			 * if awe.sm is not used, simply replace PERMALINK with $permalink
			 */ 
			$url = str_replace('PERMALINK', $permalink, $url);		
		}

		/**
		 * Start building each list item. They're build up separately to allow filtering by other
		 * plugins.
		 * Give the first and last list item in the list an extra class to allow for cool CSS tricks
		 */
		if ($i == 0) {
			$link = '<li class="sociablefirst">';
		} else if ($totalsites == ($i+1)) {
			$link = '<li class="sociablelast">';
		} else {
			$link = '<li>';
		}
		
		/**
		 * Start building the link, nofollow it to make sure Search engines don't follow it, 
		 * and optionally add target=_blank to open in a new window if that option is set in the 
		 * backend.
		 */
		$link .= '<a ';
		$link .= ($sitename=="Blogplay") ? '' : 'rel="nofollow" ';
		//$link .= ' id="'.esc_attr(strtolower(str_replace(" ", "", $sitename))).'" ';
		/**
		 * Use the iframe option if it is enabled and supported by the service/site
		 */
		if (get_option('sociable_useiframe') && !isset($site['supportsIframe'])) {
			$iframeWidth = get_option('sociable_iframewidth',900);
			$iframeHeight = get_option('sociable_iframeheight',500);
			$link .= 'class="thickbox" href="' . $url . "?TB_iframe=true&amp;height=$iframeHeight&amp;width=$iframeWidth\">";
		} else {
			if(!($sitename=="Add to favorites")) {
				if (get_option('sociable_usetargetblank')) {
					$link .= " target=\"_blank\"";
				}
				$link .= " href=\"".$url."\" title=\"$description\">";
			} else {
				$link .= " href=\"$url\" title=\"$description\">";			
			} 
		}
		
		/**
		 * If the option to use text links is enabled in the backend, display a text link, otherwise, 
		 * display an image.
		 */
		if (get_option('sociable_usetextlinks')) {
			$link .= $description;
		} else {
			/**
			 * If site doesn't have sprite information
			 */
			if (!isset($site['spriteCoordinates']) || get_option('sociable_disablesprite',false) || is_feed()) {
				if (strpos($site['favicon'], 'http') === 0) {
					$imgsrc = $site['favicon'];
				} else {
					$imgsrc = $imagepath.$site['favicon'];
				}
				$link .= "<img src=\"".$imgsrc."\" title=\"$description\" alt=\"$description\"";
				$link .= (!get_option('sociable_disablealpha',false)) ? " class=\"sociable-hovers" : "";
			/**
			 * If site has sprite information use it
			 */
			} else {
				$imgsrc = $imagepath."services-sprite.gif";
				$services_sprite_url = $imagepath . "services-sprite.png";
				$spriteCoords = $site['spriteCoordinates'];
				$link .= "<img src=\"".$imgsrc."\" title=\"$description\" alt=\"$description\" style=\"width: 16px; height: 16px; background: transparent url($services_sprite_url) no-repeat; background-position:-$spriteCoords[0]px -$spriteCoords[1]px\"";
				$link .= (!get_option('sociable_disablealpha',false)) ? " class=\"sociable-hovers" : "";
			}
			if (isset($site['class']) && $site['class']) {
				$link .= (!get_option('sociable_disablealpha',false)) ? " sociable_{$site['class']}\"" : " class=\"sociable_{$site['class']}\"";
			} else {
				$link .= (!get_option('sociable_disablealpha',false)) ? "\"" : "";
			}
			$link .= " />";
		}
		$link .= "</a></li>";
		
		/**
		 * Add the list item to the output HTML, but allow other plugins to filter the content first.
		 * This is used for instance in the Google Analytics for WordPress plugin to track clicks
		 * on Sociable links.
		 */
		$html .= "\t".apply_filters('sociable_link',$link)."\n";
		$i++;
	}

	$html .= "</ul>\n</div>\n";

	return $html;
}

/**
 * Hook the_content to output html if we should display on any page
 */
$sociable_contitionals = get_option('sociable_conditionals');
if (is_array($sociable_contitionals) and in_array(true, $sociable_contitionals)) {
	add_filter('the_content', 'sociable_display_hook');
	add_filter('the_excerpt', 'sociable_display_hook');
	
	/**
	 * Loop through the settings and check whether Sociable should be outputted.
	 */
	function sociable_display_hook($content='') {
		$conditionals = get_option('sociable_conditionals');
		if ((is_home()     and $conditionals['is_home']) or
		    (is_single()   and $conditionals['is_single']) or
		    (is_page()     and $conditionals['is_page']) or
		    (is_category() and $conditionals['is_category']) or
			(is_tag() 	   and $conditionals['is_tag']) or
		    (is_date()     and $conditionals['is_date']) or
			(is_author()   and $conditionals['is_author']) or
		    (is_search()   and $conditionals['is_search'])) {
			$content .= sociable_html();
		} elseif ((is_feed() and $conditionals['is_feed'])) {
			$sociable_html = sociable_html();
			$sociable_html = strip_tags($sociable_html,"<a><img>");
			$content .= $sociable_html . "<br/><br/>";
		}
		return $content;
	}
}

/**
 * Set the default settings on activation on the plugin.
 */
function sociable_activation_hook() {
	global $wpdb;
	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'sociableoff'");
	return sociable_restore_config(false);
}
register_activation_hook(__FILE__, 'sociable_activation_hook');

/**
 * Add the Sociable menu to the Settings menu
 * @param boolean $force if set to true, force updates the settings.
 */
function sociable_restore_config($force=false) {
	global $sociable_known_sites;

	if ($force or !is_array(get_option('sociable_active_sites')))
		update_option('sociable_active_sites', array(
			'Print',
			'PDF',
			'FTW.ro',
			'Problogger.ro',
			'Proddit',
			'Twitter',
			'Facebook',
			'Google'
		));

	if ($force or !is_string(get_option('sociable_tagline')))
		update_option('sociable_tagline', "<strong>" . __("Adauga", 'sociable') . "</strong>");

	if ($force or !is_array(get_option('sociable_conditionals')))
		update_option('sociable_conditionals', array(
			'is_home' => False,
			'is_single' => True,
			'is_page' => True,
			'is_category' => False,
			'is_tag' => False,
			'is_date' => False,
			'is_search' => False,
			'is_author' => False,
			'is_feed' => False,
		));

	if ( $force OR !( get_option('sociable_usecss') ) )
		update_option('sociable_usecss', true);
		
	if ( $force or !( get_option('sociable_iframewidth')))	{
		update_option('sociable_iframewidth',900);
	}
	if ( $force or !( get_option('sociable_iframeheight')))	{
		update_option('sociable_iframeheight',500);
	}
	
	if ( $force or !( get_option('sociable_disablealpha')))	{
		update_option('sociable_disablealpha',false);
	}
	
	if ( $force or !( get_option('sociable_disablesprite')) ) {
		update_option('sociable_disablesprite',true);
	}
	
	if ( $force or !( get_option('sociable_disablewidget')) ) {
		update_option('sociable_disablewidget',false);
	}
	
}

/**
 * Add the Sociable menu to the Settings menu
 */
function sociable_admin_menu() {
	add_options_page('Sociable .ro', 'Sociable .ro', 8, 'Sociable', 'sociable_submenu');
}
add_action('admin_menu', 'sociable_admin_menu');

/**
 * Make sure the required javascript files are loaded in the Sociable backend, and that they are only
 * loaded in the Sociable settings page, and nowhere else.
 */
function sociable_admin_js() {
	if (isset($_GET['page']) && $_GET['page'] == 'Sociable') {
		global $sociablepluginpath;
		
		wp_enqueue_script('jquery'); 
		wp_enqueue_script('jquery-ui-core',false,array('jquery')); 
		wp_enqueue_script('jquery-ui-sortable',false,array('jquery','jquery-ui-core')); 
		wp_enqueue_script('sociable-js',$sociablepluginpath.'sociable-admin.js', array('jquery','jquery-ui-core','jquery-ui-sortable')); 
	}
}
add_action('admin_print_scripts', 'sociable_admin_js');

/**
 * Make sure the required stylesheet is loaded in the Sociable backend, and that it is only
 * loaded in the Sociable settings page, and nowhere else.
 */
function sociable_admin_css() {
	global $sociablepluginpath;
	if (isset($_GET['page']) && $_GET['page'] == 'Sociable')
		wp_enqueue_style('sociable-css',$sociablepluginpath.'sociable-admin.css'); 
}
add_action('admin_print_styles', 'sociable_admin_css');

/**
 * If Wists is active, load it's js file. This is the only site that historically has had a JS file
 * in Sociable. For all other sites this has so far been refused.
 */
function sociable_js() {
	if (get_option('sociable_useiframe')==true)
	{
		global $sociablepluginpath;
		wp_enqueue_script('jquery');
		wp_enqueue_script('sociable-thickbox',$sociablepluginpath.'thickbox/thickbox.js',array('jquery')); 	
	}
	if (in_array('Add to favorites',get_option('sociable_active_sites'))) {
		global $sociablepluginpath;
		wp_enqueue_script('sociable-addtofavorites',$sociablepluginpath.'addtofavorites.js');
	}
	if (in_array('Wists', get_option('sociable_active_sites'))) {
		global $sociablepluginpath;
		wp_enqueue_script('sociable-wists',$sociablepluginpath.'wists.js'); 
	}	
}
add_action('wp_print_scripts', 'sociable_js');

/**
 * If the user has the (default) setting of using the Sociable CSS, load it.
 */
function sociable_css() {
	if (get_option('sociable_useiframe') == true) {
		global $sociablepluginpath;
		wp_enqueue_style('sociable-thickbox-css',$sociablepluginpath.'thickbox/thickbox.css');
	}
	if (get_option('sociable_usecss') == true) {
		global $sociablepluginpath;
		wp_enqueue_style('sociable-front-css',$sociablepluginpath.'sociable.css'); 
	}
}
add_action('wp_print_styles', 'sociable_css');

/**
 * Update message, used in the admin panel to show messages to users.
 */
function sociable_message($message) {
	echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
}

/**
 * Displays a checkbox that allows users to disable Sociable on a
 * per post or page basis.
 */
function sociable_meta() {
	global $post;
	$sociableoff = false;
	if (get_post_meta($post->ID,'_sociableoff',true)) {
		$sociableoff = true;
	} 
	?>
	<input type="checkbox" id="sociableoff" name="sociableoff" <?php checked($sociableoff); ?>/> <label for="sociableoff"><?php _e('Sociable disabled?','sociable') ?></label>
	<?php
}

/**
 * Add the checkbox defined above to post and page edit screens.
 */
function sociable_meta_box() {
	add_meta_box('sociable','Sociable','sociable_meta','post','side');
	add_meta_box('sociable','Sociable','sociable_meta','page','side');
}
add_action('admin_menu', 'sociable_meta_box');

/**
 * If the post is inserted, set the appropriate state for the sociable off setting.
 */
function sociable_insert_post($pID) {
	if (isset($_POST['sociableoff'])) {
		if (!get_post_meta($post->ID,'_sociableoff',true))
			add_post_meta($pID, '_sociableoff', true, true);
	} else {
		if (get_post_meta($post->ID,'_sociableoff',true))
			delete_post_meta($pID, '_sociableoff');
	}
}
add_action('wp_insert_post', 'sociable_insert_post');

/**
 * Displays the Sociable admin menu, first section (re)stores the settings.
 */
function sociable_submenu() {
	global $sociable_known_sites, $sociable_date, $sociablepluginpath;

	$sociable_known_sites = apply_filters('sociable_known_sites',$sociable_known_sites);
	
	if (isset($_REQUEST['restore']) && $_REQUEST['restore']) {
		check_admin_referer('sociable-config');
		sociable_restore_config(true);
		sociable_message(__("Configurarile au fost resetate la cele initiale.", 'sociable'));
	} else if (isset($_REQUEST['save']) && $_REQUEST['save']) {
		check_admin_referer('sociable-config');
		$active_sites = Array();
		if (!$_REQUEST['active_sites'])
			$_REQUEST['active_sites'] = Array();
		foreach($_REQUEST['active_sites'] as $sitename=>$dummy)
			$active_sites[] = $sitename;
		update_option('sociable_active_sites', $active_sites);
		/**
		 * Have to delete and re-add because update doesn't hit the db for identical arrays
		 * (sorting does not influence associated array equality in PHP)
		 */
		delete_option('sociable_active_sites', $active_sites);
		add_option('sociable_active_sites', $active_sites);

		foreach ( array('usetargetblank', 'useiframe', 'disablealpha', 'disablesprite', 'awesmenable', 'usecss', 'usetextlinks', 'disablewidget') as $val ) {
			if ( isset($_POST[$val]) && $_POST[$val] )
				update_option('sociable_'.$val,true);
			else
				update_option('sociable_'.$val,false);
		}
		
		if (isset($_POST['iframewidth']) && is_numeric($_POST['iframewidth'])) {
			update_option('sociable_iframewidth',$_POST['iframewidth']);
		} else {
			update_option('sociable_iframewidth',900);
		}
		if (isset($_POST['iframeheight']) && is_numeric($_POST['iframeheight'])) {
			update_option('sociable_iframeheight',$_POST['iframeheight']);
		} else {
			update_option('sociable_iframeheight',500);
		}
		
		foreach ( array('awesmapikey', 'tagline', 'imagedir') as $val ) {
			if ( !$_POST[$val] )
				update_option( 'sociable_'.$val, '');
			else
				update_option( 'sociable_'.$val, $_POST[$val] );
		}
		
		if (isset($_POST["imagedir"]) && !trim($_POST["imagedir"]) == "") {
			update_option('sociable_disablesprite',true);
		}
		
		/**
		 * Update conditional displays
		 */
		$conditionals = Array();
		if (!$_POST['conditionals'])
			$_POST['conditionals'] = Array();
		
		$curconditionals = get_option('sociable_conditionals');
		if (!array_key_exists('is_feed',$curconditionals)) {
			$curconditionals['is_feed'] = false;
		}
		foreach($curconditionals as $condition=>$toggled)
			$conditionals[$condition] = array_key_exists($condition, $_POST['conditionals']);
			
		update_option('sociable_conditionals', $conditionals);

		sociable_message(__("Modificarile au fost salvate.", 'sociable'));
	}
	
	/**
	 * Show active sites first and in the right order.
	 */
	$active_sites = get_option('sociable_active_sites');
	$active = Array(); 
	$disabled = $sociable_known_sites;
	foreach( $active_sites as $sitename ) {
		$active[$sitename] = $disabled[$sitename];
		unset($disabled[$sitename]);
	}
	uksort($disabled, "strnatcasecmp");
	
	/**
	 * Display options.
	 */
?>
<form action="<?php echo attribute_escape( $_SERVER['REQUEST_URI'] ); ?>" method="post">
<?php
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('sociable-config');
?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e("Optiuni Sociable", 'sociable'); ?></h2>
	<table class="form-table">
	<tr>
		<th>
			<?php _e("Site-uri", "sociable"); ?>:<br/>
			<small><?php _e("Bifeaza site-urile pe care le doresti afisate pe blogul tau. Drag & drop pentru a modifica ordinea de afisare.", 'sociable'); ?></small>
		</th>
		<td>
			<div style="width: 100%; height: 100%">
			<ul id="sociable_site_list">
				<?php foreach (array_merge($active, $disabled) as $sitename=>$site) { ?>
					<li id="<?php echo $sitename; ?>"
						class="sociable_site <?php echo (in_array($sitename, $active_sites)) ? "active" : "inactive"; ?>">
						<input
							type="checkbox"
							id="cb_<?php echo $sitename; ?>"
							name="active_sites[<?php echo $sitename; ?>]"
							<?php echo (in_array($sitename, $active_sites)) ? ' checked="checked"' : ''; ?>
						/>
						<?php
						$imagepath = get_option('sociable_imagedir');
						
						if ($imagepath == "") {
							$imagepath = $sociablepluginpath.'images/';
						} else {		
							$imagepath .= (substr($imagepath,strlen($imagepath)-1,1)=="/") ? "" : "/";
						}
						
						if (!isset($site['spriteCoordinates']) || get_option('sociable_disablesprite')) {
							if (strpos($site['favicon'], 'http') === 0) {
								$imgsrc = $site['favicon'];
							} else {
								$imgsrc = $imagepath.$site['favicon'];
							}
							echo "<img src=\"$imgsrc\" width=\"16\" height=\"16\" />";
						} else {
							$imgsrc = $imagepath."services-sprite.gif";
							$services_sprite_url = $imagepath . "services-sprite.png";
							$spriteCoords = $site['spriteCoordinates'];
							echo "<img src=\"$imgsrc\" width=\"16\" height=\"16\" style=\"background: transparent url($services_sprite_url) no-repeat; background-position:-$spriteCoords[0]px -$spriteCoords[1]px\" />";
						}
						
						echo $sitename; ?>
					</li>
				<?php } ?>
			</ul>
			</div>
			<input type="hidden" id="site_order" name="site_order" value="<?php echo join('|', array_keys($sociable_known_sites)) ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Dezactiveaza masca alpha pe bara de icoane?", "sociable"); ?>
		</th>
		<td>
			<input type="checkbox" name="disablealpha" <?php checked( get_option('sociable_disablealpha'), true ) ; ?> />
		</td>
	</tr>	
	<tr>
		<th scope="row" valign="top">
			<?php _e("Tagline", "sociable"); ?>
		</th>
		<td>
			<?php _e("Modifica mai jos textul ce va fi afisat inainte de bara de icoane. Pentru o customizare avansata, copiaza continutul fisierului <em>sociable.css</em> din directorului pluginului Sociable in fisierul <em>style.css</em> al temei folosite in mod curent si dezactiveaza utilizarea stylesheet ce vine impreuna cu pluginul.", 'sociable'); ?><br/>
			<input size="80" type="text" name="tagline" value="<?php echo attribute_escape(stripslashes(get_option('sociable_tagline'))); ?>" />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Afisare:", "sociable"); ?>
		</th>
		<td>
			<?php _e("Bara de icoane apare la sfarsitul fiecarui articol, iar articole pot fi afisate pe diverse pagini. In functie de audienta blogului tau si de tema folosita, poate nu ar fi o idee buna sa o afisezi chiar pe toate paginile .", 'sociable'); ?><br/>
			<br/>
			<?php
			/**
			 * Load conditions under which Sociable displays
			 */
			$conditionals 	= get_option('sociable_conditionals');
			?>
			<input type="checkbox" name="conditionals[is_home]"<?php checked($conditionals['is_home']); ?> /> <?php _e("Prima pagina a blogului", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_single]"<?php checked($conditionals['is_single']); ?> /> <?php _e("Pagina individuala a articolului", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_page]"<?php checked($conditionals['is_page']); ?> /> <?php _e('Fiecare pagina', 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_category]"<?php checked($conditionals['is_category']); ?> /> <?php _e("Arhiva categoriilor", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_tag]"<?php checked($conditionals['is_tag']); ?> /> <?php _e("Arhiva cuvintelor cheie", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_date]"<?php checked($conditionals['is_date']); ?> /> <?php _e("Arhiva lunara", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_author]"<?php checked($conditionals['is_author']); ?> /> <?php _e("Arhiva unui autor", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_search]"<?php checked($conditionals['is_search']); ?> /> <?php _e("Rezultatele cautarii", 'sociable'); ?><br/>
			<input type="checkbox" name="conditionals[is_feed]"<?php checked($conditionals['is_feed']); ?> /> <?php _e("Feed RSS", 'sociable'); ?><br/>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Folosire CSS:", "sociable"); ?>
		</th>
		<td>
			<input type="checkbox" name="usecss" <?php checked( get_option('sociable_usecss'), true ); ?> /> <?php _e("Doresti sa folosesti stylesheet ce vine impreuna cu pluginul?", "sociable"); ?>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Folosire linkuri text:", "sociable"); ?>
		</th>
		<td>
			<input type="checkbox" name="usetextlinks" <?php checked( get_option('sociable_usetextlinks'), true ); ?> /> <?php _e("Doresti sa folosesti link-uri text in locul imaginilor?", "sociable"); ?>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Director imagini", "sociable"); ?>
		</th>
		<td>
			<?php _e("Pluginul Sociable vine impreuna cu propriul set de icoane, dar daca doresti sa le inlocuiesti pe acestea cu altele personale, introdu URL pentru directorul ce le contine, asigurandu-te ca fisierele au aceeasi denumire ca acelea ce au venit impreuna cu pluginul.", 'sociable'); ?><br/>
			<input size="80" type="text" name="imagedir" value="<?php echo attribute_escape(stripslashes(get_option('sociable_imagedir'))); ?>" /><br />
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Deschide linkurile intr-o fereastra noua:", "sociable"); ?>
		</th>
		<td>
			<input type="checkbox" name="usetargetblank" <?php checked( get_option('sociable_usetargetblank'), true ); ?> /> <?php _e("Doresti sa aplici link-urilor atributul <code>target=_blank</code> ? (Forteaza deschirea link-urilor intr-o fereastra noua)", "sociable"); ?>
		</td>		
	</tr>
	<tr>
		<th scope="row" valign="top">
			<?php _e("Dezactiveaza widgetul CNet.ro din dashboard:", "sociable"); ?>
		</th>
		<td>
			<input type="checkbox" name="disablewidget" <?php checked( get_option('sociable_disablewidget'), true ); ?> />
		</td>		
	</tr>	
	<tr>
		<td>&nbsp;</td>
		<td>
			<span class="submit"><input name="save" value="<?php _e("Salveaza modificari", 'sociable'); ?>" type="submit" /></span>
			<span class="submit"><input name="restore" value="<?php _e("Restaureaza configurarile initiale", 'sociable'); ?>" type="submit"/></span>
		</td>
	</tr>
</table>

<h2><?php _e('Credite','sociable'); ?></h2>
<p><?php _e('<a href="http://blogplay.com/plugin/">Sociable</a> a fost creat initial de  catre <a href="http://push.cx/">Peter Harkins</a> si a fost actualizat/intretinut de la inceputul anului 2008 de catre <a href="http://yoast.com/">Joost de Valk</a>. Incepand cu septembrie 2009, noua pagina oficiala a pluginului Sociable este <a href="http://blogplay.com">BlogPlay.com</a>. Pluginul este lansat sub licenta GNU GPL version 2.','Sociable'); ?></p>
<h2><?php _e('Varianta in limba romana','sociable'); ?></h2>
<p><?php _e('Realizata de <a href="http://twitter.com/unmicdrac">Paun Eugen</a> si lansata prin intermediul CNet.ro. Pentru suport, cereri de adaugare a noi agregatoare, etc. folositi adresa de mail paun.eugen [at]  gmail.com','sociable'); ?></p>

</div>
</form>
<?php
}

/**
 * Add an icon for the Sociable plugin's settings page to the dropdown for Ozh's admin dropdown menu
 */
function sociable_add_ozh_adminmenu_icon( $hook ) {
	static $sociableicon;
	if (!$sociableicon) {
		$sociableicon = WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)). '/book_add.png';
	}
	if ($hook == 'Sociable') return $sociableicon;
	return $hook;
}
add_filter( 'ozh_adminmenu_icon', 'sociable_add_ozh_adminmenu_icon' );				

/**
 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the
 * settings page.
 */
function sociable_filter_plugin_actions( $links, $file ){
	// Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
	
	if ( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=Sociable">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}
add_filter( 'plugin_action_links', 'sociable_filter_plugin_actions', 10, 2 );

/**
 * Add the Cnet.ro RSS feed to the WordPress dashboard
 */
if (!function_exists('blogplay_db_widget')) {
	function blogplay_text_limit( $text, $limit, $finish = ' [&hellip;]') {
		if( strlen( $text ) > $limit ) {
	    	$text = substr( $text, 0, $limit );
			$text = substr( $text, 0, - ( strlen( strrchr( $text,' ') ) ) );
			$text .= $finish;
		}
		return $text;
	}
	
	function blogplay_db_widget($image = 'normal', $num = 3, $excerptsize = 250, $showdate = true) {
		require_once(ABSPATH.WPINC.'/rss.php');  
		if ( $rss = fetch_rss( 'http://feeds.feedburner.com/cnetro?format=xml' ) ) {
			echo '<div class="rss-widget">';
			echo '<ul>';
			$rss->items = array_slice( $rss->items, 0, $num );
			foreach ( (array) $rss->items as $item ) {
				echo '<li>';
				echo '<a class="rsswidget" href="'.clean_url( $item['link'], $protocolls=null, 'display' ).'">'. htmlentities($item['title']) .'</a> ';
				if ($showdate)
					echo '<span class="rss-date">'. date('F j, Y', strtotime($item['pubdate'])) .'</span>';
				echo '<div class="rssSummary">'. blogplay_text_limit($item['summary'],$excerptsize) .'</div>';
				echo '</li>';
			}
			echo '</ul>';
			echo '<div style="border-top: 1px solid #ddd; padding-top: 10px; text-align:center;">';
			echo '<a href="http://feeds.feedburner.com/cnetro?format=xml"><img src="'.get_bloginfo('wpurl').'/wp-includes/images/rss.png" alt=""/> Abonare la feed-ul complet CNet.ro</a>';
			if ($image == 'normal') {
				echo ' &nbsp; &nbsp; &nbsp; ';
			} else {
				echo '<br/><br/>';
			}
			echo '<a href="http://www.cnet.ro/abonamente/"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/sociable/images/mail.png" alt=""/> Abonare prin mail la articolele de pe CNet.ro</a>';
			echo '</div>';
			echo '</div>';
		}
	}
 
	function blogplay_widget_setup() {
	    wp_add_dashboard_widget( 'blogplay_db_widget' , 'Ultimul articol de pe CNet.ro' , 'blogplay_db_widget');
	}
 
	if (!get_option('sociable_disablewidget',false)) {
		add_action('wp_dashboard_setup', 'blogplay_widget_setup');
	}
}
?>