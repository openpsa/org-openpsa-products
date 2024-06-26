/**
 * This is the configuration file for the module.
 * This is where you set default values for different configuration variables
 */
/* The schema databases to use */
'schemadb_product' => 'file:/org/openpsa/products/config/schemadb_product_default.php',
'schemadb_group' => 'file:/org/openpsa/products/config/schemadb_group_default.php',
'schemadb_config' => 'file:/org/openpsa/products/config/config_schemadb.php',

'unit_options' => [
    'p'  => 'piece',     // per piece
    'h'  => 'hour',      // per hour
    'd'  => 'day',       // per day
    'm'  => 'month',     // per month
    'q'  => 'quarter',   // per quarter
    'hy' => 'half-year', // per half
    'y'  => 'year',      // per annum
],

'root_group' => 0,

// Indexer related
'index_products' => true, // whether to index products
'index_groups' => true, // whether to index groups

'enable_scheduling' => false,

'show_items_in_feed' => 10,

// default schema to use when exporting products
'csv_export_schema' => 'default',

'product_page_title' => '<TOPIC_TITLE>: <PRODUCT_TITLE>',