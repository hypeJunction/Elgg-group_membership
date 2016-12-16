<?php

/**
 * Group Membership Lists
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2015, Ismayil Khayredinov
 */
require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', 'group_membership_init');

/**
 * Initialize the plugin
 * @return void
 */
function group_membership_init() {

	elgg_extend_view('elgg.css', 'user/format/member.css');
	elgg_extend_view('admin.css', 'user/format/member.css');

	elgg_register_plugin_hook_handler('route', 'groups', 'group_membership_router');
	elgg_register_plugin_hook_handler('sort_fields', 'user', 'group_membership_sort_fields');
	elgg_register_plugin_hook_handler('sort_options', 'user', 'group_membership_sort_options');
	elgg_register_plugin_hook_handler('rel_options', 'user', 'group_membership_rel_options');

	elgg_unregister_plugin_hook_handler('register', 'menu:user_hover', 'groups_user_entity_menu_setup');
	elgg_register_plugin_hook_handler('register', 'menu:membership', 'group_membership_menu_setup');

	elgg_register_action('groups/make_admin', __DIR__ . '/actions/groups/make_admin.php');
	elgg_register_action('groups/remove_admin', __DIR__ . '/actions/groups/remove_admin.php');
	elgg_register_plugin_hook_handler('permissions_check', 'group', 'group_membership_group_admin_permissions');

	elgg_register_widget_type('group_members', elgg_echo('groups:widget:group_members'), elgg_echo('groups:widget:group_members:desc'), array('groups'), false);
}

/**
 * Route groups membership pages
 *
 * @param string $hook   "route"
 * @param string $type   "groups"
 * @param array  $return Identifier and segments
 * @param array  $params Hook params
 * @return array
 */
function group_membership_router($hook, $type, $return, $params) {

	if (!is_array($return)) {
		return;
	}
	
	// Initial page identifier might be different from /groups
	// i.e. subtype specific handler e.g. /schools
	$initial_identifier = elgg_extract('identifier', $params);
	$identifier = elgg_extract('identifier', $return);
	$segments = elgg_extract('segments', $return);

	if ($identifier !== 'groups') {
		return;
	}

	$page = array_shift($segments);
	if (!$page) {
		$page = 'all';
	}

	// we want to pass the original identifier to the resource view
	// doing this via route hook in order to keep the page handler intact
	$resource_params = array(
		'identifier' => $initial_identifier ? : 'groups',
		'segments' => $segments,
	);

	switch ($page) {
		case 'members':
			$guid = array_shift($segments);
			$sort = array_shift($segments);
			$resource_params['guid'] = $guid;
			$resource_params['sort'] = $sort;
			if (!elgg_is_active_plugin('user_sort') && elgg_view_exists("resources/groups/members/$sort")) {
				echo elgg_view_resource("groups/members/$sort", $resource_params);
			} else {
				echo elgg_view_resource('groups/members', $resource_params);
			}
			return false;
		case 'requests' :
		case 'invited' :
		case 'invite' :
			$guid = array_shift($segments);
			$resource_params['guid'] = $guid;
			echo elgg_view_resource("groups/$page", $resource_params);
			return false;
	}
}

/**
 * Add group specific sort field options
 * 
 * @param string $hook   "sort_fields"
 * @param string $type   "user"
 * @param array  $return Fields
 * @param array  $params Hook params
 * @return array
 */
function group_membership_sort_fields($hook, $type, $return, $params) {

	$page_owner = elgg_get_page_owner_entity();
	if (!$page_owner instanceof ElggGroup) {
		return;
	}

	return array(
		'alpha::asc',
		'alpha::desc',
		'group_rel::desc',
		'group_rel::asc',
	);
}

/**
 * Sort options
 *
 * @param string $hook    "rel_options"
 * @param string $type    "user"
 * @param array  $options Options
 * @param array  $params  Hook params
 * @return array
 */
function group_membership_sort_options($hook, $type, $options, $params) {

	$field = elgg_extract('field', $params);
	$direction = elgg_extract('direction', $params, 'ASC');

	$order_by = explode(',', elgg_extract('order_by', $options, ''));
	array_walk($order_by, 'trim');

	switch ($field) {
		case 'group_rel' :
			if (isset($options['joins']['group_rel'])) {
				array_unshift($order_by, "group_rel.time_created {$direction}");
			}
			break;
	}

	$options['order_by'] = implode(', ', array_unique(array_filter($order_by)));
	return $options;
}

/**
 * Relationship options
 *
 * @param string $hook    "rel_options"
 * @param string $type    "user"
 * @param array  $options Options
 * @param array  $params  Hook params
 * @return array
 */
