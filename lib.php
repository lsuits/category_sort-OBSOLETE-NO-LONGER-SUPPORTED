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
        $sorter->sorts['do_nothing'] =
            array('title' => get_string('do_nothing', 'local_category_sort'));
        $sorter->sorts['local_category_sort'] = self::default_sort();
        return true;
    }

    function format_sort($key, $sort) {
        return array($key => $sort['title']);
    }

    function retrieve_generator($key) {
        global $CFG;

        $generators = self::gather_sorts();

        if (!isset($generators[$key]) or !$sort = $generators[$key]) {
            throw new Exception(get_string('key_not_exists', 'local_category_sort'));
        }

        $path_fail = (
            isset($sort['includes']) and
            $fullpath = $CFG->dirroot . $sort['includes'] and
            !file_exists($fullpath)
        );

        if ($path_fail) {
            throw new Exception(
                get_string('key_bad_include', 'local_category_sort', $fullpath)
            );
        }

        if (isset($fullpath)) include_once $fullpath;

        if (!is_callable($sort['function'])) {
            throw new Exception(
                get_string('key_bad_function', 'local_category_sort',
                print_r($sort['function'], true))
            );
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

            try {
                self::$sort_generator = self::retrieve_generator($require);
            } catch (Exception $e) {
                $code->key = $require;
                $code->reason = $e->getMessage();

                throw new Exception(
                    get_string('key_failed', 'local_category_sort', $code)
                );
            }
        }

        $params = array($categories, $parent);

        uasort($categories, call_user_func_array(self::$sort_generator, $params));

        foreach ($categories as $category) {
            $sortorder += 10000;
            $category->sortorder = $sortorder;
            $DB->update_record('course_categories', $category);

            // Address course order; try to maintain course sort order
            $by_cat = array('category' => $category->id);
            $courses = $DB->get_records('course', $by_cat, 'sortorder ASC');

            $internal_sort = $category->sortorder;
            foreach ($courses as $course) {
                $course->sortorder = $internal_sort++;
                $DB->update_record('course', $course);
            }

            $children = $DB->get_records('course_categories', array(
                'parent' => $category->id
            ));

            // Apply sort to children
            $sortorder = self::apply($children, $sortorder, $category);
        }

        return $sortorder;
    }
}
