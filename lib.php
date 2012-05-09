<?php

abstract class local_category_sort {
    private static $sort_generator;

    function gather_sorts() {
        $sorter = new stdClass;
        $sorter->sorts = array();

        events_trigger('category_sort_gather', $sorter);

        return $sorter->sorts;
    }

    function default_sort() {
        $name = get_string('sort_type', 'local_category_sort');

        return array(
            'title' => $name,
            'includes' => '/local/category_sort/lib.php',
            'function' => array('local_category_sort', 'sort_categories')
        );
    }

    function sort_gather($sorter) {
        $sorter->sorts['local_category_sort'] = self::default_sort();
        return true;
    }

    function format_sort($key, $sort) {
        return array($key => $sort['title']);
    }

    function retrieve_generator($key) {
        $generators = self::gather_sorts();

        $fallback = array('local_category_sort', 'sort_categories');

        $fails_basic = (
            !isset($generators[$key]) or
            !$sort = $generators[$key] or
            !file_exists($sort['includes'])
        );

        if ($fails_basic) {
            return $fallback;
        }

        if (!is_callable($sort['funciton'])) {
            global $CFG;
            include_once $CFG->dirroot . $sort['includes'];

            if (!is_callable($sort['function'])) {
                return $fallback;
            }
        }

        return $sort['function'];
    }

    function sort_categories($categories, $parent) {
        return function ($a, $b) {
            return strcmp($a->name, $b->name);
        };
    }

    function apply($categories, $sortorder=0, $parent=0) {
        global $DB;

        // Cache generator once successfully retrieved
        if (empty(self::$sort_generator)) {
            $require = get_config('local_category_sort', 'selected_sort');

            self::$sort_generator = self::retrieve_generator($require);
        }

        $params = array($categories, $parent);

        uasort($categories, call_user_func_array(self::$sort_generator, $params));

        foreach ($categories as $category) {
            $category->sortorder = $sortorder++;
            $DB->update_record('course_categories', $category);

            $children = $DB->get_records('course_categories',
                array('parent' => $category->id));

            // Apply sort to children
            $sortorder = self::apply($children, $sortorder, $category);
        }

        return $sortorder;
    }
}