function group_membership_rel_options($hook, $type, $options, $params) {

	$dbprefix = elgg_get_config('dbprefix');

	$page_owner = elgg_extract('page_owner', $params);
	if (!isset($page_owner)) {
		$page_owner = elgg_get_page_owner_entity();
	}

	$guid = ($page_owner) ? (int) $page_owner->guid : 0;

	$rel = elgg_extract('rel', $params);

	switch ($rel) {

		case 'member' :
			$options['joins']['group_rel'] = "JOIN {$dbprefix}entity_relationships AS group_rel
				ON group_rel.guid_two = $guid AND group_rel.relationship = 'member' AND group_rel.guid_one = e.guid";
			break;

		case 'invited' :
			$options['joins']['group_rel'] = "JOIN {$dbprefix}entity_relationships AS group_rel
				ON group_rel.guid_one = $guid AND group_rel.relationship = 'invited' AND group_rel.guid_two = e.guid";
			break;

		case 'membership_request' :
			$options['joins']['group_rel'] = "JOIN {$dbprefix}entity_relationships AS group_rel
				ON group_rel.guid_two = $guid AND group_rel.relationship = 'membership_request' AND group_rel.guid_one = e.guid";
			break;
	}

	return $options;
}

/**
 * Setup group membership menu
 * 
 * @param string         $hook   "register"
 * @param sring          $type   "menu:membership"
 * @param ElggMenuItem[] $return Menu
 * @param array          $params Hook params
 * @return ElggMenuItem[]
 */
function group_membership_menu_setup($hook, $type, $return, $params) {

	$user = elgg_extract('entity', $params);
	$group = elgg_extract('group', $params, elgg_get_page_owner_entity());

	if (!$user instanceof ElggUser || !$group instanceof ElggGroup) {
		return;
	}

	$identifier = is_callable('group_subtypes_get_identifier') ? group_subtypes_get_identifier($group) : 'groups';

	if ($group->isMember($user)) {
		if ($group->canEdit()) {
			if (($admin = check_entity_relationship($user->guid, 'group_admin', $group->guid)) || $group->owner_guid == $user->guid) {
				// subtitle element will be added identifying group administrator
				if ($admin && $user->guid != elgg_get_logged_in_user_guid()) {
					$return[] = ElggMenuItem::factory(array(
								'name' => "$identifier:removeadmin",
								'href' => "action/groups/remove_admin?user_guid={$user->guid}&group_guid={$group->guid}",
								'text' => elgg_echo("$identifier:removeadmin"),
								'confirm' => true,
					));
				}
			} else {
				$return[] = ElggMenuItem::factory(array(
							'name' => "$identifier:removeuser",
							'href' => "action/groups/remove?user_guid={$user->guid}&group_guid={$group->guid}",
							'text' => elgg_echo("$identifier:removeuser"),
							'confirm' => true,
				));
				$return[] = ElggMenuItem::factory(array(
							'name' => "$identifier:makeadmin",
							'href' => "action/groups/make_admin?user_guid={$user->guid}&group_guid={$group->guid}",
							'text' => elgg_echo("$identifier:makeadmin"),
							'confirm' => true,
				));
			}
		}
	} else if (check_entity_relationship($user->guid, 'membership_request', $group->guid)) {

		$return[] = ElggMenuItem::factory(array(
			'name' => "$identifier:request:accept",
			'href' => $url = "action/groups/addtogroup?user_guid={$user->guid}&group_guid={$group->guid}",
			'text' => elgg_echo("$identifier:request:accept"),
		));

		$return[] = ElggMenuItem::factory(array(
				'name' => "$identifier:request:decline",
				'href' => "action/groups/killrequest?user_guid={$user->guid}&group_guid={$group->guid}",
				'confirm' => elgg_echo('groups:joinrequest:remove:check'),
				'text' => elgg_echo("$identifier:request:decline"),
		));
	} else if (check_entity_relationship($group->guid, 'invited', $user->guid)) {

		$return[] = ElggMenuItem::factory(array(
			'name' => "$identifier:invitation:revoke",
			'text' => elgg_echo("$identifier:invitation:revoke"),
			'href' => "action/groups/killinvitation?user_guid={$user->guid}&group_guid={$group->guid}",
			'confirm' => true,
		));
	}

	return $return;
}

/**
 * Allow group administrators to edit groups
 * 
 * @param string $hook   "permissions_check"
 * @param string $type   "group"
 * @param bool   $return Permission
 * @param array  $params Hook params
 * @return bool
 */
function group_membership_group_admin_permissions($hook, $type, $return, $params) {

	$entity = elgg_extract('entity', $params);
	$user = elgg_extract('user', $params);

	if (!$entity instanceof ElggGroup || !$user instanceof ElggUser || !$entity->isMember($user)) {
		return;
	}

	if (check_entity_relationship($user->guid, 'group_admin', $entity->guid)) {
		return true;
	}
}
