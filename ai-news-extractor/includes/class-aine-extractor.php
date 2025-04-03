<?php
/**
 * کلاس استخراج اخبار
 *
 * @package AI_News_Extractor
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس استخراج اخبار
 */
class AINE_Extractor {
    
    /**
     * استخراج اخبار از یک منبع
     *
     * @param object $source منبع خبری
     * @param int $limit تعداد اخبار
     * @return array لیست اخبار استخراج شده
     */
    public function extract_news($source, $limit = 5) {
        try {
            // برای دیباگ
            error_log('Starting extraction for source ID: ' . $source->id);
            
            $results = array();
            
            // دریافت HTML صفحه
            $html = $this->fetch_url($source->url);
            if (!$html) {
                error_log('Failed to fetch URL: ' . $source->url);
                return $results;
            }
            
            // بارگذاری کتابخانه DOM
            if (!class_exists('DOMDocument')) {
                error_log('DOMDocument class is not available');
                return $results;
            }
            
            // ایجاد DOM
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // استخراج سلکتورها
            $selectors = json_decode($source->selectors, true);
            
            // تبدیل ساختار قدیمی به جدید (برای سازگاری با نسخه‌های قبلی)
            if (isset($selectors['title']) && !is_array($selectors['title'])) {
                $old_selectors = $selectors;
                $selectors = array();
                foreach ($old_selectors as $key => $value) {
                    $selectors[$key] = array(
                        'type' => 'xpath',
                        'value' => $value
                    );
                }
            }
            
            // پیدا کردن لینک‌های اخبار
            $news_links = array();
            
            // تبدیل سلکتور عنوان به XPath
            $title_xpath = $this->get_xpath_selector($selectors['title']);
            
            // فرض می‌کنیم که سلکتور عنوان شامل لینک‌ها هم می‌شود
            $title_elements = $xpath->query($title_xpath);
            $count = 0;
            
            // اگر هیچ عنصری پیدا نشد، لاگ ثبت کنیم
            if ($title_elements->length === 0) {
                error_log('No elements found with selector: ' . print_r($selectors['title'], true) . ' (XPath: ' . $title_xpath . ')');
            }
            
            foreach ($title_elements as $element) {
                // پیدا کردن لینک
                $link = null;
                if ($element->tagName === 'a') {
                    $link = $element->getAttribute('href');
                } else {
                    $links = $xpath->query('.//a', $element);
                    if ($links->length > 0) {
                        $link = $links->item(0)->getAttribute('href');
                    }
                }
                
                // اضافه کردن به لیست
                if ($link) {
                    // تبدیل به URL کامل
                    if (strpos($link, 'http') !== 0) {
                        $parsed_url = parse_url($source->url);
                        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                        
                        if (strpos($link, '/') === 0) {
                            $link = $base_url . $link;
                        } else {
                            $link = $base_url . '/' . $link;
                        }
                    }
                    
                    $news_links[] = $link;
                    $count++;
                    
                    // رسیدن به محدودیت
                    if ($count >= $limit) {
                        break;
                    }
                }
            }
            
            // اگر هیچ لینکی پیدا نشد، لاگ ثبت کنیم
            if (empty($news_links)) {
                error_log('No news links found for source ID: ' . $source->id);
            } else {
                error_log('Found ' . count($news_links) . ' news links');
            }
            
            // استخراج محتوای هر خبر
            foreach ($news_links as $link) {
                error_log('Processing link: ' . $link);
                $news_item = $this->extract_news_content($link, $selectors);
                if ($news_item) {
                    $results[] = $news_item;
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Error in extract_news: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * استخراج محتوای یک خبر
     *
     * @param string $url آدرس خبر
     * @param array $selectors سلکتورها
     * @return array|null اطلاعات خبر
     */
    public function extract_news_content($url, $selectors) {
        try {
            // دریافت HTML صفحه
            $html = $this->fetch_url($url);
            if (!$html) {
                error_log('Failed to fetch news URL: ' . $url);
                return null;
            }
            
            // ایجاد DOM
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // استخراج عنوان
            $title = '';
            $title_xpath = $this->get_xpath_selector($selectors['title']);
            $title_elements = $xpath->query($title_xpath);
            if ($title_elements->length > 0) {
                $title = trim($title_elements->item(0)->textContent);
            } else {
                error_log('No title found for: ' . $url . ' with selector: ' . print_r($selectors['title'], true) . ' (XPath: ' . $title_xpath . ')');
            }
            
            // استخراج محتوا
            $content = '';
            $content_xpath = $this->get_xpath_selector($selectors['content']);
            $content_elements = $xpath->query($content_xpath);
            if ($content_elements->length > 0) {
                // استخراج HTML محتوا
                $content_html = $dom->saveHTML($content_elements->item(0));
                
                // پاکسازی محتوا
                $content = $this->clean_content($content_html);
            } else {
                error_log('No content found for: ' . $url . ' with selector: ' . print_r($selectors['content'], true) . ' (XPath: ' . $content_xpath . ')');
            }
            
            // استخراج تصویر
            $image = '';
            $image_xpath = $this->get_xpath_selector($selectors['image']);
            $image_elements = $xpath->query($image_xpath);
            if ($image_elements->length > 0) {
                $element = $image_elements->item(0);
                if ($element->tagName === 'img') {
                    $image = $element->getAttribute('src');
                } else {
                    $img_tags = $xpath->query('.//img', $element);
                    if ($img_tags->length > 0) {
                        $image = $img_tags->item(0)->getAttribute('src');
                    }
                }
                
                // تبدیل به URL کامل
                if ($image && strpos($image, 'http') !== 0) {
                    $parsed_url = parse_url($url);
                    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                    
                    if (strpos($image, '/') === 0) {
                        $image = $base_url . $image;
                    } else {
                        $image = $base_url . '/' . $image;
                    }
                }
            } else {
                error_log('No image found for: ' . $url . ' with selector: ' . print_r($selectors['image'], true) . ' (XPath: ' . $image_xpath . ')');
            }
            
            // استخراج تاریخ (اختیاری)
            $date = '';
            if (isset($selectors['date']) && !empty($selectors['date']['value'])) {
                $date_xpath = $this->get_xpath_selector($selectors['date']);
                $date_elements = $xpath->query($date_xpath);
                if ($date_elements->length > 0) {
                    $date = trim($date_elements->item(0)->textContent);
                }
            }
            
            // بررسی اعتبار
            if (empty($title) || empty($content)) {
                error_log('Invalid news item: empty title or content for URL: ' . $url);
                return null;
            }
            
            return array(
                'title' => $title,
                'content' => $content,
                'image' => $image,
                'date' => $date,
                'url' => $url
            );
        } catch (Exception $e) {
            error_log('Error in extract_news_content: ' . $e->getMessage() . ' for URL: ' . $url);
            return null;
        }
    }
    
    /**
     * آزمایش استخراج با سلکتورهای جدید
     *
     * @param string $url آدرس منبع
     * @param array $selectors سلکتورها
     * @return array نتایج استخراج
     */
    public function test_extraction($url, $selectors) {
        try {
            // دریافت HTML صفحه
            $html = $this->fetch_url($url);
            if (!$html) {
                return array('success' => false, 'message' => 'خطا در دریافت صفحه. لطفاً URL را بررسی کنید.');
            }
            
            // تبدیل ساختار سلکتورها
            $structured_selectors = array();
            foreach ($selectors as $key => $value) {
                if (isset($value['type']) && isset($value['value'])) {
                    $structured_selectors[$key] = $value;
                } else {
                    $structured_selectors[$key] = array(
                        'type' => 'xpath',
                        'value' => $value
                    );
                }
            }
            
            // ایجاد DOM
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            $results = array();
            
            // تست سلکتور عنوان
            $title_xpath = $this->get_xpath_selector($structured_selectors['title']);
            $title_elements = $xpath->query($title_xpath);
            if ($title_elements->length > 0) {
                $results['title'] = array(
                    'count' => $title_elements->length,
                    'sample' => trim($title_elements->item(0)->textContent),
                    'xpath' => $title_xpath,
                    'type' => $structured_selectors['title']['type']
                );
            } else {
                $results['title'] = array(
                    'count' => 0,
                    'sample' => '',
                    'xpath' => $title_xpath,
                    'type' => $structured_selectors['title']['type']
                );
            }
            
            // تست سلکتور محتوا
            $content_xpath = $this->get_xpath_selector($structured_selectors['content']);
            $content_elements = $xpath->query($content_xpath);
            if ($content_elements->length > 0) {
                $content_html = $dom->saveHTML($content_elements->item(0));
                $results['content'] = array(
                    'count' => $content_elements->length,
                    'sample' => $this->truncate_content($this->clean_content($content_html), 200),
                    'xpath' => $content_xpath,
                    'type' => $structured_selectors['content']['type']
                );
            } else {
                $results['content'] = array(
                    'count' => 0,
                    'sample' => '',
                    'xpath' => $content_xpath,
                    'type' => $structured_selectors['content']['type']
                );
            }
            
            // تست سلکتور تصویر
            $image_xpath = $this->get_xpath_selector($structured_selectors['image']);
            $image_elements = $xpath->query($image_xpath);
            if ($image_elements->length > 0) {
                $element = $image_elements->item(0);
                $image = '';
                
                if ($element->tagName === 'img') {
                    $image = $element->getAttribute('src');
                } else {
                    $img_tags = $xpath->query('.//img', $element);
                    if ($img_tags->length > 0) {
                        $image = $img_tags->item(0)->getAttribute('src');
                    }
                }
                
                $results['image'] = array(
                    'count' => $image_elements->length,
                    'sample' => $image,
                    'xpath' => $image_xpath,
                    'type' => $structured_selectors['image']['type']
                );
            } else {
                $results['image'] = array(
                    'count' => 0,
                    'sample' => '',
                    'xpath' => $image_xpath,
                    'type' => $structured_selectors['image']['type']
                );
            }
            
            // تست سلکتور تاریخ
            if (isset($structured_selectors['date']) && !empty($structured_selectors['date']['value'])) {
                $date_xpath = $this->get_xpath_selector($structured_selectors['date']);
                $date_elements = $xpath->query($date_xpath);
                if ($date_elements->length > 0) {
                    $results['date'] = array(
                        'count' => $date_elements->length,
                        'sample' => trim($date_elements->item(0)->textContent),
                        'xpath' => $date_xpath,
                        'type' => $structured_selectors['date']['type']
                    );
                } else {
                    $results['date'] = array(
                        'count' => 0,
                        'sample' => '',
                        'xpath' => $date_xpath,
                        'type' => $structured_selectors['date']['type']
                    );
                }
            }
            
            return array('success' => true, 'data' => $results);
        } catch (Exception $e) {
            error_log('Error in test_extraction: ' . $e->getMessage());
            return array('success' => false, 'message' => 'خطا در آزمایش سلکتورها: ' . $e->getMessage());
        }
    }
    
    /**
     * تبدیل سلکتور به XPath
     *
     * @param array $selector آرایه سلکتور شامل نوع و مقدار
     * @return string سلکتور XPath
     */
    private function get_xpath_selector($selector) {
        // اگر سلکتور خالی باشد
        if (empty($selector) || empty($selector['value'])) {
            return '';
        }
        
        // اگر نوع XPath باشد، مستقیماً برگردان
        if ($selector['type'] === 'xpath') {
            return $selector['value'];
        }
        
        // تبدیل سلکتور CSS به XPath
        return $this->css_to_xpath($selector['value']);
    }
    
    /**
     * تبدیل سلکتور CSS به XPath
     *
     * @param string $selector سلکتور CSS
     * @return string سلکتور XPath
     */
    private function css_to_xpath($selector) {
        // سلکتور خالی
        if (empty($selector)) {
            return '';
        }
        
        // سلکتور ID
        if (strpos($selector, '#') === 0) {
            $id = substr($selector, 1);
            return "//*[@id='$id']";
        }
        
        // سلکتور کلاس
        if (strpos($selector, '.') === 0) {
            $class = substr($selector, 1);
            return "//*[contains(@class, '$class')]";
        }
        
        // سلکتورهای ترکیبی ساده
        if (preg_match('/^([a-zA-Z0-9]+)\.([a-zA-Z0-9\-_]+)$/i', $selector, $matches)) {
            // مثال: div.content
            $tag = $matches[1];
            $class = $matches[2];
            return "//$tag[contains(@class, '$class')]";
        }
        
        if (preg_match('/^([a-zA-Z0-9]+)#([a-zA-Z0-9\-_]+)$/i', $selector, $matches)) {
            // مثال: div#content
            $tag = $matches[1];
            $id = $matches[2];
            return "//$tag[@id='$id']";
        }
        
        // سلکتور تگ ساده
        if (preg_match('/^([a-zA-Z0-9]+)$/i', $selector)) {
            return "//$selector";
        }
        
        // سلکتورهای پیچیده‌تر - برای سادگی، اینها را پشتیبانی نمی‌کنیم
        error_log('Complex CSS selector not fully supported: ' . $selector);
        return "//$selector";
    }
    
    /**
     * بررسی تکراری نبودن خبر
     *
     * @param string $url آدرس خبر
     * @return bool آیا خبر در دیتابیس موجود است
     */
    public static function is_news_exists($url) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_history';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE original_url = %s",
            $url
        ));
        
        return $count > 0;
    }
    
    /**
     * ثبت استخراج در تاریخچه
     *
     * @param int $source_id شناسه منبع
     * @param string $url آدرس خبر
     * @param string $title عنوان خبر
     * @param int $post_id شناسه پست وردپرس
     * @param string $status وضعیت
     * @return int|false شناسه ثبت
     */
    public static function log_extraction($source_id, $url, $title, $post_id = 0, $status = 'pending') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aine_history';
        
        $data = array(
            'source_id' => $source_id,
            'original_url' => $url,
            'original_title' => $title,
            'post_id' => $post_id,
            'status' => $status
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * دریافت محتوای یک URL
     *
     * @param string $url آدرس
     * @return string|false محتوای HTML
     */
    private function fetch_url($url) {
        try {
            $args = array(
                'timeout' => 30,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            );
            
            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response)) {
                error_log('Error fetching URL: ' . $url . ' - ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log('Error fetching URL: ' . $url . ' - HTTP Status Code: ' . $response_code);
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                error_log('Empty response body for URL: ' . $url);
                return false;
            }
            
            return $body;
        } catch (Exception $e) {
            error_log('Exception in fetch_url: ' . $e->getMessage() . ' for URL: ' . $url);
            return false;
        }
    }
    
    /**
     * پاکسازی محتوا
     *
     * @param string $content محتوای HTML
     * @return string محتوای پاکسازی شده
     */
    private function clean_content($content) {
        try {
            // حذف اسکریپت‌ها
            $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
            
            // حذف استایل‌ها
            $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
            
            // حذف کامنت‌ها
            $content = preg_replace('/<!--(.|\s)*?-->/', '', $content);
            
            // حذف فرم‌ها
            $content = preg_replace('/<form\b[^>]*>(.*?)<\/form>/is', '', $content);
            
            // تمیز کردن محتوا
            $content = trim($content);
            
            return $content;
        } catch (Exception $e) {
            error_log('Error in clean_content: ' . $e->getMessage());
            return $content;
        }
    }
    
    /**
     * کوتاه کردن محتوا برای نمایش
     *
     * @param string $content محتوا
     * @param int $length طول
     * @return string محتوای کوتاه شده
     */
    private function truncate_content($content, $length = 100) {
        try {
            $plain_text = strip_tags($content);
            
            if (strlen($plain_text) <= $length) {
                return $plain_text;
            }
            
            return substr($plain_text, 0, $length) . '...';
        } catch (Exception $e) {
            error_log('Error in truncate_content: ' . $e->getMessage());
            return substr(strip_tags($content), 0, $length) . '...';
        }
    }
}