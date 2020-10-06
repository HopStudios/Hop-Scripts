<?php

/*
    Hop Change MSM Primary Site
    INSTRUCTIONS
    Please find instructions here:
    https://gitlab.com/hop-studios/hoperations/-/wikis/ee-development/Remove-MSM-site-installation-to-keep-a-non-default-site
*/

// SET THESE VARIABLES
$host = 'localhost';
$user = 'username';
$pass = 'password';
$db = 'db_name';
$site_to_keep = '7'; // site_id of the site you want changed into the primary site site_id = 1

// If the new site relies on the channels from the old primary site, you need to move them to the new site
$channels_to_keep = '12, 49, 24, 16, 17, 18, 21, 15, 11'; // ids of all the channels you want to move into the new Site 1

// check connection or fail gracefully
$conn = new mysqli($host, $user, $pass,$db) or die("Connect failed: %s\n". $conn -> error);

if (!empty($channels_to_keep)) {
    // move all EE channel data to site_to_keep
    $conn->query("UPDATE exp_channels SET site_id = $site_to_keep WHERE channel_id IN ($channels_to_keep)");
    $conn->query("UPDATE exp_channel_titles SET site_id = $site_to_keep WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
    $conn->query("UPDATE exp_channel_data SET site_id = $site_to_keep WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
    // TODO: Get all fields for the channels to keep
    // TODO: Get all categories for the channels to keep
}

// This code need additional work for EE4+ sites
$conn->query("UPDATE exp_channel_fields SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_field_groups SET site_id = $site_to_keep WHERE site_id = 1");
// IMPORTANT: If you have matrix, uncomment below
// $conn->query("UPDATE exp_matrix_cols SET site_id = $site_to_keep WHERE site_id = 1");
// $conn->query("UPDATE exp_matrix_data SET site_id = $site_to_keep WHERE site_id = 1");

$conn->query("UPDATE exp_files SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_file_dimensions SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_categories SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_category_groups SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_category_fields SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_category_field_data SET site_id = $site_to_keep WHERE site_id = 1");
$conn->query("UPDATE exp_upload_prefs SET site_id = $site_to_keep WHERE site_id = 1");

// IMPORTANT: You might want to use the below lines to delete duplicate fields/field_groups. 
// This will delete the fields with a lower ID, so make sure that's what you actually want. Otherwise leave it commented out and delete duplicates manually.
// $conn->query("DELETE t1.* FROM exp_channel_fields t1 INNER JOIN exp_channel_fields t2 WHERE t1.field_id < t2.field_id AND t1.field_name = t2.field_name");
// $conn->query("DELETE t1.* FROM exp_field_groups t1 INNER JOIN exp_field_groups t2 WHERE t1.group_id < t2.group_id AND t1.group_name = t2.group_name");

// IMPORTANT: If you have Structure installed, uncomment below
// $conn->query("UPDATE exp_structure SET site_id = $site_to_keep WHERE site_id = 0");
// $conn->query("UPDATE exp_structure SET site_id = $site_to_keep WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
// $conn->query("UPDATE exp_structure_channels SET site_id = $site_to_keep WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
// $conn->query("UPDATE exp_structure_listings SET site_id = $site_to_keep WHERE site_id = 1 AND channel_id IN ($channels_to_keep)");
// $conn->query("UPDATE exp_structure_settings SET site_id = $site_to_keep WHERE site_id = 1");

// MENTION situations where you want to keep 2 out of 5 sites

// Find the list of tables that have the site_id column
if ($result = $conn->query("SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME IN ('site_id') AND TABLE_SCHEMA='$db'")) {
    printf("Select returned %d rows.\n", $result->num_rows);

    // push the results into an iterable
    while($row = $result->fetch_array()) {
        $rows[] = $row;
    }

    foreach($rows as $row) {
        printf($row['TABLE_NAME']);
        $table_name = $row['TABLE_NAME'];

        // Delete the records tied to the default site
        if ($result = $conn->query("DELETE FROM $table_name WHERE site_id = 1;")) {
            printf(" - Deleted where site_id is 1");
        }

        // Update the site_id of site_to_keep to become 1
        if ($result = $conn->query("UPDATE $table_name SET site_id = 1 WHERE site_id = $site_to_keep;")) {
            printf(" - Updated " . "$site_to_keep to site_id 1");
        }

        // Delete all entries that are not site_id 1
        if ($result = $conn->query("DELETE FROM $table_name WHERE site_id > 1;")) {
            printf("- Deleted where site_id is not 1 or 0 (stuff like shared snippets)\n");
        }
    }

    $result->close();
}

$conn->close();

/*
    IMPORTANT: Next Steps

    - In the CP, remove "1" from site identifier in path/url settings 
    - Look for any references to site_id= or site= in templates
    - Check on File Manager and delete duplicate upload directories
*/

?>
