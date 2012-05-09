<?php

class local_category_sort_setting extends admin_setting_configselect {
    public function write_setting($data) {
        $result = parent::write_setting($data);

        // If do_nothing, then don't sort
        if (!empty($result) or $data == 'do_nothing') {
            return $result;
        }

        global $DB;

        $categories = $DB->get_records('course_categories', array('parent' => 0));

        try {
            local_category_sort::apply($categories);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }
}
