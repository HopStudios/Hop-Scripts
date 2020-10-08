<?php

/*
    Hop Change MSM Primary Site
	Copyright 2020 Hop Studios
    Please find instructions here:
    https://github.com/HopStudios/Hop-Scripts/
*/

// SET THESE VARIABLES
$host = 'localhost';
$user = 'username';
$pass = 'password';
$db = 'db_name';

$new_primary_site = '7'; // current site_id of the site you want as the primary site (site_id = 1)

// FUTURE: Handle situation where you want to keep more than 1 site, but you want to get rid of site 1


// If the new site relies on  channels from the old primary site,
// list them here and they will be moved to the new site
$channels_to_keep = '12, 49, 24, 16, 17, 18, 21, 15, 11';

// check connection or fail gracefully
$conn = new mysqli($host, $user, $pass,$db) or die("Connect failed: %s\n". $conn -> error);

// move EE channel data to new_primary_site
// only needed if you're saving some channels
if (!empty($channels_to_keep)) {
    $conn->query("UPDATE exp_channels SET site_id = $new_primary_site WHERE channel_id IN ($channels_to_keep)");
    $conn->query("UPDATE exp_channel_titles SET site_id = $new_primary_site WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
    $conn->query("UPDATE exp_channel_data SET site_id = $new_primary_site WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
    // This only works for EE3 and earlier -- moving fields is more complex now
	$conn->query("UPDATE exp_channel_fields SET site_id = $new_primary_site WHERE site_id = 1");
	$conn->query("UPDATE exp_field_groups SET site_id = $new_primary_site WHERE site_id = 1");

    // TODO: Get specific fields for the channels you're keeping, and also handle modern field tables
    // TODO: Get specific categories for the channels you're keeping
}

// Save all the file directories from site 1
$conn->query("UPDATE exp_files SET site_id = $new_primary_site WHERE site_id = 1");
$conn->query("UPDATE exp_file_dimensions SET site_id = $new_primary_site WHERE site_id = 1");
$conn->query("UPDATE exp_upload_prefs SET site_id = $new_primary_site WHERE site_id = 1");

// save all the categories from site 1
$conn->query("UPDATE exp_categories SET site_id = $new_primary_site WHERE site_id = 1");
$conn->query("UPDATE exp_category_groups SET site_id = $new_primary_site WHERE site_id = 1");
$conn->query("UPDATE exp_category_fields SET site_id = $new_primary_site WHERE site_id = 1");
$conn->query("UPDATE exp_category_field_data SET site_id = $new_primary_site WHERE site_id = 1");

// IMPORTANT: If you have matrix, uncomment below
// $conn->query("UPDATE exp_matrix_cols SET site_id = $new_primary_site WHERE site_id = 1");
// $conn->query("UPDATE exp_matrix_data SET site_id = $new_primary_site WHERE site_id = 1");

// IMPORTANT: You might want to use the below lines to delete duplicate fields/field_groups. 
// This will simply delete the fields with a lower ID, so make sure that's what you actually want. 
// Otherwise leave it commented out and delete duplicates manually, or write a smarter process here
// $conn->query("DELETE t1.* FROM exp_channel_fields t1 INNER JOIN exp_channel_fields t2 WHERE t1.field_id < t2.field_id AND t1.field_name = t2.field_name");
// $conn->query("DELETE t1.* FROM exp_field_groups t1 INNER JOIN exp_field_groups t2 WHERE t1.group_id < t2.group_id AND t1.group_name = t2.group_name");

// IMPORTANT: If you have Structure installed, uncomment below
// $conn->query("UPDATE exp_structure SET site_id = $new_primary_site WHERE site_id = 0");
// $conn->query("UPDATE exp_structure SET site_id = $new_primary_site WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
// $conn->query("UPDATE exp_structure_channels SET site_id = $new_primary_site WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
// $conn->query("UPDATE exp_structure_listings SET site_id = $new_primary_site WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
// $conn->query("UPDATE exp_structure_settings SET site_id = $new_primary_site WHERE site_id = 1");


// Find the list of tables that have the site_id column
if ($result = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = 'site_id' AND TABLE_SCHEMA='$db'")) {
    printf("Select returned %d rows.\n", $result->num_rows);

    // push the results into an iterable
    while($row = $result->fetch_array()) {
        $rows[] = $row;
    }

    foreach($rows as $row) {
        printf($row['TABLE_NAME']);
        $table_name = $row['TABLE_NAME'];

        // Delete all records from the old default site
        if ($result = $conn->query("DELETE FROM $table_name WHERE site_id = 1")) {
            printf(" - Deleted where site_id is 1");
        }

        // Update the site_id of new_primary_site to be site_id = 1
        if ($result = $conn->query("UPDATE $table_name SET site_id = 1 WHERE site_id = $new_primary_site")) {
            printf(" - Updated $new_primary_site to site_id 1");
        }

        // Delete all entries that are not site_id 0 or 1
        if ($result = $conn->query("DELETE FROM $table_name WHERE site_id > 1")) {
            printf("- Deleted where site_id is not 1 or 0\n");
        }        
    }
        
	// IMPORTANT: This does not guarantee complete data consistency, especially in third-party modules,
	// and it does not clear cached data.
	// For example -- You may have to refresh comment counts, tag counts, freeform submission counts...

    $result->close();
}

$conn->close();

?>
