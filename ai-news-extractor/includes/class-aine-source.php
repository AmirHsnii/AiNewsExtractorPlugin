<?php
/**
 * کلاس مدیریت منابع خبری
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت منابع خبری
 */
class AINE_Source {
    
    /**
     * دریافت یک منبع با شناسه
     *
     * @param int $source_id شناسه منبع
     * @return object|null منبع خبری یا null
     */
    public static function get_source($source_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        $source = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $source_id
        ));
        
        return $source;
    }
    
    /**
     * دریافت همه منابع
     *
     * @return array لیست منابع خبری
     */
    public static function get_all_sources() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        $sources = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY name ASC"
        );
        
        return $sources;
    }
    
    /**
     * دریافت منابع فعال
     *
     * @return array لیست منابع خبری فعال
     */
    public static function get_active_sources() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        $sources = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE active = 1 ORDER BY name ASC"
        );
        
        return $sources;
    }
    
    /**
     * افزودن منبع جدید
     *
     * @param array $data اطلاعات منبع
     * @return int|false شناسه منبع یا false در صورت خطا
     */
    public static function add_source($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        // آماده‌سازی سلکتورها
        $selectors = array(
            'title' => array('type' => 'xpath', 'value' => ''),
            'content' => array('type' => 'xpath', 'value' => ''),
            'image' => array('type' => 'xpath', 'value' => ''),
            'date' => array('type' => 'xpath', 'value' => '')
        );
        
        // بررسی و اضافه کردن سلکتورها
        if (isset($data['selector_title_type']) && isset($data['selector_title_value'])) {
            $selectors['title'] = array(
                'type' => sanitize_text_field($data['selector_title_type']),
                'value' => sanitize_text_field($data['selector_title_value'])
            );
        } elseif (isset($data['selector_title'])) {
            $selectors['title'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_title'])
            );
        }
        
        if (isset($data['selector_content_type']) && isset($data['selector_content_value'])) {
            $selectors['content'] = array(
                'type' => sanitize_text_field($data['selector_content_type']),
                'value' => sanitize_text_field($data['selector_content_value'])
            );
        } elseif (isset($data['selector_content'])) {
            $selectors['content'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_content'])
            );
        }
        
        if (isset($data['selector_image_type']) && isset($data['selector_image_value'])) {
            $selectors['image'] = array(
                'type' => sanitize_text_field($data['selector_image_type']),
                'value' => sanitize_text_field($data['selector_image_value'])
            );
        } elseif (isset($data['selector_image'])) {
            $selectors['image'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_image'])
            );
        }
        
        if (isset($data['selector_date_type']) && isset($data['selector_date_value'])) {
            $selectors['date'] = array(
                'type' => sanitize_text_field($data['selector_date_type']),
                'value' => sanitize_text_field($data['selector_date_value'])
            );
        } elseif (isset($data['selector_date'])) {
            $selectors['date'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_date'])
            );
        }
        
        // آماده‌سازی داده‌ها برای درج
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'url' => esc_url_raw($data['url']),
            'selectors' => json_encode($selectors),
            'active' => isset($data['active']) ? 1 : 0
        );
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * به‌روزرسانی منبع
     *
     * @param int $source_id شناسه منبع
     * @param array $data اطلاعات منبع
     * @return bool موفقیت یا شکست
     */
    public static function update_source($source_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        // آماده‌سازی سلکتورها
        $selectors = array(
            'title' => array('type' => 'xpath', 'value' => ''),
            'content' => array('type' => 'xpath', 'value' => ''),
            'image' => array('type' => 'xpath', 'value' => ''),
            'date' => array('type' => 'xpath', 'value' => '')
        );
        
        // بررسی و اضافه کردن سلکتورها
        if (isset($data['selector_title_type']) && isset($data['selector_title_value'])) {
            $selectors['title'] = array(
                'type' => sanitize_text_field($data['selector_title_type']),
                'value' => sanitize_text_field($data['selector_title_value'])
            );
        } elseif (isset($data['selector_title'])) {
            $selectors['title'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_title'])
            );
        }
        
        if (isset($data['selector_content_type']) && isset($data['selector_content_value'])) {
            $selectors['content'] = array(
                'type' => sanitize_text_field($data['selector_content_type']),
                'value' => sanitize_text_field($data['selector_content_value'])
            );
        } elseif (isset($data['selector_content'])) {
            $selectors['content'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_content'])
            );
        }
        
        if (isset($data['selector_image_type']) && isset($data['selector_image_value'])) {
            $selectors['image'] = array(
                'type' => sanitize_text_field($data['selector_image_type']),
                'value' => sanitize_text_field($data['selector_image_value'])
            );
        } elseif (isset($data['selector_image'])) {
            $selectors['image'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_image'])
            );
        }
        
        if (isset($data['selector_date_type']) && isset($data['selector_date_value'])) {
            $selectors['date'] = array(
                'type' => sanitize_text_field($data['selector_date_type']),
                'value' => sanitize_text_field($data['selector_date_value'])
            );
        } elseif (isset($data['selector_date'])) {
            $selectors['date'] = array(
                'type' => 'xpath',
                'value' => sanitize_text_field($data['selector_date'])
            );
        }
        
        // آماده‌سازی داده‌ها برای به‌روزرسانی
        $update_data = array(
            'name' => sanitize_text_field($data['name']),
            'url' => esc_url_raw($data['url']),
            'selectors' => json_encode($selectors),
            'active' => isset($data['active']) ? 1 : 0
        );
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $source_id)
        );
        
        return $result !== false;
    }
    
    /**
     * حذف منبع
     *
     * @param int $source_id شناسه منبع
     * @return bool موفقیت یا شکست
     */
    public static function delete_source($source_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $source_id)
        );
        
        return $result !== false;
    }
    
    /**
     * تغییر وضعیت فعال/غیرفعال منبع
     *
     * @param int $source_id شناسه منبع
     * @param bool $active وضعیت فعال بودن
     * @return bool موفقیت یا شکست
     */
    public static function toggle_source($source_id, $active) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_sources';
        
        $result = $wpdb->update(
            $table_name,
            array('active' => $active ? 1 : 0),
            array('id' => $source_id)
        );
        
        return $result !== false;
    }
    
    /**
     * آزمایش سلکتورها
     *
     * @param string $url آدرس URL
     * @param array $selectors سلکتورها
     * @return array نتیجه آزمایش
     */
    public static function test_selectors($url, $selectors) {
        // آماده‌سازی سلکتورها به فرمت جدید
        $structured_selectors = array();
        
        // سلکتور عنوان
        if (isset($selectors['title_type']) && isset($selectors['title'])) {
            $structured_selectors['title'] = array(
                'type' => $selectors['title_type'],
                'value' => $selectors['title']
            );
        } elseif (isset($selectors['title'])) {
            $structured_selectors['title'] = array(
                'type' => 'xpath',
                'value' => $selectors['title']
            );
        }
        
        // سلکتور محتوا
        if (isset($selectors['content_type']) && isset($selectors['content'])) {
            $structured_selectors['content'] = array(
                'type' => $selectors['content_type'],
                'value' => $selectors['content']
            );
        } elseif (isset($selectors['content'])) {
            $structured_selectors['content'] = array(
                'type' => 'xpath',
                'value' => $selectors['content']
            );
        }
        
        // سلکتور تصویر
        if (isset($selectors['image_type']) && isset($selectors['image'])) {
            $structured_selectors['image'] = array(
                'type' => $selectors['image_type'],
                'value' => $selectors['image']
            );
        } elseif (isset($selectors['image'])) {
            $structured_selectors['image'] = array(
                'type' => 'xpath',
                'value' => $selectors['image']
            );
        }
        
        // سلکتور تاریخ
        if (isset($selectors['date_type']) && isset($selectors['date'])) {
            $structured_selectors['date'] = array(
                'type' => $selectors['date_type'],
                'value' => $selectors['date']
            );
        } elseif (isset($selectors['date'])) {
            $structured_selectors['date'] = array(
                'type' => 'xpath',
                'value' => $selectors['date']
            );
        }
        
        // استفاده از Extractor برای آزمایش سلکتورها
        $extractor = new AINE_Extractor();
        $result = $extractor->test_extraction($url, $structured_selectors);
        
        return $result;
    }
}