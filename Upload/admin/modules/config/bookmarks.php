<?php
/**
 * Social Bookmarking Manager
 * Copyright 2011 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->social_bookmarks, "index.php?module=config-bookmarks");

if($mybb->input['action'] == "add")
{
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(!trim($mybb->input['link']))
		{
			$errors[] = $lang->error_missing_link;
		}

		if(!trim($mybb->input['image']))
		{
			$errors[] = $lang->error_missing_path;
		}

		if(!trim($mybb->input['disporder']))
		{
			$errors[] = $lang->error_missing_order;
		}

		if(!$errors)
		{
			$new_bookmark = array(
				"name" => $db->escape_string($mybb->input['name']),
				"link" => $db->escape_string($mybb->input['link']),
				"image" => $db->escape_string($mybb->input['image']),
				"disporder" => $mybb->get_input('disporder', MyBB::INPUT_INT),
				"active" => $mybb->get_input('active', MyBB::INPUT_INT)
			);

			$bid = $db->insert_query("bookmarks", $new_bookmark);

			// Log admin action
			log_admin_action($bid, htmlspecialchars_uni($mybb->input['name']));

			flash_message($lang->success_bookmark_added, 'success');
			admin_redirect("index.php?module=config-bookmarks");
		}
	}

	$page->add_breadcrumb_item($lang->add_social_bookmark);
	$page->output_header($lang->bookmarks." - ".$lang->add_social_bookmark);

	$sub_tabs['bookmark'] = array(
		'title' => $lang->social_bookmarks,
		'link' => "index.php?module=config-bookmarks",
		'description' => $lang->social_bookmarks_desc
	);
	$sub_tabs['add_bookmark'] = array(
		'title' => $lang->add_social_bookmark,
		'link' => "index.php?module=config-bookmarks&amp;action=add",
		'description' => $lang->add_social_bookmark_desc
	);

	$page->output_nav_tabs($sub_tabs, 'add_bookmark');
	$form = new Form("index.php?module=config-bookmarks&amp;action=add", "post", "add");

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['image'] = 'images/bookmarks/';
		$mybb->input['active'] = 1;
	}

	if(empty($mybb->input['disporder']))
	{
		$query = $db->simple_select("bookmarks", "max(disporder) as dispordermax");
		$mybb->input['disporder'] = $db->fetch_field($query, "dispordermax")+1;
	}

	$form_container = new FormContainer($lang->add_social_bookmark);
	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('name', $mybb->get_input('name'), array('id' => 'name')), 'name');
	$form_container->output_row($lang->link." <em>*</em>", $lang->link_desc, $form->generate_text_box('link', $mybb->get_input('link'), array('id' => 'link')), 'link');
	$form_container->output_row($lang->bookmark_icon_path." <em>*</em>", $lang->bookmark_icon_path_desc, $form->generate_text_box('image', $mybb->get_input('image'), array('id' => 'image')), 'image');
	$form_container->output_row($lang->display_order." <em>*</em>", $lang->bookmark_display_order_desc, $form->generate_numeric_field('disporder', $mybb->get_input('disporder'), array('id' => 'disporder', 'min' => 0)), 'disporder');
	$form_container->output_row($lang->active." <em>*</em>", "", $form->generate_yes_no_radio('active', $mybb->get_input('active')));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_bookmark);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit")
{
	$query = $db->simple_select("bookmarks", "*", "bid='".$mybb->get_input('bid', MyBB::INPUT_INT)."'");
	$bookmark = $db->fetch_array($query);

	if(!$bookmark['bid'])
	{
		flash_message($lang->error_invalid_bookmark, 'error');
		admin_redirect("index.php?module=config-bookmarks");
	}

	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(!trim($mybb->input['link']))
		{
			$errors[] = $lang->error_missing_link;
		}

		if(!trim($mybb->input['image']))
		{
			$errors[] = $lang->error_missing_path;
		}

		if(!trim($mybb->input['disporder']))
		{
			$errors[] = $lang->error_missing_order;
		}

		if(!$errors)
		{
			$update_bookmark = array(
				"name" => $db->escape_string($mybb->input['name']),
				"link" => $db->escape_string($mybb->input['link']),
				"image" => $db->escape_string($mybb->input['image']),
				"disporder" => $mybb->get_input('disporder', MyBB::INPUT_INT),
				"active" => $mybb->get_input('active', MyBB::INPUT_INT)
			);

			$db->update_query("bookmarks", $update_bookmark, "bid='{$bookmark['bid']}'");

			// Log admin action
			log_admin_action($bookmark['bid'], htmlspecialchars_uni($mybb->input['name']));

			flash_message($lang->success_bookmark_updated, 'success');
			admin_redirect("index.php?module=config-bookmarks");
		}
	}

	$page->add_breadcrumb_item($lang->edit_social_bookmark);
	$page->output_header($lang->bookmarks." - ".$lang->edit_social_bookmark);

	$sub_tabs['edit_bookmark'] = array(
		'title' => $lang->edit_social_bookmark,
		'link' => "index.php?module=config-bookmarks&amp;action=edit",
		'description' => $lang->edit_social_bookmark_desc
	);

	$page->output_nav_tabs($sub_tabs, 'edit_bookmark');

	$form = new Form("index.php?module=config-bookmarks&amp;action=edit", "post", "edit");
	echo $form->generate_hidden_field("bid", $bookmark['bid']);

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input = array_merge($mybb->input, $bookmark);
	}

	$form_container = new FormContainer($lang->edit_social_bookmark);
	$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('name', $mybb->input['name'], array('id' => 'name')), 'name');
	$form_container->output_row($lang->link." <em>*</em>", $lang->link_desc, $form->generate_text_box('link', $mybb->input['link'], array('id' => 'link')), 'link');
	$form_container->output_row($lang->bookmark_icon_path." <em>*</em>", $lang->bookmark_icon_path_desc, $form->generate_text_box('image', $mybb->input['image'], array('id' => 'image')), 'image');
	$form_container->output_row($lang->display_order." <em>*</em>", $lang->bookmark_display_order_desc, $form->generate_numeric_field('disporder', $mybb->input['disporder'], array('id' => 'disporder', 'min' => 0)), 'disporder');
	$form_container->output_row($lang->active." <em>*</em>", "", $form->generate_yes_no_radio('active', $mybb->input['active']));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_bookmark);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete")
{
	$query = $db->simple_select("bookmarks", "*", "bid='".$mybb->get_input('bid', MyBB::INPUT_INT)."'");
	$bookmark = $db->fetch_array($query);

	if(!$bookmark['bid'])
	{
		flash_message($lang->error_invalid_bookmark, 'error');
		admin_redirect("index.php?module=config-bookmarks");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-bookmarks");
	}

	if($mybb->request_method == "post")
	{
		$db->delete_query("bookmarks", "bid='{$bookmark['bid']}'");

		// Log admin action
		log_admin_action($bookmark['bid'], htmlspecialchars_uni($bookmark['name']));

		flash_message($lang->success_bookmark_deleted, 'success');
		admin_redirect("index.php?module=config-bookmarks");
	}
	else
	{
		$page->output_confirm_action("index.php?module=config-bookmarks&amp;action=delete&amp;bid={$bookmark['bid']}", $lang->confirm_bookmark_deletion);
	}
}

if($mybb->input['action'] == "disable")
{	
	$query = $db->simple_select("bookmarks", "*", "bid='".$mybb->get_input('bid', MyBB::INPUT_INT)."'");
	$bookmark = $db->fetch_array($query);

	if(!$bookmark['bid'])
	{
		flash_message($lang->error_invalid_bookmark, 'error');
		admin_redirect("index.php?module=config-bookmarks");
	}

	$active = array(
		"active" => 0
	);
	$db->update_query("bookmarks", $active, "bid='{$bookmark['bid']}'");

	// Log admin action
	log_admin_action($bookmark['bid'], htmlspecialchars_uni($bookmark['name']));

	flash_message($lang->success_bookmark_disabled, 'success');
	admin_redirect("index.php?module=config-bookmarks");
}

if($mybb->input['action'] == "enable")
{
	$query = $db->simple_select("bookmarks", "*", "bid='".$mybb->get_input('bid', MyBB::INPUT_INT)."'");
	$bookmark = $db->fetch_array($query);

	if(!$bookmark['bid'])
	{
		flash_message($lang->error_invalid_bookmark, 'error');
		admin_redirect("index.php?module=config-bookmarks");
	}

	$active = array(
		"active" => 1
	);
	$db->update_query("bookmarks", $active, "bid='{$bookmark['bid']}'");

	// Log admin action
	log_admin_action($bookmark['bid'], htmlspecialchars_uni($bookmark['name']));

	flash_message($lang->success_bookmark_enabled, 'success');
	admin_redirect("index.php?module=config-bookmarks");
}

if(!$mybb->input['action'])
{
	$page->output_header($lang->social_bookmarks);

	$sub_tabs['bookmark'] = array(
		'title' => $lang->social_bookmarks,
		'link' => "index.php?module=config-bookmarks",
		'description' => $lang->social_bookmarks_desc
	);
	$sub_tabs['add_bookmark'] = array(
		'title' => $lang->add_social_bookmark,
		'link' => "index.php?module=config-bookmarks&amp;action=add",
		'description' => $lang->add_social_bookmark_desc
	);

	$page->output_nav_tabs($sub_tabs, 'bookmark');

	$table = new Table;
	$table->construct_header($lang->name);
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));

	$query = $db->simple_select("bookmarks", "*", "", array("order_by" => "disporder", "order_dir" => "asc"));
	while($bookmark = $db->fetch_array($query))
	{
		$bookmark['name'] = htmlspecialchars_uni($bookmark['name']);
		if($bookmark['active'] == 1)
		{
			$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.png\" alt=\"({$lang->alt_enabled})\" title=\"{$lang->alt_enabled}\"  style=\"vertical-align: middle;\" /> ";
		}
		else
		{
			$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.png\" alt=\"({$lang->alt_disabled})\" title=\"{$lang->alt_disabled}\"  style=\"vertical-align: middle;\" /> ";
		}
		$table->construct_cell("<div>{$icon}<strong><a href=\"index.php?module=config-bookmarks&amp;action=edit&amp;bid={$bookmark['bid']}\">{$bookmark['name']}</a></strong></div>");

		$popup = new PopupMenu("bookmark_{$bookmark['bid']}", $lang->options);
		$popup->add_item($lang->edit_bookmark, "index.php?module=config-bookmarks&amp;action=edit&amp;bid={$bookmark['bid']}");
		if($bookmark['active'] == 1)
		{
			$popup->add_item($lang->disable_bookmark, "index.php?module=config-bookmarks&amp;action=disable&amp;bid={$bookmark['bid']}&amp;my_post_key={$mybb->post_code}");
		}
		else
		{
			$popup->add_item($lang->enable_bookmark, "index.php?module=config-bookmarks&amp;action=enable&amp;bid={$bookmark['bid']}&amp;my_post_key={$mybb->post_code}");
		}
		$popup->add_item($lang->delete_bookmark, "index.php?module=config-bookmarks&amp;action=delete&amp;bid={$bookmark['bid']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->confirm_bookmark_deletion}')");
		$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_bookmarks, array('colspan' => 2));
		$table->construct_row();
	}

	$table->output($lang->social_bookmarks);

	$page->output_footer();
}
