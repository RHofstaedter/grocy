<?php

// Settings can also be overwritten in two ways:
//
// First priority:
// A .txt file with the same name as the setting in /data/settingoverrides
// the content of the file is used as the setting value
//
// Second priority:
// An environment variable with the same name as the setting and prefix "GROCY_"
// so for example "GROCY_BASE_URL"
//
// Third priority:
// The settings defined here below

// Either "production", "dev", "demo" or "prerelease"
// When not "production", the application will work in a demo mode which means
// authentication is disabled and some demo data will be generated during the database schema migration
// (pass the query parameter "nodemodata", e.g. https://grocy.example.com/?nodemodata to skip that)
setting('MODE', 'production');

// The directory name of one of the available localization folders
// in the "/localization" directory (e.g. "en" or "de")
setting('DEFAULT_LOCALE', 'en');

// This is used to define the first day of a week for calendar views,
// leave empty to use the locale default
// Needs to be a number where Sunday = 0, Monday = 1 and so forth
setting('CALENDAR_FIRST_DAY_OF_WEEK', '');

// If calendars should show week numbers
setting('CALENDAR_SHOW_WEEK_OF_YEAR', true);

// Set this if you want to have a different start day for the weekly meal plan view,
// leave empty to use CALENDAR_FIRST_DAY_OF_WEEK (see above)
// Needs to be a number where Sunday = 0, Monday = 1 and so forth
// Can also be set to -1 to dynamically start the meal plan week on "today"
setting('MEAL_PLAN_FIRST_DAY_OF_WEEK', '');

// To keep it simple: Grocy does not handle any currency conversions,
// this here is used to format all money values,
// so doesn't really matter, but needs to be the
// ISO 4217 code of the currency ("USD", "EUR", "GBP", etc.)
setting('CURRENCY', 'USD');

// Your preferred unit for energy
// E.g. "kcal" or "kJ" or something else (doesn't really matter, it's only used to display energy values)
setting('ENERGY_UNIT', 'kcal');

// When running Grocy in a subdirectory, this should be set to the relative path, otherwise empty
// It needs to be set to the part (of the URL) AFTER the document root,
// if URL rewriting is disabled, including index.php
// Example with URL Rewriting support:
//  Root URL = https://example.com/grocy
//  => BASE_PATH = /grocy
// Example without URL Rewriting support:
//  Root URL = https://example.com/grocy/public/index.php/
//  => BASE_PATH = /grocy/public/index.php
setting('BASE_PATH', '');

// The base URL of your installation,
// should be just "/" when running directly under the root of a (sub)domain
// or for example "https://example.com/grocy" when using a subdirectory
setting('BASE_URL', '/');

// The plugin to use for external barcode lookups,
// must be the filename (folder "/plugins" for built-in plugins or "/data/plugins" for user plugins) without the .php extension,
// see /plugins/DemoBarcodeLookupPlugin.php for a commented example implementation
// Leave empty to disable external barcode lookups
setting('STOCK_BARCODE_LOOKUP_PLUGIN', 'OpenFoodFactsBarcodeLookupPlugin');

// If, however, your webserver does not support URL rewriting, set this to true
setting('DISABLE_URL_REWRITING', false);

// Specify an custom homepage if desired, by default the homepage will be set to the stock overview page
// This needs to be one of the following values:
// stock, shoppinglist, recipes, chores, tasks, batteries, equipment, calendar, mealplan
setting('ENTRY_PAGE', 'stock');

// Set this to true if you want to disable authentication / the login screen,
// places where user context is needed will then use the default (first existing) user
setting('DISABLE_AUTH', false);

// Either "Grocy\Middleware\DefaultAuthMiddleware", "Grocy\Middleware\ReverseProxyAuthMiddleware"
// or any class that implements Grocy\Middleware\AuthMiddleware
setting('AUTH_CLASS', 'Grocy\Middleware\DefaultAuthMiddleware');

// Options when using ReverseProxyAuthMiddleware
setting('REVERSE_PROXY_AUTH_HEADER', 'REMOTE_USER'); // The name of the HTTP header which your reverse proxy uses to pass the username (on successful authentication)
setting('REVERSE_PROXY_AUTH_USE_ENV', false); // Set to true if the username is passed as environment variable

// Options when using LdapAuthMiddleware
setting('LDAP_ADDRESS', ''); // Example value "ldap://vm-dc2019.local.berrnd.net"
setting('LDAP_BASE_DN', ''); // Example value "DC=local,DC=berrnd,DC=net"
setting('LDAP_BIND_DN', ''); // Example value "CN=grocy_bind_account,OU=service_accounts,DC=local,DC=berrnd,DC=net"
setting('LDAP_BIND_PW', ''); // Password for the above account
setting('LDAP_USER_FILTER', ''); // Example value "(OU=grocy_users)"
setting('LDAP_UID_ATTR', ''); // Windows AD: "sAMAccountName", OpenLDAP: "uid", GLAuth: "cn"

// Default permissions for new users
// the array needs to contain the technical/constant names
// See the file controllers/Users/User.php for possible values
setting('DEFAULT_PERMISSIONS', ['ADMIN']);

