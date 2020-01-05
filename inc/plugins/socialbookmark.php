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
if(THIS_SCRIPT == 'showthread.php')
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
		"version"			=> "1.2",
		"codename"			=> "socialbookmark",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is installed.
function socialbookmark_install()
{
	global $db;
	socialbookmark_uninstall();
	$collation = $db->build_create_table_collation();

	switch($db->type)
	{
		case "pgsql":
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."bookmarks (
				bid serial,
				name varchar(120) NOT NULL default '',
				link varchar(255) NOT NULL default '',
				image varchar(220) NOT NULL default '',
				disporder smallint NOT NULL default '0',
				active smallint NOT NULL default '1',
				PRIMARY KEY (bid)
			);");
			break;
		case "sqlite":
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."bookmarks (
				bid INTEGER PRIMARY KEY,
				name varchar(120) NOT NULL default '',
				link varchar(255) NOT NULL default '',
				image varchar(220) NOT NULL default '',
				disporder smallint(5) NOT NULL default '0',
				active tinyint(1) NOT NULL default '1'
			);");
			break;
		default:
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."bookmarks (
				bid int unsigned NOT NULL auto_increment,
				name varchar(120) NOT NULL default '',
				link varchar(255) NOT NULL default '',
				image varchar(220) NOT NULL default '',
				disporder smallint(5) unsigned NOT NULL default '0',
				active tinyint(1) NOT NULL default '1',
				PRIMARY KEY (bid)
			) ENGINE=MyISAM{$collation};");
			break;
	}

	$db->write_query("INSERT INTO ".TABLE_PREFIX."bookmarks (bid, name, link, image, disporder) VALUES
(1, 'Facebook', 'https://www.facebook.com/sharer.php?u={url}&title={title}', 'images/bookmarks/facebook.png', 1),
(2, 'Twitter', 'https://twitter.com/intent/tweet?text={title} {url}', 'images/bookmarks/twitter.png', 2),
(3, 'Reddit', 'https://www.reddit.com/submit?url={url}&title={title}', 'images/bookmarks/reddit.png', 3),
(4, 'Digg', 'http://digg.com/submit?phrase=2&url={url}&title={title}', 'images/bookmarks/digg.png', 4),
(5, 'del.icio.us', 'https://del.icio.us/post?url={url}&title={title}', 'images/bookmarks/delicious.png', 5),
(6, 'Tumblr', 'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}', 'images/bookmarks/tumblr.png', 6),
(7, 'Pinterest', 'https://pinterest.com/pin/create/button/?url={url}', 'images/bookmarks/pinterest.png', 7),
(8, 'Blogger', 'https://www.blogger.com/blog-this.g?u={url}&n={title}', 'images/bookmarks/blogger.png', 8),
(9, 'Fark', 'https://www.fark.com/submit?new_url={url}', 'images/bookmarks/fark.png', 9),
(10, 'LinkedIn', 'https://www.linkedin.com/shareArticle?url={url}&title={title}', 'images/bookmarks/linkedin.png', 10),
(11, 'Mix', 'https://mix.com/add?url={url}', 'images/bookmarks/mix.png', 11),
(12, 'Google', 'https://www.google.com/bookmarks/mark?op=edit&bkmk={url}&title={title}', 'images/bookmarks/google.png', 12)");
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
		'optionscode' => 'numeric
min=1',
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
		'template'	=> $db->escape_string('<li style="width:{$value}%; float:left;"><a href="{$bookmark[\'link\']}" title="{$title}"><img src="{$bookmark[\'image\']}" alt="{$bookmark[\'name\']}">&nbsp;{$bookmark[\'name\']}</a></li>'),
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
			$bookmark['name'] = htmlspecialchars_uni($bookmark['name']);
			$title = $lang->sprintf($lang->submit_thread_to, $bookmark['name']);
			$value = 100/$mybb->settings['bookmarking_number'];
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
	global $lang;
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
