<?php

add_action("rest_api_init", "getUserCourses");

function getUserCourses()
{
  register_rest_route("wbs", "user-courses-table", [
    "methods" => "GET",
    "callback" => "getUserCoursesRequest",
    "permission_callback" => "__return_true",
  ]);
}

function getUserCoursesRequest($request)
{
  global $wpdb;

  //as api-key in the request
  $api_key = $_SERVER["HTTP_API_KEY"];

  if ($api_key !== API_KEY) {
    return new WP_Error("rest_forbidden", "Bad request", ["status" => 401]);
  }

  // store url params in array
  $urlParams = $_SERVER["QUERY_STRING"];

  //split each param key/value pair into an array
  $individualQueryParam = explode("&", $urlParams);

  $courseIds = [];

  // loop over each queryParam string,in the array and split the key and value respectively
  foreach ($individualQueryParam as $pair) {
    list($key, $value) = explode("=", $pair);

    // throw an error if any of the params key is not 'id'
    if ($key !== "id") {
      return new WP_Error(
        "rest_forbidden",
        "Bad request: the query parameter key '$key' must be called 'id'",
        ["status" => 400]
      );
    }

    // throw an error if any of the params key is not one of 8655, 9755, 17422
    if (!in_array($value, ["8655", "9755", "17422"], true)) {
      return new WP_Error(
        "rest_forbidden",
        "Bad request: the query parameter value '$value' must be one of '8655, 9755, 17422'",
        ["status" => 400]
      );
    }
    // trim the each value and push the it into the courseIds array
    array_push($courseIds, trim($value));
  }

  // get length of query_params_array
  $params_count = count($courseIds);

  // build dynamic number of placeholders
  $placeholders = implode(",", array_fill(0, $params_count, "%d"));

  // build the query by preparing it with the placeholders to better protect against sql injection
  $results = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT * FROM `wp_stm_lms_user_courses` WHERE `course_id` IN ($placeholders)",
      $courseIds
    )
  );

  return $results;
}
?>