// "1D" (=> Code128) or "2D" (=> DataMatrix)
setting('GROCYCODE_TYPE', '2D');


// Label printer settings
setting('LABEL_PRINTER_WEBHOOK', ''); // The URI that Grocy will POST to when asked to print a label
setting('LABEL_PRINTER_RUN_SERVER', true); // Whether the webhook will be called server- or client-side
setting('LABEL_PRINTER_PARAMS', ['font_family' => 'Source Sans Pro (Regular)']); // Additional parameters supplied to the webhook
setting('LABEL_PRINTER_HOOK_JSON', true); // TRUE to use JSON or FALSE to use normal POST request variables


// Thermal printer options
// Thermal printers are receipt printers, not regular printers,
// the printer must support the ESC/POS protocol, see https://github.com/mike42/escpos-php
setting('TPRINTER_IS_NETWORK_PRINTER', false); // Set to true if it's a network printer
setting('TPRINTER_PRINT_QUANTITY_NAME', true); // Set to false if you do not want to print the quantity names (related to the shopping list)
setting('TPRINTER_PRINT_NOTES', true); // Set to false if you do not want to print notes (related to the shopping list)
setting('TPRINTER_IP', '127.0.0.1'); // IP of the network printer (does only matter if it's a network printer)
setting('TPRINTER_PORT', 9100); // Port of the network printer (does only matter if it's a network printer)
setting('TPRINTER_CONNECTOR', '/dev/usb/lp0'); // Printer device (does only matter if you use a locally attached printer)
// For USB on Linux this is often '/dev/usb/lp0', for serial printers it could be similar to '/dev/ttyS0'
// Make sure that the user that runs the webserver has permissions to write to the printer - on Linux add your webserver user to the LP group with usermod -a -G lp www-data


// Feature flags
// Here you can disable the parts which you don't need to have a less cluttered UI
// (set the setting to "false" to disable the corresponding part, which should be self explanatory)
setting('FEATURE_FLAG_STOCK', true);
setting('FEATURE_FLAG_SHOPPINGLIST', true);
setting('FEATURE_FLAG_RECIPES', true);
setting('FEATURE_FLAG_CHORES', true);
setting('FEATURE_FLAG_TASKS', true);
setting('FEATURE_FLAG_BATTERIES', true);
setting('FEATURE_FLAG_EQUIPMENT', true);
setting('FEATURE_FLAG_CALENDAR', true);
setting('FEATURE_FLAG_LABEL_PRINTER', false);

// Sub feature flags
setting('FEATURE_FLAG_STOCK_PRICE_TRACKING', true);
setting('FEATURE_FLAG_STOCK_LOCATION_TRACKING', true);
setting('FEATURE_FLAG_STOCK_BEST_BEFORE_DATE_TRACKING', true);
setting('FEATURE_FLAG_STOCK_PRODUCT_OPENED_TRACKING', true);
setting('FEATURE_FLAG_STOCK_PRODUCT_FREEZING', true);
setting('FEATURE_FLAG_STOCK_BEST_BEFORE_DATE_FIELD_NUMBER_PAD', true); // Activate the number pad in due date fields on (supported) mobile browsers
setting('FEATURE_FLAG_SHOPPINGLIST_MULTIPLE_LISTS', true);
setting('FEATURE_FLAG_RECIPES_MEALPLAN', true);
setting('FEATURE_FLAG_CHORES_ASSIGNMENTS', true);
setting('FEATURE_FLAG_THERMAL_PRINTER', false);

// Feature settings
setting('FEATURE_FLAG_DISABLE_BROWSER_BARCODE_CAMERA_SCANNING', false); // Set this to true if you want to disable the ability to scan a barcode via the device camera (Browser API)
setting('FEATURE_FLAG_AUTO_TORCH_ON_WITH_CAMERA', true); // Enables the torch automatically (if the device has one)


// Default user settings
// These settings can be changed per user and via the UI,
// below are the defaults which are used when the user has not changed the setting so far

// Night mode related
defaultUserSetting('night_mode', 'follow-system'); // "on" = Night mode is always on ; "off" = Night mode is always off / "follow-system" = System preferred color schema is used
defaultUserSetting('auto_night_mode_enabled', false); // If night mode is enabled automatically when inside a given time range (see the two settings below)
defaultUserSetting('auto_night_mode_time_range_from', '20:00'); // Format HH:mm
defaultUserSetting('auto_night_mode_time_range_to', '07:00'); // Format HH:mm
defaultUserSetting('auto_night_mode_time_range_goes_over_midnight', true); // If the time range above goes over midnight
defaultUserSetting('night_mode_enabled_internal', false); // Internal setting if night mode is actually enabled (based on the other settings)

// Generic settings
defaultUserSetting('auto_reload_on_db_change', false); // If the page should be automatically reloaded when there was an external change
defaultUserSetting('show_clock_in_header', false); // Show a clock in the header next to the logo or not
defaultUserSetting('keep_screen_on', false); // If the screen should always be kept on
defaultUserSetting('keep_screen_on_when_fullscreen_card', false); // If the screen should be kept on when a "fullscreen-card" is displayed

