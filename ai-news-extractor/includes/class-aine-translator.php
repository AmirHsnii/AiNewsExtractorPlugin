<?php
/**
 * کلاس ترجمه متن
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس ترجمه متن
 */
class AINE_Translator {
    
    /**
     * سرویس ترجمه فعال
     *
     * @var string
     */
    private $active_service = 'google';
    
    /**
     * ترجمه متن
     *
     * @param string $text متن اصلی
     * @param string $from زبان مبدا
     * @param string $to زبان مقصد
     * @return string|false متن ترجمه شده یا false در صورت خطا
     */
    public function translate($text, $from = 'EN', $to = 'FA') {
        // محدودیت طول متن
        if (strlen($text) > 10000) {
            $text = $this->split_and_translate($text, $from, $to);
            return $text;
        }
        
        // انتخاب سرویس ترجمه
        switch ($this->active_service) {
            case 'google':
                return $this->google_translate($text, $from, $to);
            default:
                return false;
        }
    }
    
    /**
     * شکستن متن طولانی و ترجمه بخش به بخش
     *
     * @param string $text متن اصلی
     * @param string $from زبان مبدا
     * @param string $to زبان مقصد
     * @return string متن ترجمه شده
     */
    private function split_and_translate($text, $from, $to) {
        // شکستن متن به بخش‌های 5000 کاراکتری
        $chunks = str_split($text, 5000);
        $translated_text = '';
        
        foreach ($chunks as $chunk) {
            $translated_chunk = $this->translate($chunk, $from, $to);
            if ($translated_chunk) {
                $translated_text .= $translated_chunk;
            } else {
                $translated_text .= $chunk; // اگر ترجمه نشد، متن اصلی را استفاده کن
            }
        }
        
        return $translated_text;
    }
    
    /**
     * ترجمه با Google Translate (رایگان)
     *
     * @param string $text متن اصلی
     * @param string $from زبان مبدا
     * @param string $to زبان مقصد
     * @return string|false متن ترجمه شده یا false در صورت خطا
     */
    private function google_translate($text, $from, $to) {
        // تبدیل کدهای زبان به فرمت مورد نیاز گوگل
        $from = strtolower($from);
        $to = strtolower($to);
        
        if ($from === 'en') $from = 'en';
        if ($to === 'fa') $to = 'fa';
        
        // کوتاه کردن متن اگر بیش از حد طولانی باشد
        if (strlen($text) > 5000) {
            return $this->split_and_translate($text, $from, $to);
        }
        
        try {
            // ایجاد URL ترجمه گوگل
            $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' . $from . '&tl=' . $to . '&dt=t&q=' . urlencode($text);
            
            // ارسال درخواست
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ));
            
            // بررسی خطا
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                error_log('Google Translate Error: ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response)));
                return false;
            }
            
            // دریافت پاسخ
            $result = wp_remote_retrieve_body($response);
            $result = json_decode($result, true);
            
            // جمع‌آوری ترجمه
            $translated_text = '';
            if (isset($result[0]) && is_array($result[0])) {
                foreach ($result[0] as $segment) {
                    if (isset($segment[0])) {
                        $translated_text .= $segment[0];
                    }
                }
            }
            
            // بررسی ترجمه
            if (empty($translated_text)) {
                error_log('Google Translate Error: Empty translation result');
                return false;
            }
            
            return $translated_text;
            
        } catch (Exception $e) {
            error_log('Google Translate Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * آزمایش اتصال به سرویس ترجمه
     * 
     * @return array نتیجه تست اتصال
     */
    public function test_api_connection() {
        try {
            // متن تست
            $test_text = 'Hello World! This is a test message.';
            
            // آزمایش ترجمه
            $translated_text = $this->translate($test_text, 'EN', 'FA');
            
            // اگر ترجمه موفقیت‌آمیز بود
            if ($translated_text && $translated_text !== $test_text) {
                return array(
                    'success' => true,
                    'message' => __('اتصال به سرویس ترجمه با موفقیت انجام شد.', 'ai-news-extractor'),
                    'sample' => array(
                        'original' => $test_text,
                        'translated' => $translated_text
                    )
                );
            } else {
                return array(
                    'success' => false,
                    'message' => __('اتصال به سرویس ترجمه امکان‌پذیر نیست. لطفاً دوباره تلاش کنید.', 'ai-news-extractor')
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در اتصال به سرویس ترجمه: ', 'ai-news-extractor') . $e->getMessage()
            );
        }
    }
    
    /**
     * تغییر سرویس ترجمه فعال
     *
     * @param string $service نام سرویس
     * @return void
     */
    public function set_active_service($service) {
        $this->active_service = $service;
    }
}