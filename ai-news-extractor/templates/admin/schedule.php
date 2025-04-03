<?php
/**
 * قالب تنظیمات زمانبندی منابع
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
        <h1><?php _e('تنظیمات زمانبندی منابع', 'ai-news-extractor'); ?></h1>
        <p class="aine-dashboard-subtitle"><?php _e('در این بخش می‌توانید تعداد اخبار برای استخراج خودکار از هر منبع و فعال یا غیرفعال بودن آن را تنظیم کنید.', 'ai-news-extractor'); ?></p>
    </div>
    
    <?php if (isset($message)) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="aine-card">
        <h2><?php _e('وضعیت زمانبندی', 'ai-news-extractor'); ?></h2>
        
        <?php 
        $schedule_enabled = get_option('aine_schedule_enabled', '1');
        $schedule_time = get_option('aine_schedule_time', '00:00');
        $next_run = wp_next_scheduled('aine_daily_extraction');
        ?>
        
        <div class="aine-status-items">
            <div class="aine-status-item">
                <div class="aine-status-label"><?php _e('وضعیت زمانبندی:', 'ai-news-extractor'); ?></div>
                <div class="<?php echo $schedule_enabled === '1' ? 'aine-status-active' : 'aine-status-inactive'; ?>">
                    <?php echo $schedule_enabled === '1' ? __('فعال', 'ai-news-extractor') : __('غیرفعال', 'ai-news-extractor'); ?>
                </div>
            </div>
            <div class="aine-status-item">
                <div class="aine-status-label"><?php _e('زمان اجرا:', 'ai-news-extractor'); ?></div>
                <div><?php echo esc_html($schedule_time); ?></div>
            </div>
            <?php if ($next_run && $schedule_enabled === '1') : ?>
                <div class="aine-status-item">
                    <div class="aine-status-label"><?php _e('اجرای بعدی:', 'ai-news-extractor'); ?></div>
                    <div><?php echo esc_html(date_i18n('Y-m-d H:i:s', $next_run)); ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <p class="description">
            <?php _e('برای تغییر این تنظیمات به بخش "تنظیمات" مراجعه کنید.', 'ai-news-extractor'); ?>
            <a href="<?php echo admin_url('admin.php?page=ai-news-settings'); ?>" class="button button-small">
                <?php _e('رفتن به تنظیمات', 'ai-news-extractor'); ?>
            </a>
        </p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('aine_save_schedule', 'aine_schedule_nonce'); ?>
        
        <div class="aine-card">
            <h2><?php _e('تنظیمات منابع', 'ai-news-extractor'); ?></h2>
            
            <?php if (empty($sources_with_schedule)) : ?>
                <p><?php _e('هیچ منبع خبری فعالی وجود ندارد.', 'ai-news-extractor'); ?></p>
            <?php else : ?>
                <table class="widefat aine-modern-table">
                    <thead>
                        <tr>
                            <th><?php _e('نام منبع', 'ai-news-extractor'); ?></th>
                            <th><?php _e('تعداد اخبار', 'ai-news-extractor'); ?></th>
                            <th><?php _e('وضعیت', 'ai-news-extractor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sources_with_schedule as $source) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($source->name); ?>
                                    <div class="row-actions">
                                        <a href="<?php echo esc_url($source->url); ?>" target="_blank"><?php _e('مشاهده سایت', 'ai-news-extractor'); ?></a>
                                    </div>
                                </td>
                                <td>
                                    <select name="source_settings[<?php echo $source->id; ?>][news_count]" class="aine-form-select">
                                        <?php for ($i = 1; $i <= 10; $i++) : ?>
                                            <option value="<?php echo $i; ?>" <?php selected($source->news_count, $i); ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <label>
                                        <input type="checkbox" name="source_settings[<?php echo $source->id; ?>][enabled]" value="1" <?php checked($source->schedule_enabled, 1); ?>>
                                        <?php _e('فعال در زمانبندی', 'ai-news-extractor'); ?>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <p class="submit">
            <button type="submit" name="aine_schedule_submit" class="aine-btn aine-btn-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('ذخیره تنظیمات', 'ai-news-extractor'); ?>
            </button>
        </p>
    </form>
    
    <div class="aine-card">
        <h2><?php _e('راهنمای تنظیمات زمانبندی', 'ai-news-extractor'); ?></h2>
        
        <div class="aine-getting-started-steps">
            <div class="aine-step">
                <div class="aine-step-number">1</div>
                <div class="aine-step-content">
                    <h3><?php _e('تعداد اخبار', 'ai-news-extractor'); ?></h3>
                    <p><?php _e('برای هر منبع می‌توانید تعیین کنید در هر اجرای خودکار چه تعداد خبر جدید استخراج شود. تعداد کمتر سرعت بیشتری دارد، اما ممکن است برخی اخبار را از دست بدهید.', 'ai-news-extractor'); ?></p>
                </div>
            </div>
            <div class="aine-step">
                <div class="aine-step-number">2</div>
                <div class="aine-step-content">
                    <h3><?php _e('وضعیت فعال‌سازی', 'ai-news-extractor'); ?></h3>
                    <p><?php _e('برای هر منبع می‌توانید تعیین کنید که آیا در زمانبندی خودکار استفاده شود یا خیر. می‌توانید بدون حذف منبع، آن را موقتاً از زمانبندی خارج کنید.', 'ai-news-extractor'); ?></p>
                </div>
            </div>
            <div class="aine-step">
                <div class="aine-step-number">3</div>
                <div class="aine-step-content">
                    <h3><?php _e('زمانبندی کلی', 'ai-news-extractor'); ?></h3>
                    <p><?php _e('در بخش تنظیمات می‌توانید زمان اجرای روزانه و فعال/غیرفعال بودن کل سیستم زمانبندی را تنظیم کنید.', 'ai-news-extractor'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>