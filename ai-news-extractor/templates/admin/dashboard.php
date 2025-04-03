<?php
/**
 * قالب داشبورد اصلی افزونه
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
        <h1><?php _e('استخراج اخبار هوش مصنوعی', 'ai-news-extractor'); ?></h1>
        <p class="aine-dashboard-subtitle"><?php _e('ابزاری برای استخراج خودکار اخبار هوش مصنوعی از منابع خارجی و ترجمه آنها به فارسی', 'ai-news-extractor'); ?></p>
    </div>
    
    <?php if (isset($message)) : ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="aine-dashboard-grid">
        <div class="aine-column">
            <div class="aine-card">
                <h2><?php _e('آمار', 'ai-news-extractor'); ?></h2>
                
                <div class="aine-stats-cards">
                    <div class="aine-stat-card">
                        <div class="aine-stat-icon">
                            <span class="dashicons dashicons-rss"></span>
                        </div>
                        <div class="aine-stat-content">
                            <div class="aine-stat-number"><?php echo $sources_count; ?></div>
                            <div class="aine-stat-title"><?php _e('منابع خبری', 'ai-news-extractor'); ?></div>
                        </div>
                    </div>
                    
                    <div class="aine-stat-card">
                        <div class="aine-stat-icon">
                            <span class="dashicons dashicons-admin-page"></span>
                        </div>
                        <div class="aine-stat-content">
                            <div class="aine-stat-number"><?php echo $extracted_count; ?></div>
                            <div class="aine-stat-title"><?php _e('استخراج شده', 'ai-news-extractor'); ?></div>
                        </div>
                    </div>
                    
                    <div class="aine-stat-card">
                        <div class="aine-stat-icon">
                            <span class="dashicons dashicons-welcome-write-blog"></span>
                        </div>
                        <div class="aine-stat-content">
                            <div class="aine-stat-number"><?php echo $post_count; ?></div>
                            <div class="aine-stat-title"><?php _e('پست‌ها', 'ai-news-extractor'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="aine-card">
                <h2><?php _e('استخراج سریع', 'ai-news-extractor'); ?></h2>
                
                <div class="aine-quick-extraction-form">
                    <div class="aine-form-group">
                        <label for="aine-quick-source" class="aine-form-label"><?php _e('منبع خبری:', 'ai-news-extractor'); ?></label>
                        <select id="aine-quick-source" class="aine-form-select">
                            <option value=""><?php _e('انتخاب منبع خبری...', 'ai-news-extractor'); ?></option>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'aine_sources';
                            $sources = $wpdb->get_results("SELECT * FROM $table_name WHERE active = 1 ORDER BY name ASC");
                            
                            foreach ($sources as $source) {
                                echo '<option value="' . esc_attr($source->id) . '">' . esc_html($source->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="aine-form-group">
                        <label for="aine-quick-limit" class="aine-form-label"><?php _e('تعداد اخبار:', 'ai-news-extractor'); ?></label>
                        <select id="aine-quick-limit" class="aine-form-select">
                            <?php for ($i = 1; $i <= 10; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, 5); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="aine-form-actions">
                        <button id="aine-run-extraction" class="aine-btn aine-btn-primary" disabled>
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('استخراج اخبار', 'ai-news-extractor'); ?>
                        </button>
                    </div>
                </div>
                
                <div id="aine-extraction-results" style="display: none;">
                    <h3><?php _e('نتایج استخراج', 'ai-news-extractor'); ?></h3>
                    <div id="aine-results-container"></div>
                </div>
            </div>
            
            <div class="aine-card">
                <h2><?php _e('اجرای دستی استخراج', 'ai-news-extractor'); ?></h2>
                <p><?php _e('با کلیک روی دکمه زیر می‌توانید فرآیند استخراج را به صورت دستی اجرا کنید:', 'ai-news-extractor'); ?></p>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ai-news-extractor&action=run_manual_extraction'), 'aine_run_manual_extraction'); ?>" class="aine-btn aine-btn-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('اجرای دستی استخراج اخبار', 'ai-news-extractor'); ?>
                </a>
                
                <p class="description"><?php _e('این عملیات اخبار جدید را از تمام منابع فعال در زمانبندی استخراج می‌کند.', 'ai-news-extractor'); ?></p>
            </div>
        </div>
        
        <div class="aine-column">
            <div class="aine-card">
                <h2><?php _e('وضعیت سیستم', 'ai-news-extractor'); ?></h2>
                
                <div class="aine-status-items">
                    <div class="aine-status-item">
                        <div class="aine-status-label"><?php _e('زمانبندی استخراج روزانه:', 'ai-news-extractor'); ?></div>
                        <div class="<?php echo $schedule_enabled === '1' ? 'aine-status-active' : 'aine-status-inactive'; ?>">
                            <?php echo $schedule_enabled === '1' ? __('فعال', 'ai-news-extractor') : __('غیرفعال', 'ai-news-extractor'); ?>
                        </div>
                    </div>
                    
                    <?php if ($schedule_enabled === '1') : ?>
                        <div class="aine-status-item">
                            <div class="aine-status-label"><?php _e('زمان اجرای روزانه:', 'ai-news-extractor'); ?></div>
                            <div><?php echo esc_html($schedule_time); ?></div>
                        </div>
                        
                        <?php if ($next_run) : ?>
                            <div class="aine-status-item">
                                <div class="aine-status-label"><?php _e('اجرای بعدی:', 'ai-news-extractor'); ?></div>
                                <div><?php echo esc_html(date_i18n('Y-m-d H:i:s', $next_run)); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="aine-status-item">
                        <div class="aine-status-label"><?php _e('آخرین استخراج:', 'ai-news-extractor'); ?></div>
                        <div>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'aine_history';
                            $last_extraction = $wpdb->get_var("SELECT extracted_at FROM $table_name ORDER BY extracted_at DESC LIMIT 1");
                            
                            echo $last_extraction ? esc_html(date_i18n('Y-m-d H:i:s', strtotime($last_extraction))) : __('هیچ', 'ai-news-extractor');
                            ?>
                        </div>
                    </div>
                    
                    <div class="aine-status-item">
                        <div class="aine-status-label"><?php _e('سرویس ترجمه:', 'ai-news-extractor'); ?></div>
                        <div class="aine-status-active"><?php _e('Google Translate (رایگان)', 'ai-news-extractor'); ?></div>
                    </div>
                    
                    <div class="aine-status-item">
                        <div class="aine-status-label"><?php _e('منابع فعال:', 'ai-news-extractor'); ?></div>
                        <div>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'aine_sources';
                            $active_sources = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE active = 1");
                            
                            echo $active_sources;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="aine-card">
                <h2><?php _e('دسترسی سریع', 'ai-news-extractor'); ?></h2>
                
                <div class="aine-quick-links">
                    <a href="<?php echo admin_url('admin.php?page=ai-news-sources'); ?>" class="aine-quick-link">
                        <span class="dashicons dashicons-rss"></span>
                        <div class="aine-link-text"><?php _e('مدیریت منابع', 'ai-news-extractor'); ?></div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-news-schedule'); ?>" class="aine-quick-link">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <div class="aine-link-text"><?php _e('تنظیمات زمانبندی', 'ai-news-extractor'); ?></div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-news-history'); ?>" class="aine-quick-link">
                        <span class="dashicons dashicons-backup"></span>
                        <div class="aine-link-text"><?php _e('تاریخچه استخراج', 'ai-news-extractor'); ?></div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-news-settings'); ?>" class="aine-quick-link">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <div class="aine-link-text"><?php _e('تنظیمات', 'ai-news-extractor'); ?></div>
                    </a>
                </div>
            </div>
            
            <div class="aine-card">
                <h2><?php _e('راهنمای شروع کار', 'ai-news-extractor'); ?></h2>
                
                <div class="aine-getting-started-steps">
                    <div class="aine-step">
                        <div class="aine-step-number">1</div>
                        <div class="aine-step-content">
                            <h3><?php _e('افزودن منابع خبری', 'ai-news-extractor'); ?></h3>
                            <p><?php _e('ابتدا به بخش "منابع خبری" بروید و منابع مورد نظر خود را با سلکتورهای مناسب اضافه کنید.', 'ai-news-extractor'); ?></p>
                        </div>
                    </div>
                    
                    <div class="aine-step">
                        <div class="aine-step-number">2</div>
                        <div class="aine-step-content">
                            <h3><?php _e('تنظیم زمانبندی', 'ai-news-extractor'); ?></h3>
                            <p><?php _e('به بخش "تنظیمات زمانبندی" بروید و تعداد اخبار مورد نظر از هر منبع و فعال بودن آنها را مشخص کنید.', 'ai-news-extractor'); ?></p>
                        </div>
                    </div>
                    
                    <div class="aine-step">
                        <div class="aine-step-number">3</div>
                        <div class="aine-step-content">
                            <h3><?php _e('استخراج دستی یا خودکار', 'ai-news-extractor'); ?></h3>
                            <p><?php _e('می‌توانید منتظر اجرای خودکار زمانبندی شده باشید یا از قسمت "استخراج سریع" یا "اجرای دستی" استفاده کنید.', 'ai-news-extractor'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال بارگذاری -->
<div id="aine-extraction-modal" class="aine-modal">
    <div class="aine-modal-content">
        <span class="aine-modal-close">&times;</span>
        <div class="aine-modal-header">
            <h3><?php _e('استخراج اخبار', 'ai-news-extractor'); ?></h3>
        </div>
        <div class="aine-extraction-progress">
            <div class="aine-loading-spinner"></div>
            <p id="aine-progress-message"><?php _e('در حال استخراج و ترجمه اخبار...', 'ai-news-extractor'); ?></p>
        </div>
    </div>
</div>