// Stock settings
defaultUserSetting('product_presets_location_id', -1); // Default location id for new products (-1 means no location is preset)
defaultUserSetting('product_presets_product_group_id', -1); // Default product group id for new products (-1 means no product group is preset)
defaultUserSetting('product_presets_qu_id', -1); // Default quantity unit id for new products (-1 means no quantity unit is preset)
defaultUserSetting('product_presets_default_due_days', 0); // Default due days for new products (-1 means that the product will be never overdue)
defaultUserSetting('product_presets_treat_opened_as_out_of_stock', true); // Default "Treat opened as out of stock" option for new products
defaultUserSetting('product_presets_default_stock_label_type', 0); // "Default stock entry label" option for new products (0 = No label, 1 = Single Label, 2 = Label per unit)
defaultUserSetting('stock_decimal_places_amounts', 4); // Default decimal places allowed for amounts
defaultUserSetting('stock_decimal_places_prices_input', 2); // Default decimal places allowed for prices (input)
defaultUserSetting('stock_decimal_places_prices_display', 2); // Default decimal places allowed for prices (display)
defaultUserSetting('stock_auto_decimal_separator_prices', false);  // If the decimal separator should be set automatically for amount inputs
defaultUserSetting('stock_due_soon_days', 5); // The "expiring soon" days
defaultUserSetting('stock_default_purchase_amount', 0); // The default amount prefilled on the purchase page
defaultUserSetting('stock_default_consume_amount', 1); // The default amount prefilled on the consume page
defaultUserSetting('stock_default_consume_amount_use_quick_consume_amount', false); // If the products quick consume amount should be prefilled on the consume page
defaultUserSetting('scan_mode_consume_enabled', false); // If scan mode on the consume page is enabled
defaultUserSetting('scan_mode_purchase_enabled', false); // If scan mode on the purchase page is enabled
defaultUserSetting('show_icon_on_stock_overview_page_when_product_is_on_shopping_list', true); // When enabled, an icon is shown on the stock overview page (next to the product name) when the prodcut is currently on a shopping list
defaultUserSetting('stock_overview_show_all_out_of_stock_products', false); // By default the stock overview page lists all products which are currently in stock or below their min. stock amount - when this is enabled, all (active) products are always shown
defaultUserSetting('show_purchased_date_on_purchase', false); // Whether the purchased date should be editable on purchase (defaults to today otherwise)
defaultUserSetting('show_warning_on_purchase_when_due_date_is_earlier_than_next', true); // Show a warning on purchase when the due date of the purchased product is earlier than the next due date in stock

// Shopping list settings
defaultUserSetting('shopping_list_to_stock_workflow_auto_submit_when_prefilled', false); // Automatically do the booking using the last price and the amount of the shopping list item, if the product has "Default due days" set
defaultUserSetting('shopping_list_show_calendar', false); // When enabled, a small (month view) calendar will be shown on the shopping list page
defaultUserSetting('shopping_list_round_up', false); // When enabled, all quantity amounts on the shopping list are always displayed rounded up to the nearest whole number
defaultUserSetting('shopping_list_auto_add_below_min_stock_amount', false); // If products should be automatically added to the shopping list when they are below their min. stock amount
defaultUserSetting('shopping_list_auto_add_below_min_stock_amount_list_id', 1); // When the above setting is enabled, the id of the shopping list to which the products will be added
defaultUserSetting('shopping_list_print_show_header', true); // Default for the shopping list print option "Show header"
defaultUserSetting('shopping_list_print_group_by_product_group', true); // Default for the shopping list print option "Group by product group"
defaultUserSetting('shopping_list_print_layout_type', 'table'); // Default for the shopping list print option "Layout type" (table or list)

// Recipe settings
defaultUserSetting('recipe_ingredients_group_by_product_group', false); // Group recipe ingredients by their product group
defaultUserSetting('recipes_show_list_side_by_side', true); // If the recipe should be displayed next to recipe list on the recipes page
defaultUserSetting('recipes_show_ingredient_checkbox', false); // When enabled, a little checkbox will be shown next to each ingredient to mark it as done

// Chores settings
defaultUserSetting('chores_due_soon_days', 5); // The "due soon" days
defaultUserSetting('chores_overview_swap_tracking_buttons', false); // When enabled, the "Track next chore schedule" and "Track chore execution now" buttons/menu items are swapped

// Batteries settings
defaultUserSetting('batteries_due_soon_days', 5); // The "due soon" days

// Tasks settings
defaultUserSetting('tasks_due_soon_days', 5); // The "due soon" days

// Calendar settings
defaultUserSetting('calendar_color_products', '#007bff'); // The event color (hex code) for due products
defaultUserSetting('calendar_color_tasks', '#28a745'); // The event color (hex code) for due tasks
defaultUserSetting('calendar_color_chores', '#ffc107'); // The event color (hex code) for due chores
defaultUserSetting('calendar_color_batteries', '#17a2b8'); // The event color (hex code) for due battery charge cycles
defaultUserSetting('calendar_color_meal_plan', '#6c757d'); // The event color (hex code) for meal plan items
