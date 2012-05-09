<?php

$handlers = array(
    'category_sort_gather' => array(
        'handlerfile' => '/local/category_sort/lib.php',
        'handlerfunction' => array('local_category_sort', 'sort_gather'),
        'schedule' => 'instant'
    )
);
