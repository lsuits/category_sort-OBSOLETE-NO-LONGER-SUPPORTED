<?php

class local_category_sort_setting extends admin_setting_configselect {
    public function write_setting($data) {
        $result = parent::write_setting($data);

        if (!empty($result)) {
            return $result;
        }

        global $DB;

        $categories = $DB->get_records('course_categories', array('parent' => 0));

        local_category_sort::apply($categories);

        return $result;
    }
}
