<?php
/*
Plugin Name: Contact Form 7 & LINE 通知
Description: 當 Contact Form 7 表單被提交時，發送通知至 LINE。
Version: 1.0
Author: <a href="https://digitor.com.tw/" target="_blank">Dung-Yi Lin</a> | <a href="https://cheerer.tw" target="_blank">Cheerer Creative Inc.</a>
*/

// 創建設置選項頁面
add_action('admin_menu', 'line_notify_menu');
function line_notify_menu() {
    add_options_page(
        'LINE Notify 設置', // 頁面標題
        'LINE Notify 設置', // 選單標題
        'manage_options',   // 能訪問這個設置頁面的權限
        'line-notify-settings', // 選單 slug
        'line_notify_settings_page' // 用來顯示頁面內容的函數
    );
}

// 顯示設置頁面的 HTML
function line_notify_settings_page() {
    ?>
    <div class="wrap">
        <h2>LINE Notify 設置</h2>
        <form method="post" action="options.php">
            <?php settings_fields('line-notify-settings-group'); ?>
            <?php do_settings_sections('line-notify-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Access Token</th>
                    <td><input type="text" name="line_notify_token" value="<?php echo esc_attr(get_option('line_notify_token')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">包含的表單域</th>
                    <td><input type="text" name="line_notify_form_fields" value="<?php echo esc_attr(get_option('line_notify_form_fields')); ?>" /></td>
                </tr>
            </table>
            <p>
                Access Token 可以從 <a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a> 的個人頁面中取得。
            </p>
            <p>
                在「包含的表單域」中，請輸入要包含在通知中的表單域名稱，用逗號分隔。<br>
                Ex: your-name,your-email,your-phone
            </p>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 在外掛列表中添加設定連結
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cf7_line_notify_settings_link');

function cf7_line_notify_settings_link($links) {
    // 添加設定連結
    $settings_link = '<a href="options-general.php?page=line-notify-settings">' . __('設定') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// 註冊新增的設置選項
add_action('admin_init', 'line_notify_settings_init');
function line_notify_settings_init() {
    register_setting('line-notify-settings-group', 'line_notify_token');
    register_setting('line-notify-settings-group', 'line_notify_form_fields');
}


// 修改原有的通知功能，以使用設置中的 Access Token
add_action('wpcf7_before_send_mail', 'send_line_notification');
function send_line_notification($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        // 取得表單提交的資料
        $posted_data = $submission->get_posted_data();

        // 從設置中獲取用戶指定的表單域
        $fields = explode(',', get_option('line_notify_form_fields'));
        $message = "新的報名:\n";
        foreach ($fields as $field) {
            $field = trim($field); // 去除可能的空格
            if (isset($posted_data[$field])) {
                $message .= ucfirst($field) . ": " . $posted_data[$field] . "\n";
            }
        }

        // LINE Notify API URL
        $url = 'https://notify-api.line.me/api/notify';

        // 從設置中獲取 Access Token
        $token = get_option('line_notify_token');

        // 發送請求到 LINE Notify
        $response = wp_remote_post($url, array(
            'headers' => array('Authorization' => 'Bearer ' . $token),
            'body' => array('message' => $message)
        ));
    }
}

