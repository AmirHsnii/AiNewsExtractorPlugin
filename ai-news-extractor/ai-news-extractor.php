<?php
/**
 * Plugin Name: AI News Extractor & Translator
 * Plugin URI: https://wiki-prompt.ir
 * Description: استخراج خودکار اخبار هوش مصنوعی از منابع خارجی و ترجمه آنها به فارسی
 * Version: 1.2.0
 * Author: AmirHosein Hoseini
 * Author URI: https://wiki-prompt.ir
 * Text Domain: ai-news-extractor
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تنظیم حالت دیباگ
define('AINE_DEBUG', true);

// در ابتدای فایل ai-news-extractor.php
try {
    // کد اصلی افزونه
} catch (Exception $e) {
    echo '<div style="color:red; padding:20px; border:2px solid red; margin:20px;">Error: ' . $e->getMessage() . '</div>';
    error_log('AI News Extractor Error: ' . $e->getMessage());
}

if (AINE_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// تعریف ثابت‌های افزونه
define('AINE_VERSION', '1.2.0');
define('AINE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AINE_ADMIN_URL', get_admin_url());

// کلاس اصلی افزونه
class AI_News_Extractor {
    
    // نمونه کلاس
    private static $instance = null;
    
    /**
     * گرفتن نمونه از کلاس (الگوی Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        // بارگذاری فایل‌های مورد نیاز
        $this->includes();
        
        // راه‌اندازی هوک‌های افزونه
        $this->init_hooks();
        
        // راه‌اندازی زمانبندی افزونه
        $this->setup_schedule();
    }
    
    /**
     * شامل کردن فایل‌های مورد نیاز
     */
    private function includes() {
        // کلاس‌های هسته افزونه
        require_once AINE_PLUGIN_DIR . 'includes/class-aine-admin.php';
        require_once AINE_PLUGIN_DIR . 'includes/class-aine-source.php';
        require_once AINE_PLUGIN_DIR . 'includes/class-aine-extractor.php';
        require_once AINE_PLUGIN_DIR . 'includes/class-aine-translator.php';
        require_once AINE_PLUGIN_DIR . 'includes/class-aine-post-creator.php';
    }
    
    /**
     * راه‌اندازی هوک‌های افزونه
     */
    private function init_hooks() {
        // هوک‌های فعال‌سازی و غیرفعال‌سازی افزونه
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // اضافه کردن منوهای مدیریت
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // راه‌اندازی بخش اجرایی افزونه
        add_action('init', array($this, 'init'));
        
        // هوک اجرای استخراج روزانه
        add_action('aine_daily_extraction', array($this, 'run_daily_extraction'));
    }
    
    /**
     * راه‌اندازی زمانبندی افزونه
     */
    public function setup_schedule() {
        // بررسی فعال بودن زمانبندی
        $schedule_enabled = get_option('aine_schedule_enabled', '1');
        
        // حذف زمانبندی قبلی در هر صورت
        $timestamp = wp_next_scheduled('aine_daily_extraction');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aine_daily_extraction');
        }
        
        // اگر زمانبندی فعال باشد، زمانبندی جدید ایجاد می‌کنیم
        if ($schedule_enabled === '1') {
            $schedule_time = get_option('aine_schedule_time', '00:00');
            $schedule_timestamp = strtotime(date('Y-m-d') . ' ' . $schedule_time . ':00');
            
            if ($schedule_timestamp < time()) {
                $schedule_timestamp = strtotime('+1 day', $schedule_timestamp);
            }
            
            wp_schedule_event($schedule_timestamp, 'daily', 'aine_daily_extraction');
            error_log('Scheduled event set for: ' . date('Y-m-d H:i:s', $schedule_timestamp));
        } else {
            error_log('Scheduled extraction is disabled');
        }
    }
    
    /**
     * متد عمومی برای بازنشانی زمانبندی
     */
    public function reset_schedule() {
        $this->setup_schedule();
    }
    
    /**
     * فعال‌سازی افزونه
     */
    public function activate() {
        // ایجاد جداول مورد نیاز در دیتابیس
        $this->create_tables();
        
        // تنظیم گزینه‌های پیش‌فرض
        add_option('aine_schedule_enabled', '1');
        add_option('aine_schedule_time', '00:00');
        add_option('aine_schedule_sources', '');
        
        // تنظیم زمانبندی
        $this->setup_schedule();
        
        // پاکسازی rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * غیرفعال‌سازی افزونه
     */
    public function deactivate() {
        // حذف زمانبندی
        $timestamp = wp_next_scheduled('aine_daily_extraction');
        wp_unschedule_event($timestamp, 'aine_daily_extraction');
        
        // پاکسازی rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * ایجاد جداول مورد نیاز در دیتابیس
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول منابع خبری
        $table_sources = $wpdb->prefix . 'aine_sources';
        $sql_sources = "CREATE TABLE $table_sources (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            selectors text NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // جدول تاریخچه استخراج
        $table_history = $wpdb->prefix . 'aine_history';
        $sql_history = "CREATE TABLE $table_history (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source_id mediumint(9) NOT NULL,
            original_url varchar(255) NOT NULL,
            original_title text NOT NULL,
            extracted_at datetime DEFAULT CURRENT_TIMESTAMP,
            post_id bigint(20) DEFAULT 0,
            status varchar(50) DEFAULT 'pending',
            PRIMARY KEY  (id),
            UNIQUE KEY original_url (original_url)
        ) $charset_collate;";
        
        // جدول تنظیمات زمانبندی منابع
        $table_schedule = $wpdb->prefix . 'aine_schedule_sources';
        $sql_schedule = "CREATE TABLE $table_schedule (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source_id mediumint(9) NOT NULL,
            news_count int(5) DEFAULT 5,
            enabled tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY source_id (source_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_sources);
        dbDelta($sql_history);
        dbDelta($sql_schedule);
    }
    
    /**
     * اضافه کردن منوهای مدیریت
     */
    public function add_admin_menu() {
        // منوی اصلی
        add_menu_page(
            __('استخراج اخبار هوش مصنوعی', 'ai-news-extractor'),
            __('اخبار هوش مصنوعی', 'ai-news-extractor'),
            'manage_options',
            'ai-news-extractor',
            array('AINE_Admin', 'main_page'),
            'dashicons-rss',
            30
        );
        
        // زیر منوها
        add_submenu_page(
            'ai-news-extractor',
            __('منابع خبری', 'ai-news-extractor'),
            __('منابع خبری', 'ai-news-extractor'),
            'manage_options',
            'ai-news-sources',
            array('AINE_Admin', 'sources_page')
        );
        
        add_submenu_page(
            'ai-news-extractor',
            __('تاریخچه استخراج', 'ai-news-extractor'),
            __('تاریخچه استخراج', 'ai-news-extractor'),
            'manage_options',
            'ai-news-history',
            array('AINE_Admin', 'history_page')
        );
        
        add_submenu_page(
            'ai-news-extractor',
            __('تنظیمات زمانبندی', 'ai-news-extractor'),
            __('تنظیمات زمانبندی', 'ai-news-extractor'),
            'manage_options',
            'ai-news-schedule',
            array('AINE_Admin', 'schedule_page')
        );
        
        add_submenu_page(
            'ai-news-extractor',
            __('تنظیمات', 'ai-news-extractor'),
            __('تنظیمات', 'ai-news-extractor'),
            'manage_options',
            'ai-news-settings',
            array('AINE_Admin', 'settings_page')
        );
    }
    
    /**
     * راه‌اندازی بخش اجرایی افزونه
     */
    public function init() {
        // ثبت نوع پست سفارشی
        $this->register_custom_post_type();
        
        // بارگذاری ترجمه‌ها
        load_plugin_textdomain('ai-news-extractor', false, AINE_PLUGIN_DIR . '/languages');
    }
    
    /**
     * ثبت نوع پست سفارشی
     */
    private function register_custom_post_type() {
        $labels = array(
            'name'               => __('اخبار هوش مصنوعی', 'ai-news-extractor'),
            'singular_name'      => __('خبر هوش مصنوعی', 'ai-news-extractor'),
            'menu_name'          => __('اخبار هوش مصنوعی', 'ai-news-extractor'),
            'add_new'            => __('افزودن خبر جدید', 'ai-news-extractor'),
            'add_new_item'       => __('افزودن خبر جدید', 'ai-news-extractor'),
            'edit_item'          => __('ویرایش خبر', 'ai-news-extractor'),
            'new_item'           => __('خبر جدید', 'ai-news-extractor'),
            'view_item'          => __('مشاهده خبر', 'ai-news-extractor'),
            'search_items'       => __('جستجوی اخبار', 'ai-news-extractor'),
            'not_found'          => __('خبری یافت نشد', 'ai-news-extractor'),
            'not_found_in_trash' => __('خبری در سطل زباله یافت نشد', 'ai-news-extractor'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'ai-news'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-rss',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        );
        
        register_post_type('news', $args);
    }
    
    /**
     * اجرای استخراج روزانه
     */
    public function run_daily_extraction() {
        try {
            error_log('Starting daily extraction process');
            
            $extractor = new AINE_Extractor();
            $processed_count = 0;
            
            // دریافت تنظیمات زمانبندی منابع
            global $wpdb;
            $table_schedule = $wpdb->prefix . 'aine_schedule_sources';
            $table_sources = $wpdb->prefix . 'aine_sources';
            
            $scheduled_sources = $wpdb->get_results(
                "SELECT s.*, ss.news_count 
                FROM $table_schedule AS ss
                JOIN $table_sources AS s ON ss.source_id = s.id
                WHERE ss.enabled = 1 AND s.active = 1"
            );
            
            if (empty($scheduled_sources)) {
                error_log('No scheduled sources found or all are disabled');
                return false;
            }
            
            foreach ($scheduled_sources as $source) {
                // استخراج اخبار از هر منبع بر اساس تعداد مشخص شده
                $news_count = isset($source->news_count) ? intval($source->news_count) : 5;
                $news_items = $extractor->extract_news($source, $news_count);
                
                if (!empty($news_items)) {
                    $translator = new AINE_Translator();
                    $post_creator = new AINE_Post_Creator();
                    
                    foreach ($news_items as $news) {
                        // بررسی تکراری نبودن خبر
                        if (AINE_Extractor::is_news_exists($news['url'])) {
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
                            $processed_count++;
                        } else {
                            AINE_Extractor::log_extraction($source->id, $news['url'], $news['title'], 0, 'failed');
                        }
                    }
                }
            }
            
            error_log('Daily extraction completed. Processed ' . $processed_count . ' news items.');
            return $processed_count;
        } catch (Exception $e) {
            error_log('Error in daily extraction: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * اجرای دستی استخراج
     */
    public function run_manual_extraction() {
        return $this->run_daily_extraction();
    }
}

// راه‌اندازی افزونه
function aine_init() {
    return AI_News_Extractor::get_instance();
}

// شروع افزونه
aine_init();