<?php

defined('MOODLE_INTERNAL') or die();

if ($hassiteconfig) {
    require_once dirname(__FILE__) . '/lib.php';
    require_once dirname(__FILE__) . '/adminlib.php';

    $sort_page = new admin_settingpage(
        'local_category_sort', get_string('pluginname', 'local_category_sort')
    );

    $sorts = local_category_sort::gather_sorts();
    $map = array('local_category_sort', 'format_sort');

    $transformed = array_map($map, array_keys($sorts), array_values($sorts));

    $flatten = function($in, $sort) { return $in + $sort; };

    $options = array_reduce($transformed, $flatten, array());

    $sort_url = new moodle_url('/local/category_sort/sort.php');
    $a->sort_url = $sort_url->out();
    $a->help_url = get_string('local_category_sort', 'outside_help');

    $sort_setting = new local_category_sort_setting(
        'local_category_sort/selected_sort',
        get_string('selected_sort', 'local_category_sort'),
        get_string('selected_sort_desc', 'local_category_sort'),
        'local_category_sort', $options
    );

    $sort_page->add(
        new admin_setting_heading('local_category_sort/heading',
            '', get_string('heading', 'local_category_sort', $a)
        )
    );

    $sort_page->add($sort_setting);

    $ADMIN->add('server', $sort_page);
}
