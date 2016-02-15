<?php

$identifier = elgg_extract('identifier', $vars, 'groups');

$entity = elgg_extract('entity', $vars);
$guid = (int) $entity->guid;

$base_url = elgg_normalize_url("$identifier/invited/$entity->guid") . '?' . parse_url(current_page_url(), PHP_URL_QUERY);

$list_class = (array) elgg_extract('list_class', $vars, array());
$list_class[] = 'elgg-list-invited';

$item_class = (array) elgg_extract('item_class', $vars, array());
$item_class[] = 'elgg-invitation';

$options = (array) elgg_extract('options', $vars, array());

$list_options = array(
	'full_view' => true,
	'limit' => elgg_extract('limit', $vars, elgg_get_config('default_limit')) ? : 10,
	'list_class' => implode(' ', $list_class),
	'item_class' => implode(' ', $item_class),
	'no_results' => elgg_echo("$identifier:invited:no_results"),
	'pagination' => elgg_is_active_plugin('hypeLists') || !elgg_in_context('widgets'),
	'pagination_type' => 'default',
	'base_url' => $base_url,
	'list_id' => "invites-$guid",
	'item_view' => 'user/format/member',
	'auto_refresh' => false,
	'group' => $entity,
);

$getter_options = array(
	'types' => array('user'),
);

$options = array_merge_recursive($list_options, $options, $getter_options);

if (elgg_view_exists('lists/users')) {
	echo elgg_view('lists/users', array(
		'options' => $options,
		'callback' => 'elgg_list_entities',
		'rel' => 'invited',
		'group' => $entity,
	));
} else {
	$options['relationship'] = 'invited';
	$options['relationship_guid'] = $guid;
	$options['inverse_relationship'] = false;

	$sort = elgg_extract('sort', $vars);
	switch ($sort) {
		case 'newest' :
			$options['order_by'] = 'r.time_created DESC';
			break;

		default :
			$dbprefix = elgg_get_config('dbprefix');
			$options['joins'][] = "JOIN {$dbprefix}users_entity ue ON ue.guid = e.guid";
			$options['order_by'] = 'ue.name ASC';
			break;
	}

	echo elgg_list_entities_from_relationship($options);
}