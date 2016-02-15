<?php

$guid = (int) elgg_extract('guid', $vars);

elgg_entity_gatekeeper($guid, 'group');

$group = get_entity($guid);

elgg_set_page_owner_guid($guid);

elgg_group_gatekeeper();

$identifier = elgg_extract('identifier', $vars, 'groups');

// pushing context to make it easier to user 'menu:filter' hook
elgg_push_context("$identifier/membership");

$title = elgg_echo("$identifier:members");

elgg_push_breadcrumb(elgg_echo($identifier), "$identifier/all");
elgg_push_breadcrumb($group->getDisplayName(), $group->getURL());
elgg_push_breadcrumb($title);

$filter = elgg_view('filters/membership', array(
	'entity' => $group,
	'filter_context' => 'members',
		));

$content = elgg_view('lists/groups/members', array(
	'entity' => $group,
	'sort' => elgg_extract('sort', $vars),
		));

if (elgg_is_xhr()) {
	echo $content;
} else {
	$layout = elgg_view_layout('content', array(
		'content' => $content,
		'title' => $title,
		'filter' => $filter,
	));

	echo elgg_view_page($title, $layout);
}
