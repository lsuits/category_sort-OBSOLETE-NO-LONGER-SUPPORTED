<?php

abstract class local_category_sort {
    function gather_sorts() {
        $sorter = new stdClass;
        $sorter->sorts = array();

        events_trigger('category_sort_gather', $sorter);

        return $sorter;
    }

    function default_sort() {
        $name = get_string('local_category_sort', 'sort_type');

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

    function format_sort($sort) {
        return array($sort['title'] => serialize($sort));
    }

    function retrieve_sort($value) {
        $fallback = array(self, 'sort_categories');

        $unserialized = unserialize($value);

        $fails_basic = (
            empty($unserialized) or
            !file_exists($unserialized['includes'])
        );

        if ($fails_basic) {
            return $fallback;
        }

        if (!is_callable($unserialized['funciton'])) {
            global $CFG;
            include_once $CFG->dirroot . $unserialized['includes'];

            if (!is_callable($unserialized['function'])) {
                return $fallback;
            }
        }

        return $unserialized['function'];
    }

    function sort_categories($categories, $parent) {
        return function ($a, $b) {
            return strcmp($a->name, $b->name);
        };
    }

    function apply($categories, $sortorder=0, $parent=0) {
        global $DB;

        // Cache generator once successfully retrieved
        if (!self::$sort_generator) {
            $require = get_config('local_category_sort', 'selected_sort');

            self::$sort_generator = self::retrieve_sort($require);
        }

        uasort($categories, self::sort_generator($categories, $parent));

        foreach ($categories as $category) {
            $category->sortorder = $sortorder++;
            $DB->update_record('course_categories', $category);

            $children = $DB->get_records('course_categories',
                array('parent' => $category->id));

            // Apply sort to children
            $sortorder = self::apply($children, $category);
        }

        return $sortorder;
    }
}
