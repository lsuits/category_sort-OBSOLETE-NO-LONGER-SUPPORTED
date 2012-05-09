<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once dirname(__FILE__) . '/lib.php';

    $sort_generators = local_category_sort::gather_sorts();

    $flatten = function($in, $sort) {
        return $in + local_category_sort::format_sort($sort);
    };

    $options = array_reduce($sort_generators, $flatten, array());

    $a->sort_url = new moodle_url('/local/category_sort/sort.php')->out();
    $a->help_url = get_string('local_category_sort', 'outside_help');

    $settings->add(
        new admin_setting_heading('local_category_sort/heading',
            '', get_string('local_category_sort', 'heading', $a)
        )
    );

    $settings->add(
        new admin_setting_configselect('local_category_sort/selected_sort',
            get_string('local_category_sort', 'selected_sort'),
            get_string('local_category_sort', 'selected_sort_desc'),
            serialize(local_category_sort::default_sort()), $options
        )
    );
}
