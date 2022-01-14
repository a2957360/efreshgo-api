<?php
function reArrayFiles(&$file_post){
    $isMulti    = is_array($file_post['name']);
    $file_count    = $isMulti?count($file_post['name']):1;
    $file_keys    = array_keys($file_post);

    $file_ary    = [];
    for($i=0; $i<$file_count; $i++)
        foreach($file_keys as $key)
            if($isMulti)
                $file_ary[$i][$key] = $file_post[$key][$i];
            else
                $file_ary[$i][$key]    = $file_post[$key];

    return $file_ary;
}
?>