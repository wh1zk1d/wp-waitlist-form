<?php
/**
 * Plugin Name: Waitlist Form
 * Description: Add a simple 'Join the waitlist' form to your website
 * Plugin URI: https://github.com/wh1zk1d/wp-waitlist-form
 * Author: Jannik Baranczyk
 * Version: 1.0
 */

  global $wf_db_version;
  $wf_db_version = '1.0';

  // Create the table
  function wf_install() {
    global $wpdb;
    global $wf_db_version;

    $table_name = $wpdb->prefix . 'waitlistform';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      email varchar(320) DEFAULT '' NOT NULL,
      PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);

    add_option("wf_db_version", "1.0");
  }

  // Create table on plugin activation
  register_activation_hook(__FILE__, 'wf_install');
 
  // The actual form
  function wf_plugin() {
    $content = '';
    $content .= '<h2>Warteliste</h2>';

    $content .= '<form method="POST" action="http://localhost:10008/waitlist-confirm/">';

    $content .= '<label for="email">E-Mail</label>';
    $content .= '<input type="email" name="email" required />';

    $content .= '<input type="submit" name="wf_submit" />';

    $content .= '</form>';

    return $content;
  }
  add_shortcode('waitlist-form', 'wf_plugin');

  // Capture form data and save it to DB
  function wf_capture() {
    global $wpdb;

    if (isset($_POST['wf_submit'])) {
      $email = sanitize_text_field($_POST['email']);
      if (!$email || !is_email($email)) {
        exit("Missing or invalid email");
      }

      $wpdb->insert(
        $wpdb->prefix."waitlistform",
        array(
          'email' => $email,
          'time' => current_time('Y-m-d H:i:s')
        )
      );

      echo "<strong>E-Mail: " . $email . "</strong>";
    }
  }
  add_action('wp_head', 'wf_capture');

  // Add Top-level menu
  function wf_options_page_html() {
    global $wpdb;

    $entries = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."waitlistform");

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
    echo '<h2>Alle Einträge</h2>';
    echo '<table class="widefat">';
    echo '<thead';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>E-Mail</th>';
    echo '<th>Datum</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($entries as $key => $entry) {
      if ($key % 2 == 0) {
        echo '<tr class="alternate">';
      } else {
        echo '<tr>';
      }
      echo '<td>'.$entry->id.'</td>';
      echo '<td>'.$entry->email.'</td>';
      echo '<td>'.$entry->time.'</td>';
      echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '<br /><button class="button button-primary">Als CSV exportieren</button>';
    echo '</div>';
  }

  function wf_options_page() {
    add_menu_page(
      'Waitlist Form',
      'Waitlist Form',
      'manage_options',
      'wf',
      'wf_options_page_html',
      '',
      70
    );
  }
  add_action('admin_menu', 'wf_options_page');
 ?>