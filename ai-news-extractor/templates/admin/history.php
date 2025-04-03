<?php
/**
 * قالب تاریخچه استخراج اخبار
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap aine-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php _e('تاریخچه استخراج اخبار', 'ai-news-extractor'); ?>
    </h1>
    <hr class="wp-header-end">
    
    <?php if (isset($message)): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="aine-history-list">
        <?php if (empty($history_items)): ?>
        <div class="aine-no-history">
            <p>
                <?php _e('هنوز هیچ استخراجی انجام نشده است.', 'ai-news-extractor'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-news-sources')); ?>" class="button button-primary">
                <?php _e('مدیریت منابع', 'ai-news-extractor'); ?>
            </a>
        </div>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('منبع', 'ai-news-extractor'); ?></th>
                    <th scope="col"><?php _e('عنوان اصلی', 'ai-news-extractor'); ?></th>
                    <th scope="col"><?php _e('تاریخ استخراج', 'ai-news-extractor'); ?></th>
                    <th scope="col"><?php _e('وضعیت', 'ai-news-extractor'); ?></th>
                    <th scope="col"><?php _e('پست', 'ai-news-extractor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_items as $item): ?>
                <tr>
                    <td>
                        <?php echo esc_html($item->source_name); ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($item->original_url); ?>" target="_blank">
                            <?php echo esc_html($item->original_title); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->extracted_at)); ?>
                    </td>
                    <td>
                        <?php
                        switch ($item->status) {
                            case 'completed':
                                echo '<span class="aine-status-success">' . __('موفق', 'ai-news-extractor') . '</span>';
                                break;
                            case 'failed':
                                echo '<span class="aine-status-error">' . __('ناموفق', 'ai-news-extractor') . '</span>';
                                break;
                            default:
                                echo '<span class="aine-status-pending">' . __('در انتظار', 'ai-news-extractor') . '</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($item->post_id > 0): ?>
                        <a href="<?php echo get_edit_post_link($item->post_id); ?>" target="_blank">
                            <?php _e('ویرایش پست', 'ai-news-extractor'); ?>
                        </a>
                        <?php else: ?>
                        <span class="aine-no-post"><?php _e('بدون پست', 'ai-news-extractor'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
        // صفحه‌بندی
        $total_pages = ceil($total_items / $per_page);
        
        if ($total_pages > 1) {
            echo '<div class="aine-pagination">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; قبلی', 'ai-news-extractor'),
                'next_text' => __('بعدی &raquo;', 'ai-news-extractor'),
                'total' => $total_pages,
                'current' => $current_page
            ));
            echo '</div>';
        }
        ?>
        
        <?php endif; ?>
    </div>
</div>