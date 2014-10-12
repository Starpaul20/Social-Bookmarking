<?php
/**
 * Social Bookmarking
 * Copyright 2011 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Neat trick for caching our custom template(s)
if(my_strpos($_SERVER['PHP_SELF'], 'showthread.php'))
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'showthread_bookmarks,showthread_bookmarks_item';
}

// Tell MyBB when to run the hooks
$plugins->add_hook("showthread_start", "socialbookmark_run");

$plugins->add_hook("admin_config_menu", "socialbookmark_admin_menu");
$plugins->add_hook("admin_config_action_handler", "socialbookmark_admin_action_handler");
$plugins->add_hook("admin_config_permissions", "socialbookmark_admin_permissions");
$plugins->add_hook("admin_tools_get_admin_log_action", "socialbookmark_admin_adminlog");

// The information that shows up on the plugin manager
function socialbookmark_info()
{
	global $lang;
	$lang->load("config_bookmarks");

	return array(
		"name"				=> $lang->socialbookmark_info_name,
		"description"		=> $lang->socialbookmark_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is installed.
function socialbookmark_install()
{
	global $db;
	socialbookmark_uninstall();
	$collation = $db->build_create_table_collation();

	$db->write_query("CREATE TABLE ".TABLE_PREFIX."bookmarks (
				bid int(10) unsigned NOT NULL auto_increment,
				name varchar(120) NOT NULL default '',
				link varchar(255) NOT NULL default '',
				image varchar(220) NOT NULL default '',
				disporder smallint(5) NOT NULL default '0',
				active int(1) NOT NULL default '1',
				PRIMARY KEY(bid)
			) ENGINE=MyISAM{$collation}");
}

// Checks to make sure plugin is installed
function socialbookmark_is_installed()
{
	global $db;
	if($db->table_exists("bookmarks"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function socialbookmark_uninstall()
{
	global $db;
	if($db->table_exists("bookmarks"))
	{
		$db->drop_table("bookmarks");
	}
}

// This function runs when the plugin is activated.
function socialbookmark_activate()
{
	global $db;

	// Insert settings
	$query = $db->simple_select("settinggroups", "gid", "name='showthread'");
	$gid = $db->fetch_field($query, "gid");

	$insertarray = array(
		'name' => 'showbookmarking',
		'title' => 'Show Social Bookmarking',
		'description' => 'The Social Bookmarking table allows for users to bookmark threads to various bookmarking sites.',
		'optionscode' => 'onoff',
		'value' => 1,
		'disporder' => 15,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'bookmarking_number',
		'title' => 'Number of Bookmarks per row',
		'description' => 'The number of social bookmarks to display on a single row of the bookmark table. It is recommended that this value be no higher than 10.',
		'optionscode' => 'text',
		'value' => 4,
		'disporder' => 16,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	rebuild_settings();

	// Inserts templates
	$insert_array = array(
		'title'		=> 'showthread_bookmarks',
		'template'	=> $db->escape_string('<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><span class="smalltext"><strong>{$lang->bookmarks}</strong></span></td>
</tr>
<tr>
<td class="trow1" border="0" width="100%">
<ul style="list-style-type:none; margin:0px; padding:1px;">
{$bookmarklist}
</ul>
</td>
</tr>
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'showthread_bookmarks_item',
		'template'	=> $db->escape_string('<li style="width:{$value}%; float:left;"><a href="{$bookmark[\'link\']}"><img src="{$bookmark[\'image\']}" alt="{$alt_submit}">&nbsp;{$bookmark[\'name\']}</a></li>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	// Update templates
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$quickreply}')."#i", '{$socialbookmarks}{$quickreply}');

	change_admin_permission('config', 'bookmarks');
}

// This function runs when the plugin is deactivated.
function socialbookmark_deactivate()
{
	global $db;
	$db->delete_query("settings", "name IN('showbookmarking','bookmarking_number')");
	$db->delete_query("templates", "title IN('showthread_bookmarks','showthread_bookmarks_item')");
	rebuild_settings();

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", "#".preg_quote('{$socialbookmarks}')."#i", '', 0);

	change_admin_permission('config','bookmarks', -1);
}

// Bookmark box on showthread page
function socialbookmark_run()
{
	global $db, $mybb, $templates, $theme, $lang, $thread, $socialbookmarks;
	$lang->load("admin/config_bookmarks");

	if($mybb->settings['showbookmarking'] != 0)
	{
		$bookmarkcount = 0;
		$query = $db->simple_select("bookmarks", "*", "active='1'", array('order_by' => 'disporder', 'order_dir' => 'ASC'));
		while($bookmark = $db->fetch_array($query))
		{
			$tid = get_thread_link($thread['tid']);
			$bookmark['link'] = str_replace("{url}", "{$mybb->settings['bburl']}/{$tid}", $bookmark['link']);
			$bookmark['link'] = str_replace("{title}", $thread['subject'], $bookmark['link']);
			$alt_submit = $lang->sprintf($lang->submit_thread_to, $bookmark['name']);
			$value = 100/$mybb->settings['bookmarking_number'];
			$bookmark['name'] = htmlspecialchars_uni($bookmark['name']);
			eval("\$bookmarklist .= \"".$templates->get('showthread_bookmarks_item')."\";");
			++$bookmarkcount;
		}

		if($bookmarkcount > 0)
		{
			eval("\$socialbookmarks = \"".$templates->get('showthread_bookmarks')."\";");
		}
	}
}

// Add bookmark manage section in Admin CP
function socialbookmark_admin_menu($sub_menu)
{
	global $lang;
	$lang->load("config_bookmarks");

	$sub_menu['210'] = array('id' => 'bookmarks', 'title' => $lang->social_bookmarks, 'link' => 'index.php?module=config-bookmarks');

	return $sub_menu;
}

function socialbookmark_admin_action_handler($actions)
{
	$actions['bookmarks'] = array('active' => 'bookmarks', 'file' => 'bookmarks.php');

	return $actions;
}

function socialbookmark_admin_permissions($admin_permissions)
{
	global $db, $mybb, $lang;
	$lang->load("config_bookmarks");

	$admin_permissions['bookmarks'] = $lang->can_manage_social_bookmarks;

	return $admin_permissions;
}

// Admin Log display
function socialbookmark_admin_adminlog($plugin_array)
{
	global $lang;
	$lang->load("config_bookmarks");

	return $plugin_array;
}

?>