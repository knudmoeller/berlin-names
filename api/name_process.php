<?php

include("def.inc");

function run_query($query) {
  $cache_path = "../cache/" . sha1($query);
  if (file_exists($cache_path)) {
    $result = unserialize(file_get_contents($cache_path));
    return $result;
  } else {
    $result = sparql_query($query);
    file_put_contents($cache_path, serialize($result));
    return $result;
  }
}


function sum_counts($year) {
  $total = 0;
  foreach($year as $district_count) {
    $total = $total + $district_count['count'];
  }
  return $total;
}

function count_names() {
  $query = file_get_contents("../queries/count_names.rq");
  $result = run_query($query);
  $row = sparql_fetch_array( $result );
  return $row["name_count"];
}

function get_url() {
  $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $url = parse_url($url);
  $host = $url["host"];
  $path = $url["path"];
  
  return "http://$host$path";
}

function limit_page($max_pages) {
  if (array_key_exists('page', $_REQUEST)) {
    $page = $_REQUEST['page'];
  } else {
    $page = 1;
  }
  if ($page > $max_pages ) {
    $url = get_url();
    $new_url = "$url?page=$max_pages";
    header('Location: '.$new_url);
    die();
  }
  return $page;
}

function name_list($page, $max_pages) {
  $query = file_get_contents("../queries/name_pager.rq");
  $offset = ($page - 1) * LIST_LIMIT + 1;
  $query = str_replace("{{offset}}", $offset, $query);
  $query = str_replace("{{limit}}", LIST_LIMIT, $query);
  $result = run_query($query);
  $name_list = Array();
  while( $row = sparql_fetch_array( $result ) )
  {
    $name_list[] = $row["name_string"];
  }
    
  return $name_list;
}

function name_data($name) {
  
  $slices = Array();
  $result_array = Array(
    "@id" => "http://$_SERVER[HTTP_HOST]". APP_PATH . "name/$name/",
  );
  
  $female = name_sex_data($name, "F");
  if (count($female["slices"]) > 0) {
    $slices[] = $female;
  }
  $male = name_sex_data($name, "M");
  if (count($male["slices"]) > 0) {
    $slices[] = $male;
  }

  $result_array["slices"] = $slices;

  return $result_array;
}

function name_sex_data($name, $sex) {
  $query = file_get_contents("../queries/observations_for_name_sex.rq");
  $query = str_replace("{{name}}", $name, $query);
  $query = str_replace("{{sex}}", $sex, $query);
  $result = run_query($query);
  
  $slices = Array();
  $result_array = Array(
    "@id" => "http://$_SERVER[HTTP_HOST]". APP_PATH . "name/$name/$sex/",
    "name" => $name,
    "sex" => $sex,
  );
  $years = Array();
  while( $row = sparql_fetch_array( $result ) )
  {
    $year = $row["year"];
    $district = $row["district_name"];
    $count = $row["count"];

    if (!array_key_exists($year, $years)) {
      $year_json = Array(
        "year" => $year,
        "counts" => Array()
      );
      $years[$year] = $year_json;
    } else {
      $year_json = $years[$year];
    }

    $observation_json = Array(
      "district" => $district,
      "count" => $count
    );

    $years[$year]["counts"][] = $observation_json;
  }

  foreach($years as $year) {
    $total = sum_counts($year['counts']);
    $year['total'] = $total;
    $slices[] = $year;
  }

  $result_array["slices"] = $slices;

  return $result_array;
}

function select_name($name) {
  echo "<script type='text/javascript'> " .
    "var elem = document.getElementById('name_input');" .
    "elem.value = '$name';" .
    "</script>";
}

function select_sex($sex) {
  echo "<script type='text/javascript'> " .
    "var sex = '$sex';" .
    "if (sex === 'F') { sex = 'W'; } " .
    "$('#sex_select option')" .
    "  .each(function() { this.selected = (this.value === sex); });" .
    "</script>";
}

// array with path components; array_filter removes empty elements
$components = array_filter(explode("/", $_REQUEST['request']));
$path_length = count($components);


switch($path_length) {
  case 0:
    $max_pages = ceil(count_names() / LIST_LIMIT);
    $page = limit_page($max_pages);
    include "header.php";
    $data = name_list($page, $max_pages);
    include("name_list.php");
    break;
  case 1:
    $name = $components[0];
    include "header.php";
    select_name($name);
    $data = name_data($name);
    include "name.php";
    break;
  case 2:
    $name = $components[0];
    $sex = $components[1];
    include "header.php";
    select_name($name);
    select_sex($sex);
    $data = name_sex_data($name, $sex);
    include("name_sex.php");
    break;
  default:
    $output = "path_length is longer than 2<br/>";
    include "header.php";
    echo $output;
}


include "footer.php";


?>