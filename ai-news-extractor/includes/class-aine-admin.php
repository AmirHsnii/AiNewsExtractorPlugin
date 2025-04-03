<?php
/**
 * کلاس مدیریت پنل ادمین
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت پنل ادمین
 */
class AINE_Admin {
    
    /**
     * راه‌اندازی پنل ادمین
     */
    public static function init() {
        // افزودن فایل‌های CSS و JS
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        
        // افزودن اکشن‌های ادمین
        add_action('wp_ajax_aine_test_selectors', array(__CLASS__, 'ajax_test_selectors'));
        add_action('wp_ajax_aine_run_extraction', array(__CLASS__, 'ajax_run_extraction'));
        add_action('wp_ajax_aine_save_schedule_settings', array(__CLASS__, 'ajax_save_schedule_settings'));
    }
    
    /**
     * افزودن فایل‌های CSS و JS
     */
    public static function enqueue_scripts($hook) {
        // بررسی که آیا در صفحات افزونه هستیم
        if (strpos($hook, 'ai-news-') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'aine-admin-style',
            AINE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AINE_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'aine-admin-script',
            AINE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AINE_VERSION,
            true
        );
        
        // افزودن متغیرهای لوکال
        wp_localize_script('aine-admin-script', 'aine_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aine_admin_nonce'),
            
            // متن‌های کلی
            'test_selectors_text' => __('تست سلکتورها', 'ai-news-extractor'),
            'testing_text' => __('در حال تست...', 'ai-news-extractor'),
            'run_extraction_text' => __('استخراج اخبار', 'ai-news-extractor'),
            'extracting_text' => __('در حال استخراج و ترجمه...', 'ai-news-extractor'),
            'select_source_text' => __('لطفا یک منبع خبری انتخاب کنید', 'ai-news-extractor'),
            'connection_error_text' => __('خطا در ارتباط با سرور. لطفا مجددا تلاش کنید.', 'ai-news-extractor'),
            'save_text' => __('ذخیره تغییرات', 'ai-news-extractor'),
            'saving_text' => __('در حال ذخیره...', 'ai-news-extractor'),
            'saved_text' => __('تغییرات با موفقیت ذخیره شد', 'ai-news-extractor'),
            
            // متن‌های جدول نتایج
            'title_text' => __('عنوان', 'ai-news-extractor'),
            'status_text' => __('وضعیت', 'ai-news-extractor'),
            'message_text' => __('پیام', 'ai-news-extractor'),
            'success_text' => __('موفق', 'ai-news-extractor'),
            'duplicate_text' => __('تکراری', 'ai-news-extractor'),
            'error_text' => __('خطا', 'ai-news-extractor'),
            'edit_post_text' => __('ویرایش پست', 'ai-news-extractor'),
            
            // متن‌های مودال
            'processing_text' => __('در حال پردازش...', 'ai-news-extractor'),
            'please_wait_text' => __('لطفا صبر کنید...', 'ai-news-extractor'),
        ));
    }
    
    /**
     * نمایش صفحه اصلی
     */
    public static function main_page() {
        // اجرای دستی استخراج
        if (isset($_GET['action']) && $_GET['action'] === 'run_manual_extraction' && check_admin_referer('aine_run_manual_extraction')) {
            $ai_news_extractor = AI_News_Extractor::get_instance();
            $result = $ai_news_extractor->run_manual_extraction();
            
            if ($result) {
                $message = __('فرآیند استخراج با موفقیت اجرا شد.', 'ai-news-extractor');
                $message_type = 'success';
            } else {
                $message = __('خطا در اجرای فرآیند استخراج.', 'ai-news-extractor');
                $message_type = 'error';
            }
        }
        
        // دریافت آمار
        $sources_count = self::get_sources_count();
        $extracted_count = self::get_extracted_count();
        $post_count = self::get_post_count();
        
        // بررسی وضعیت زمانبندی
        $schedule_enabled = get_option('aine_schedule_enabled', '1');
        $schedule_time = get_option('aine_schedule_time', '00:00');
        $next_run = wp_next_scheduled('aine_daily_extraction');
        
        // نمایش قالب
        include AINE_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    /**
     * نمایش صفحه منابع
     */
    public static function sources_page() {
        // ذخیره یا ویرایش منبع
        if (isset($_POST['aine_source_submit']) && check_admin_referer('aine_save_source', 'aine_source_nonce')) {
            $source_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'url' => esc_url_raw($_POST['url']),
                'selector_title' => sanitize_text_field($_POST['selector_title']),
                'selector_content' => sanitize_text_field($_POST['selector_content']),
                'selector_image' => sanitize_text_field($_POST['selector_image']),
                'selector_date' => sanitize_text_field($_POST['selector_date']),
                'active' => isset($_POST['active']) ? 1 : 0,
            );
            
            // آیا این یک ویرایش است؟
            if (!empty($_POST['source_id'])) {
                $source_id = intval($_POST['source_id']);
                AINE_Source::update_source($source_id, $source_data);
                $message = __('منبع با موفقیت به‌روز شد.', 'ai-news-extractor');
                
                // اضافه یا به‌روزرسانی در جدول زمانبندی
                self::update_schedule_source($source_id, 5, 1);
            } else {
                $source_id = AINE_Source::add_source($source_data);
                $message = __('منبع جدید با موفقیت اضافه شد.', 'ai-news-extractor');
                
                // اضافه کردن به جدول زمانبندی
                if ($source_id) {
                    self::update_schedule_source($source_id, 5, 1);
                }
            }
        }
        
        // حذف منبع
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['source_id']) && check_admin_referer('aine_delete_source')) {
            $source_id = intval($_GET['source_id']);
            AINE_Source::delete_source($source_id);
            self::delete_schedule_source($source_id);
            $message = __('منبع با موفقیت حذف شد.', 'ai-news-extractor');
        }
        
        // تغییر وضعیت فعال/غیرفعال
        if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['source_id']) && check_admin_referer('aine_toggle_source')) {
            $source_id = intval($_GET['source_id']);
            $active = isset($_GET['active']) && $_GET['active'] == '1';
            AINE_Source::toggle_source($source_id, $active);
            $message = $active 
                ? __('منبع با موفقیت فعال شد.', 'ai-news-extractor')
                : __('منبع با موفقیت غیرفعال شد.', 'ai-news-extractor');
        }
        
        // ویرایش منبع؟
        $editing = false;
        $source = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['source_id'])) {
            $source_id = intval($_GET['source_id']);
            $source = AINE_Source::get_source($source_id);
            if ($source) {
                $editing = true;
            }
        }
        
        // دریافت همه منابع
        $sources = AINE_Source::get_all_sources();
        
        // نمایش قالب
        include AINE_PLUGIN_DIR . 'templates/admin/sources.php';
    }
    
    /**
     * نمایش صفحه تاریخچه
     */
    public static function history_page() {
        global $wpdb;
        
        // تعداد آیتم در هر صفحه
        $per_page = 20;
        
        // شماره صفحه فعلی
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // دریافت تاریخچه
        $table_history = $wpdb->prefix . 'aine_history';
        $table_sources = $wpdb->prefix . 'aine_sources';
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_history");
        
        $history_items = $wpdb->get_results(
            "SELECT h.*, s.name as source_name 
             FROM $table_history AS h 
             LEFT JOIN $table_sources AS s ON h.source_id = s.id 
             ORDER BY h.extracted_at DESC 
             LIMIT $per_page OFFSET $offset"
        );
        
        // نمایش قالب
        include AINE_PLUGIN_DIR . 'templates/admin/history.php';
    }
    
    /**
     * نمایش صفحه تنظیمات زمانبندی
     */
    public static function schedule_page() {
        global $wpdb;
        
        $table_sources = $wpdb->prefix . 'aine_sources';
        $table_schedule = $wpdb->prefix . 'aine_schedule_sources';
        
        // دریافت منابع با تنظیمات زمانبندی
        $sources_with_schedule = $wpdb->get_results(
            "SELECT s.*, IFNULL(ss.news_count, 5) AS news_count, IFNULL(ss.enabled, 0)
			AS schedule_enabled
            FROM $table_sources AS s
            LEFT JOIN $table_schedule AS ss ON s.id = ss.source_id
            WHERE s.active = 1
            ORDER BY s.name ASC"
        );
        
        // ذخیره تنظیمات
        if (isset($_POST['aine_schedule_submit']) && check_admin_referer('aine_save_schedule', 'aine_schedule_nonce')) {
            $source_settings = isset($_POST['source_settings']) ? $_POST['source_settings'] : array();
            
            foreach ($source_settings as $source_id => $settings) {
                $news_count = isset($settings['news_count']) ? intval($settings['news_count']) : 5;
                $enabled = isset($settings['enabled']) ? 1 : 0;
                
                self::update_schedule_source($source_id, $news_count, $enabled);
            }
            
            $message = __('تنظیمات زمانبندی با موفقیت ذخیره شد.', 'ai-news-extractor');
        }
        
        // نمایش قالب
        include AINE_PLUGIN_DIR . 'templates/admin/schedule.php';
    }
    
    /**
     * نمایش صفحه تنظیمات
     */
    public static function settings_page() {
        // ذخیره تنظیمات
        if (isset($_POST['aine_settings_submit']) && check_admin_referer('aine_save_settings', 'aine_settings_nonce')) {
            // تنظیمات اجرای خودکار
            $schedule_time = sanitize_text_field($_POST['schedule_time']);
            update_option('aine_schedule_time', $schedule_time);
            
            // ذخیره تنظیم فعال/غیرفعال بودن زمانبندی
            $schedule_enabled = isset($_POST['schedule_enabled']) ? '1' : '0';
            update_option('aine_schedule_enabled', $schedule_enabled);
            
            // ذخیره تنظیم نمایش منبع
            if (isset($_POST['show_source_credit'])) {
                update_option('aine_show_source_credit', sanitize_text_field($_POST['show_source_credit']));
            }
            
            // گرفتن نمونه از کلاس اصلی برای بازنشانی زمانبندی
            $ai_news_extractor = AI_News_Extractor::get_instance();
            $ai_news_extractor->reset_schedule();
            
            $message = __('تنظیمات با موفقیت ذخیره شد.', 'ai-news-extractor');
        }
        
        // دریافت تنظیمات فعلی
        $schedule_time = get_option('aine_schedule_time', '00:00');
        $schedule_enabled = get_option('aine_schedule_enabled', '1');
        
        // آزمایش اتصال به سرویس ترجمه
        if (isset($_POST['test_api_connection']) && check_admin_referer('aine_test_api', 'aine_api_nonce')) {
            $translator = new AINE_Translator();
            $test_result = $translator->test_api_connection();
        }
        
        // نمایش قالب
        include AINE_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * آزمایش سلکتورها با AJAX
     */
    public static function ajax_test_selectors() {
        // بررسی امنیتی
        check_ajax_referer('aine_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('شما اجازه انجام این عملیات را ندارید.', 'ai-news-extractor')));
        }
        
        // دریافت پارامترها
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $selectors = array(
            'title' => isset($_POST['selector_title']) ? sanitize_text_field($_POST['selector_title']) : '',
            'content' => isset($_POST['selector_content']) ? sanitize_text_field($_POST['selector_content']) : '',
            'image' => isset($_POST['selector_image']) ? sanitize_text_field($_POST['selector_image']) : '',
            'date' => isset($_POST['selector_date']) ? sanitize_text_field($_POST['selector_date']) : '',
        );
        
        // بررسی URL
        if (empty($url)) {
            wp_send_json_error(array('message' => __('آدرس URL الزامی است.', 'ai-news-extractor')));
        }
        
        // تست سلکتورها
        $result = AINE_Source::test_selectors($url, $selectors);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('سلکتورها با موفقیت تست شدند.', 'ai-news-extractor'),
                'data' => $result['data']
            ));
        } else {
            wp_send_json_error(array('message' => __('خطا در تست سلکتورها. لطفا مجددا بررسی کنید.', 'ai-news-extractor')));
        }
    }
    
    /**
     * اجرای استخراج خبر با AJAX
     */
    public static function ajax_run_extraction() {
        // بررسی امنیتی
        check_ajax_referer('aine_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('شما اجازه انجام این عملیات را ندارید.', 'ai-news-extractor')));
        }
        
        // دریافت منبع
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        if ($source_id <= 0) {
            wp_send_json_error(array('message' => __('منبع خبری نامعتبر است.', 'ai-news-extractor')));
        }
        
        $source = AINE_Source::get_source($source_id);
        if (!$source) {
            wp_send_json_error(array('message' => __('منبع خبری یافت نشد.', 'ai-news-extractor')));
        }
        
        // تعداد اخبار
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        if ($limit <= 0 || $limit > 10) {
            $limit = 5;
        }
        
        // اجرای استخراج
        $extractor = new AINE_Extractor();
        $news_items = $extractor->extract_news($source, $limit);
        
        if (empty($news_items)) {
            wp_send_json_error(array('message' => __('هیچ خبری از این منبع استخراج نشد.', 'ai-news-extractor')));
        }
        
        // ترجمه و ایجاد پست
        $translator = new AINE_Translator();
        $post_creator = new AINE_Post_Creator();
        $results = array();
        
        foreach ($news_items as $news) {
            // بررسی تکراری نبودن خبر
            if (AINE_Extractor::is_news_exists($news['url'])) {
                $results[] = array(
                    'title' => $news['title'],
                    'status' => 'duplicate',
                    'message' => __('این خبر قبلا استخراج شده است.', 'ai-news-extractor')
                );
                continue;
            }
            
            // ترجمه محتوا
            $translated_title = $translator->translate($news['title'], 'EN', 'FA');
            $translated_content = $translator->translate($news['content'], 'EN', 'FA');
            
            // ایجاد پست
            $post_id = $post_creator->create_post(array(
                'title' => $translated_title,
                'content' => $translated_content,
                'image_url' => $news['image'],
                'source_url' => $news['url'],
                'source_name' => $source->name
            ));
            
            // ثبت در تاریخچه
            if ($post_id) {
                AINE_Extractor::log_extraction($source->id, $news['url'], $news['title'], $post_id, 'completed');
                $results[] = array(
                    'title' => $news['title'],
                    'status' => 'success',
                    'message' => __('با موفقیت استخراج و ترجمه شد.', 'ai-news-extractor'),
                    'post_id' => $post_id,
                    'edit_url' => get_edit_post_link($post_id, '')
                );
            } else {
                AINE_Extractor::log_extraction($source->id, $news['url'], $news['title'], 0, 'failed');
                $results[] = array(
                    'title' => $news['title'],
                    'status' => 'error',
                    'message' => __('خطا در ایجاد پست.', 'ai-news-extractor')
                );
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d خبر با موفقیت پردازش شد.', 'ai-news-extractor'), count($results)),
            'results' => $results
        ));
    }
    
    /**
     * ذخیره تنظیمات زمانبندی با AJAX
     */
    public static function ajax_save_schedule_settings() {
        // بررسی امنیتی
        check_ajax_referer('aine_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('شما اجازه انجام این عملیات را ندارید.', 'ai-news-extractor')));
        }
        
        // دریافت داده‌های ارسالی
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        $news_count = isset($_POST['news_count']) ? intval($_POST['news_count']) : 5;
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        
        if ($source_id <= 0) {
            wp_send_json_error(array('message' => __('منبع خبری نامعتبر است.', 'ai-news-extractor')));
        }
        
        // به‌روزرسانی تنظیمات
        $result = self::update_schedule_source($source_id, $news_count, $enabled);
        
        if ($result) {
            wp_send_json_success(array('message' => __('تنظیمات با موفقیت ذخیره شد.', 'ai-news-extractor')));
        } else {
            wp_send_json_error(array('message' => __('خطا در ذخیره تنظیمات.', 'ai-news-extractor')));
        }
    }
    
    /**
     * به‌روزرسانی تنظیمات زمانبندی منبع
     */
    public static function update_schedule_source($source_id, $news_count, $enabled) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_schedule_sources';
        
        // بررسی وجود رکورد
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE source_id = %d",
            $source_id
        ));
        
        // به‌روزرسانی یا ایجاد
        if ($exists) {
            return $wpdb->update(
                $table_name,
                array(
                    'news_count' => $news_count,
                    'enabled' => $enabled
                ),
                array('source_id' => $source_id)
            );
        } else {
            return $wpdb->insert(
                $table_name,
                array(
                    'source_id' => $source_id,
                    'news_count' => $news_count,
                    'enabled' => $enabled
                )
            );
        }
    }
    
    /**
     * حذف تنظیمات زمانبندی منبع
     */
    public static function delete_schedule_source($source_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_schedule_sources';
        
        return $wpdb->delete($table_name, array('source_id' => $source_id));
    }
    
    /**
     * دریافت تعداد منابع
     *
     * @return int تعداد منابع
     */
    private static function get_sources_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * دریافت تعداد استخراج‌ها
     *
     * @return int تعداد استخراج‌ها
     */
    private static function get_extracted_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_history';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * دریافت تعداد پست‌های ایجاد شده
     *
     * @return int تعداد پست‌ها
     */
    private static function get_post_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_history';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE post_id > 0");
    }
}