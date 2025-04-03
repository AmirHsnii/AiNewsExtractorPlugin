<?php
/**
 * کلاس ایجاد کننده پست
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس ایجاد کننده پست
 */
class AINE_Post_Creator {
    
    /**
     * ایجاد پست برای خبر ترجمه شده
     *
     * @param array $post_data داده‌های پست
     * @return int|false شناسه پست یا false در صورت خطا
     */
    public function create_post($post_data) {
        // آماده‌سازی داده‌های پست
        $post_arr = array(
            'post_title'    => sanitize_text_field($post_data['title']),
            'post_content'  => wp_kses_post($post_data['content']),
            'post_status'   => 'draft',
            'post_type'     => 'news',
            'post_author'   => get_current_user_id() ?: 1,
        );
        
        // ایجاد پست
        $post_id = wp_insert_post($post_arr);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // ذخیره منبع خبر به عنوان متادیتا
        update_post_meta($post_id, '_aine_source_url', esc_url_raw($post_data['source_url']));
        update_post_meta($post_id, '_aine_source_name', sanitize_text_field($post_data['source_name']));
        
        // اضافه کردن تصویر شاخص
        if (!empty($post_data['image_url'])) {
            $thumbnail_id = $this->set_featured_image($post_data['image_url'], $post_id, $post_data['title']);
            if ($thumbnail_id) {
                set_post_thumbnail($post_id, $thumbnail_id);
            }
        }
        
        return $post_id;
    }
    
    /**
     * تنظیم تصویر شاخص برای پست
     *
     * @param string $image_url آدرس تصویر
     * @param int $post_id شناسه پست
     * @param string $title عنوان تصویر
     * @return int|false شناسه تصویر آپلود شده یا false در صورت خطا
     */
    private function set_featured_image($image_url, $post_id, $title) {
        // بررسی آدرس تصویر
        if (empty($image_url)) {
            return false;
        }
        
        // بررسی وجود فایل
        $attachment_id = $this->get_attachment_id_by_url($image_url);
        if ($attachment_id) {
            return $attachment_id;
        }
        
        // آیا فایل تصویر از نوع‌های مجاز است؟
        $allowed_file_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $file_type = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_file_types)) {
            return false;
        }
        
        // اطمینان از وجود فایل‌های مورد نیاز برای آپلود
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }
        
        // دانلود تصویر به صورت موقت
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        // تعیین نام فایل
        $filename = sanitize_file_name(basename($image_url));
        if (empty(pathinfo($filename, PATHINFO_EXTENSION))) {
            $filename .= ".{$file_type}";
        }
        
        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp
        );
        
        // آپلود فایل به رسانه‌ها
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);
        
        // پاکسازی فایل موقت
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        
        if (is_wp_error($attachment_id)) {
            return false;
        }
        
        return $attachment_id;
    }
    
    /**
     * یافتن شناسه تصویر با آدرس URL
     *
     * @param string $image_url آدرس تصویر
     * @return int|null شناسه تصویر یا null
     */
    private function get_attachment_id_by_url($image_url) {
        global $wpdb;
        
        // حذف پارامترهای اضافی از URL
        $image_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|webp)$)/i', '', $image_url);
        $image_url = preg_replace('/\?.*/', '', $image_url);
        
        // بررسی در دیتابیس
        $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
        
        if (!empty($attachment)) {
            return $attachment[0];
        }
        
        return null;
    }
    
    /**
     * به‌روزرسانی پست موجود
     *
     * @param int $post_id شناسه پست
     * @param array $post_data داده‌های جدید
     * @return bool موفقیت یا شکست
     */
    public function update_post($post_id, $post_data) {
        // آماده‌سازی داده‌های پست
        $post_arr = array(
            'ID'            => $post_id,
            'post_title'    => sanitize_text_field($post_data['title']),
            'post_content'  => wp_kses_post($post_data['content']),
        );
        
        // به‌روزرسانی پست
        $result = wp_update_post($post_arr);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // به‌روزرسانی تصویر شاخص در صورت نیاز
        if (!empty($post_data['image_url'])) {
            $thumbnail_id = $this->set_featured_image($post_data['image_url'], $post_id, $post_data['title']);
            if ($thumbnail_id) {
                set_post_thumbnail($post_id, $thumbnail_id);
            }
        }
        
        return true;
    }
    
    /**
     * حذف پست
     *
     * @param int $post_id شناسه پست
     * @return bool موفقیت یا شکست
     */
    public function delete_post($post_id) {
        $result = wp_delete_post($post_id, true);
        
        return $result !== false;
    }
}