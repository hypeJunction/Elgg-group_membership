<?php

$identifier = elgg_extract('identifier', $vars, 'groups');

elgg_gatekeeper();

$guid = elgg_extract('guid', $vars);
elgg_set_page_owner_guid($guid);

$group = get_entity($guid);
if (!elgg_instanceof($group, 'group')) {
	register_error(elgg_echo("$identifier:noaccess"));
	forward(REFERER);
}

if (!$group->canEdit() && (!$group->isMember() || $group->invites_enable !== 'yes')) {
	register_error(elgg_echo('groups:noaccess'));
	forward(REFERER);
}

// pushing context to make it easier to user 'menu:filter' hook
elgg_push_context("$identifier/membership");

$title = elgg_echo("$identifier:invite:title");

elgg_push_breadcrumb(elgg_echo($identifier), "$identifier/all");
elgg_push_breadcrumb($group->getDisplayName(), $group->getURL());
elgg_push_breadcrumb($title);

$filter = elgg_view('filters/membership', array(
	'entity' => $group,
	'filter_context' => 'invite',
));

$content = elgg_view_form('groups/invite', array(
	'id' => 'invite_to_group',
), array(
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
