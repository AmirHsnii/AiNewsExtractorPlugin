<?php
/**
 * قالب تنظیمات کلی افزونه
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap aine-modern-dashboard rtl">
    <div class="aine-dashboard-header">
        <h1><?php _e('تنظیمات افزونه', 'ai-news-extractor'); ?></h1>
        <p class="aine-dashboard-subtitle"><?php _e('تنظیمات کلی و زمانبندی افزونه را در این صفحه انجام دهید.', 'ai-news-extractor'); ?></p>
    </div>
    
    <?php if (isset($message)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('aine_save_settings', 'aine_settings_nonce'); ?>
        
        <div class="aine-card">
            <h2><?php _e('تنظیمات زمانبندی', 'ai-news-extractor'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="schedule_enabled"><?php _e('فعال‌سازی استخراج خودکار', 'ai-news-extractor'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="schedule_enabled" id="schedule_enabled" value="1" <?php checked('1', $schedule_enabled); ?>>
                            <?php _e('فعال بودن زمانبندی استخراج خودکار اخبار', 'ai-news-extractor'); ?>
                        </label>
                        <p class="description"><?php _e('با غیرفعال کردن این گزینه، استخراج خودکار اخبار طبق زمانبندی انجام نخواهد شد.', 'ai-news-extractor'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="schedule_time"><?php _e('زمان اجرای روزانه', 'ai-news-extractor'); ?></label>
                    </th>
                    <td>
                        <input type="time" name="schedule_time" id="schedule_time" value="<?php echo esc_attr($schedule_time); ?>" class="regular-text">
                        <p class="description"><?php _e('زمان اجرای استخراج خودکار روزانه (به وقت سرور)', 'ai-news-extractor'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="aine-card">
            <h2><?php _e('تنظیمات نمایش', 'ai-news-extractor'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="show_source_credit"><?php _e('نمایش منبع خبر', 'ai-news-extractor'); ?></label>
                    </th>
                    <td>
                        <select name="show_source_credit" id="show_source_credit" class="regular-text">
                            <option value="1" <?php selected(get_option('aine_show_source_credit', '1'), '1'); ?>><?php _e('نمایش با لینک به منبع اصلی', 'ai-news-extractor'); ?></option>
                            <option value="2" <?php selected(get_option('aine_show_source_credit', '1'), '2'); ?>><?php _e('نمایش بدون لینک', 'ai-news-extractor'); ?></option>
                            <option value="0" <?php selected(get_option('aine_show_source_credit', '1'), '0'); ?>><?php _e('عدم نمایش منبع', 'ai-news-extractor'); ?></option>
                        </select>
                        <p class="description"><?php _e('نحوه نمایش منبع خبر در انتهای محتوای استخراج شده', 'ai-news-extractor'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" name="aine_settings_submit" class="aine-btn aine-btn-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('ذخیره تنظیمات', 'ai-news-extractor'); ?>
            </button>
        </p>
    </form>
    
    <div class="aine-card">
        <h2><?php _e('آزمایش اتصال به سرویس ترجمه', 'ai-news-extractor'); ?></h2>
        <p><?php _e('برای اطمینان از عملکرد صحیح سرویس ترجمه، می‌توانید آن را آزمایش کنید:', 'ai-news-extractor'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('aine_test_api', 'aine_api_nonce'); ?>
            
            <p>
                <button type="submit" name="test_api_connection" class="button button-secondary">
                    <span class="dashicons dashicons-translation" style="margin-top: 3px;"></span>
                    <?php _e('آزمایش اتصال به سرویس ترجمه', 'ai-news-extractor'); ?>
                </button>
            </p>
        </form>
        
        <?php if (isset($test_result)) : ?>
            <div class="notice <?php echo (isset($test_result['success']) && $test_result['success']) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                <p><?php echo isset($test_result['message']) ? esc_html($test_result['message']) : __('خطای نامشخص در آزمایش اتصال', 'ai-news-extractor'); ?></p>
            </div>
            
            <?php if (isset($test_result['success']) && $test_result['success'] && isset($test_result['sample'])) : ?>
                <div class="aine-test-result">
                    <p><strong><?php _e('نمونه ترجمه:', 'ai-news-extractor'); ?></strong></p>
                    <div class="aine-test-original">
                        <strong><?php _e('متن اصلی:', 'ai-news-extractor'); ?></strong> <?php echo esc_html($test_result['sample']['original']); ?>
                    </div>
                    <div class="aine-test-translated">
                        <strong><?php _e('ترجمه شده:', 'ai-news-extractor'); ?></strong> <?php echo esc_html($test_result['sample']['translated']); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="aine-card">
        <h2><?php _e('راهنمای تنظیمات', 'ai-news-extractor'); ?></h2>
        
        <div class="aine-getting-started-steps">
            <div class="aine-step">
                <div class="aine-step-number">1</div>
                <div class="aine-step-content">
                    <h3><?php _e('زمانبندی استخراج', 'ai-news-extractor'); ?></h3>
                    <p><?php _e('با فعال کردن زمانبندی، افزونه به صورت خودکار در زمان تعیین شده اخبار جدید را استخراج می‌کند. برای منابع و تعداد اخبار هر منبع به قسمت "تنظیمات زمانبندی" مراجعه کنید.', 'ai-news-extractor'); ?></p>
                </div>
            </div>
            <div class="aine-step">
                <div class="aine-step-number">2</div>
                <div class="aine-step-content">
                    <h3><?php _e('منبع خبر', 'ai-news-extractor'); ?></h3>
                    <p><?php _e('به منظور رعایت حقوق مالکیت معنوی، توصیه می‌شود نام منبع خبر همراه با لینک به منبع اصلی نمایش داده شود.', 'ai-news-extractor'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>