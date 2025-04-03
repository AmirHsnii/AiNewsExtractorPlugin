<?php
/**
 * قالب فرم افزودن/ویرایش منبع خبری
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// آماده‌سازی مقادیر سلکتورها
$selectors = array(
    'title' => array('type' => 'xpath', 'value' => ''),
    'content' => array('type' => 'xpath', 'value' => ''),
    'image' => array('type' => 'xpath', 'value' => ''),
    'date' => array('type' => 'xpath', 'value' => '')
);

if ($editing && !empty($source->selectors)) {
    $saved_selectors = json_decode($source->selectors, true);
    
    // اگر ساختار قدیمی باشد (فقط مقدار سلکتور)
    if (isset($saved_selectors['title']) && !is_array($saved_selectors['title'])) {
        foreach ($saved_selectors as $key => $value) {
            $selectors[$key]['value'] = $value;
        }
    } else {
        // ساختار جدید (آرایه‌ای با type و value)
        $selectors = $saved_selectors;
    }
}
?>

<div class="aine-form-wrap">
    <form method="post" action="" class="aine-source-form">
        <?php wp_nonce_field('aine_save_source', 'aine_source_nonce'); ?>
        
        <?php if ($editing) : ?>
            <input type="hidden" name="source_id" value="<?php echo esc_attr($source->id); ?>">
        <?php endif; ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name"><?php _e('نام منبع', 'ai-news-extractor'); ?></label></th>
                <td>
                    <input type="text" name="name" id="name" value="<?php echo $editing ? esc_attr($source->name) : ''; ?>" class="regular-text" required>
                    <p class="description"><?php _e('نام منبع خبری (برای شناسایی آسان)', 'ai-news-extractor'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="url"><?php _e('آدرس URL', 'ai-news-extractor'); ?></label></th>
                <td>
                    <input type="url" name="url" id="url" value="<?php echo $editing ? esc_url($source->url) : ''; ?>" class="regular-text" required>
                    <p class="description"><?php _e('آدرس صفحه دسته‌بندی یا لیست اخبار (صفحه‌ای که لینک‌های اخبار در آن قرار دارند)', 'ai-news-extractor'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label><?php _e('سلکتور عنوان', 'ai-news-extractor'); ?></label></th>
                <td class="selector-field">
                    <div class="selector-type">
                        <label class="inline-radio">
                            <input type="radio" name="selector_title_type" value="css" <?php checked($selectors['title']['type'], 'css'); ?>>
                            <span><?php _e('CSS', 'ai-news-extractor'); ?></span>
                        </label>
                        <label class="inline-radio">
                            <input type="radio" name="selector_title_type" value="xpath" <?php checked($selectors['title']['type'], 'xpath'); ?>>
                            <span><?php _e('XPath', 'ai-news-extractor'); ?></span>
                        </label>
                    </div>
                    <input type="text" name="selector_title_value" id="selector_title" value="<?php echo esc_attr($selectors['title']['value']); ?>" class="regular-text" required>
                    <p class="description selector-description selector-css" <?php if ($selectors['title']['type'] !== 'css') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال‌ها: <code>#article-title</code> یا <code>.post-title</code> یا <code>h1.title</code>', 'ai-news-extractor'); ?>
                    </p>
                    <p class="description selector-description selector-xpath" <?php if ($selectors['title']['type'] !== 'xpath') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال: <code>//h1[@class="title"]</code> یا <code>//div[@id="content"]/h1</code>', 'ai-news-extractor'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label><?php _e('سلکتور محتوا', 'ai-news-extractor'); ?></label></th>
                <td class="selector-field">
                    <div class="selector-type">
                        <label class="inline-radio">
                            <input type="radio" name="selector_content_type" value="css" <?php checked($selectors['content']['type'], 'css'); ?>>
                            <span><?php _e('CSS', 'ai-news-extractor'); ?></span>
                        </label>
                        <label class="inline-radio">
                            <input type="radio" name="selector_content_type" value="xpath" <?php checked($selectors['content']['type'], 'xpath'); ?>>
                            <span><?php _e('XPath', 'ai-news-extractor'); ?></span>
                        </label>
                    </div>
                    <input type="text" name="selector_content_value" id="selector_content" value="<?php echo esc_attr($selectors['content']['value']); ?>" class="regular-text" required>
                    <p class="description selector-description selector-css" <?php if ($selectors['content']['type'] !== 'css') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال‌ها: <code>#article-body</code> یا <code>.post-content</code> یا <code>div.content</code>', 'ai-news-extractor'); ?>
                    </p>
                    <p class="description selector-description selector-xpath" <?php if ($selectors['content']['type'] !== 'xpath') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال: <code>//div[@class="content"]</code> یا <code>//article/div[@class="body"]</code>', 'ai-news-extractor'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label><?php _e('سلکتور تصویر', 'ai-news-extractor'); ?></label></th>
                <td class="selector-field">
                    <div class="selector-type">
                        <label class="inline-radio">
                            <input type="radio" name="selector_image_type" value="css" <?php checked($selectors['image']['type'], 'css'); ?>>
                            <span><?php _e('CSS', 'ai-news-extractor'); ?></span>
                        </label>
                        <label class="inline-radio">
                            <input type="radio" name="selector_image_type" value="xpath" <?php checked($selectors['image']['type'], 'xpath'); ?>>
                            <span><?php _e('XPath', 'ai-news-extractor'); ?></span>
                        </label>
                    </div>
                    <input type="text" name="selector_image_value" id="selector_image" value="<?php echo esc_attr($selectors['image']['value']); ?>" class="regular-text">
                    <p class="description selector-description selector-css" <?php if ($selectors['image']['type'] !== 'css') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال‌ها: <code>.featured-image img</code> یا <code>#main-image</code>', 'ai-news-extractor'); ?>
                    </p>
                    <p class="description selector-description selector-xpath" <?php if ($selectors['image']['type'] !== 'xpath') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال: <code>//div[@class="featured-image"]/img</code> یا <code>//img[@id="main-image"]</code>', 'ai-news-extractor'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label><?php _e('سلکتور تاریخ', 'ai-news-extractor'); ?></label></th>
                <td class="selector-field">
                    <div class="selector-type">
                        <label class="inline-radio">
                            <input type="radio" name="selector_date_type" value="css" <?php checked($selectors['date']['type'], 'css'); ?>>
                            <span><?php _e('CSS', 'ai-news-extractor'); ?></span>
                        </label>
                        <label class="inline-radio">
                            <input type="radio" name="selector_date_type" value="xpath" <?php checked($selectors['date']['type'], 'xpath'); ?>>
                            <span><?php _e('XPath', 'ai-news-extractor'); ?></span>
                        </label>
                    </div>
                    <input type="text" name="selector_date_value" id="selector_date" value="<?php echo esc_attr($selectors['date']['value']); ?>" class="regular-text">
                    <p class="description selector-description selector-css" <?php if ($selectors['date']['type'] !== 'css') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال‌ها: <code>.post-date</code> یا <code>time.published</code>', 'ai-news-extractor'); ?>
                    </p>
                    <p class="description selector-description selector-xpath" <?php if ($selectors['date']['type'] !== 'xpath') echo 'style="display:none;"'; ?>>
                        <?php _e('مثال: <code>//time[@class="published"]</code> یا <code>//span[@class="date"]</code>', 'ai-news-extractor'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="active"><?php _e('وضعیت', 'ai-news-extractor'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="active" id="active" value="1" <?php echo $editing && $source->active ? 'checked' : ''; ?>>
                        <?php _e('فعال', 'ai-news-extractor'); ?>
                    </label>
                    <p class="description"><?php _e('فعال/غیرفعال بودن استخراج اخبار از این منبع', 'ai-news-extractor'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="aine_source_submit" class="button button-primary">
                <?php echo $editing ? __('به‌روزرسانی منبع', 'ai-news-extractor') : __('افزودن منبع', 'ai-news-extractor'); ?>
            </button>
            <button type="button" id="aine-test-selectors" class="button button-secondary">
                <?php _e('تست سلکتورها', 'ai-news-extractor'); ?>
            </button>
        </p>
    </form>
</div>

<div id="aine-test-results" style="display: none;">
    <h3><?php _e('نتایج تست سلکتورها', 'ai-news-extractor'); ?></h3>
    <div class="aine-loading-spinner"></div>
</div>

<style>
.selector-field {
    position: relative;
}
.selector-type {
    margin-bottom: 10px;
}
.inline-radio {
    margin-right: 15px;
    display: inline-block;
}
.selector-description {
    margin-top: 5px !important;
}
#aine-test-results {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // نمایش/مخفی کردن توضیحات سلکتور بر اساس نوع انتخاب شده
    $('input[name^="selector_"][name$="_type"]').change(function() {
        var field = $(this).attr('name').replace('_type', '');
        var type = $(this).val();
        
        $('.selector-description').hide();
        $(this).closest('.selector-field').find('.selector-' + type).show();
    });
});
</script>