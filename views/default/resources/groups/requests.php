<?php

elgg_gatekeeper();

$guid = elgg_extract('guid', $vars);
elgg_set_page_owner_guid($guid);

$group = get_entity($guid);
if (!elgg_instanceof($group, 'group') || !$group->canEdit()) {
	register_error(elgg_echo('groups:noaccess'));
	forward(REFERER);
}

$identifier = elgg_extract('identifier', $vars, 'groups');

// pushing context to make it easier to user 'menu:filter' hook
elgg_push_context("$identifier/membership");

$title = elgg_echo("$identifier:membershiprequests");

elgg_push_breadcrumb(elgg_echo($identifier), "$identifier/all");
elgg_push_breadcrumb($group->getDisplayName(0), $group->getURL());
elgg_push_breadcrumb($title);

$filter = elgg_view('filters/membership', array(
	'entity' => $group,
	'filter_context' => 'requests',
));

$content = elgg_view('lists/groups/requests', array(
	'entity' => $group,
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