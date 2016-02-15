<?php

$page_owner = elgg_get_page_owner_entity();

if (!elgg_group_gatekeeper(false, $page_owner->guid)) {
	echo elgg_format_element('p', [
		'class' => 'elgg-no-results',
	], elgg_echo('groups:opengroup:membersonly'));
	return;
}

echo elgg_view('lists/groups/members', array(
	'pagination' => false,
	'show_rel' => false,
	'show_sort' => false,
));

echo elgg_format_element('span', [
	'class' => 'elgg-widget-more',
], elgg_view('output/url', array(
	'text' => elgg_echo('groups:members:more'),
	'href' => "groups/members/$entity->guid",
)));