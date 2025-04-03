/**
 * جاوااسکریپت داشبورد مدرن افزونه
 *
 * @package AI_News_Extractor
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Admin.js loaded successfully');
        
        // دریافت المان‌های مورد نیاز
        const extractBtn = $('#aine-run-extraction');
        const resultsContainer = $('#aine-results-container');
        const resultsSection = $('#aine-extraction-results');
        const sourceSelect = $('#aine-quick-source');
        const limitSelect = $('#aine-quick-limit');
        const extractionModal = $('#aine-extraction-modal');
        const progressMessage = $('#aine-progress-message');
        const modalClose = $('.aine-modal-close');
        const testSelectorsBtn = $('#aine-test-selectors');
        const testResultsContainer = $('#aine-test-results');
        
        // فعال/غیرفعال کردن دکمه استخراج بر اساس انتخاب منبع
        sourceSelect.on('change', function() {
            console.log('Source changed to:', $(this).val());
            extractBtn.prop('disabled', !$(this).val());
        });
        
        // نمایش/مخفی کردن توضیحات سلکتور بر اساس نوع انتخاب شده
        $('input[name^="selector_"][name$="_type"]').change(function() {
            var field = $(this).attr('name').replace('_type', '');
            var type = $(this).val();
            
            $('.selector-description').hide();
            $(this).closest('.selector-field').find('.selector-' + type).show();
        });
        
        // تست سلکتورها
        if (testSelectorsBtn.length > 0) {
            testSelectorsBtn.on('click', function(e) {
                e.preventDefault();
                
                // دریافت مقادیر
                const url = $('input[name="url"]').val();
                const selectorData = {};
                
                // جمع‌آوری سلکتورها
                $('.selector-field').each(function() {
                    const fieldName = $(this).find('input[type="text"]').attr('name');
                    const baseName = fieldName.replace('_value', '');
                    const fieldType = $('input[name="' + baseName + '_type"]:checked').val();
                    const fieldValue = $(this).find('input[type="text"]').val();
                    
                    selectorData[baseName + '_type'] = fieldType;
                    selectorData[baseName] = fieldValue;
                });
                
                // بررسی URL
                if (!url) {
                    alert('لطفاً آدرس URL را وارد کنید.');
                    return;
                }
                
                // نمایش بخش نتایج با لودر
                testResultsContainer.html('<div class="aine-loading-spinner"></div><p>در حال آزمایش سلکتورها...</p>').show();
                
                // ارسال درخواست AJAX
                $.ajax({
                    url: aine_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aine_test_selectors',
                        nonce: aine_vars.nonce,
                        url: url,
                        ...selectorData
                    },
                    success: function(response) {
                        console.log('Test selectors response:', response);
                        
                        if (response.success) {
                            let resultsHtml = '<div class="notice notice-success is-dismissible"><p>سلکتورها با موفقیت تست شدند.</p></div>';
                            
                            resultsHtml += '<table class="widefat">';
                            resultsHtml += '<thead><tr><th>سلکتور</th><th>نوع</th><th>تعداد</th><th>نمونه</th></tr></thead>';
                            resultsHtml += '<tbody>';
                            
                            const data = response.data.data;
                            
                            // سلکتور عنوان
                            if (data.title) {
                                resultsHtml += '<tr>';
                                resultsHtml += '<td><strong>عنوان</strong></td>';
                                resultsHtml += '<td>' + (data.title.type === 'css' ? 'CSS' : 'XPath') + '</td>';
                                resultsHtml += '<td>' + data.title.count + '</td>';
                                resultsHtml += '<td>' + data.title.sample + '</td>';
                                resultsHtml += '</tr>';
                            }
                            
                            // سلکتور محتوا
                            if (data.content) {
                                resultsHtml += '<tr>';
                                resultsHtml += '<td><strong>محتوا</strong></td>';
                                resultsHtml += '<td>' + (data.content.type === 'css' ? 'CSS' : 'XPath') + '</td>';
                                resultsHtml += '<td>' + data.content.count + '</td>';
                                resultsHtml += '<td>' + data.content.sample + '</td>';
                                resultsHtml += '</tr>';
                            }
                            
                            // سلکتور تصویر
                            if (data.image) {
                                resultsHtml += '<tr>';
                                resultsHtml += '<td><strong>تصویر</strong></td>';
                                resultsHtml += '<td>' + (data.image.type === 'css' ? 'CSS' : 'XPath') + '</td>';
                                resultsHtml += '<td>' + data.image.count + '</td>';
                                resultsHtml += '<td>';
                                if (data.image.sample) {
                                    resultsHtml += '<img src="' + data.image.sample + '" style="max-width:200px; max-height:100px;" />';
                                } else {
                                    resultsHtml += 'بدون تصویر';
                                }
                                resultsHtml += '</td>';
                                resultsHtml += '</tr>';
                            }
                            
                            // سلکتور تاریخ
                            if (data.date) {
                                resultsHtml += '<tr>';
                                resultsHtml += '<td><strong>تاریخ</strong></td>';
                                resultsHtml += '<td>' + (data.date.type === 'css' ? 'CSS' : 'XPath') + '</td>';
                                resultsHtml += '<td>' + data.date.count + '</td>';
                                resultsHtml += '<td>' + data.date.sample + '</td>';
                                resultsHtml += '</tr>';
                            }
                            
                            resultsHtml += '</tbody></table>';
                            
                            testResultsContainer.html(resultsHtml);
                        } else {
                            testResultsContainer.html('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown);
                        testResultsContainer.html(
                            '<div class="notice notice-error is-dismissible">' +
                            '<p>خطا در ارتباط با سرور: ' + textStatus + ' - ' + errorThrown + '</p>' +
                            (jqXHR.responseText ? '<details><summary>جزئیات بیشتر</summary><pre>' + jqXHR.responseText + '</pre></details>' : '') +
                            '</div>'
                        );
                    }
                });
            });
        }
        
        // اجرای استخراج
        extractBtn.on('click', function(e) {
            e.preventDefault();
            console.log('Extract button clicked');
            
            const sourceId = sourceSelect.val();
            const limit = limitSelect.val();
            
            console.log('Extraction parameters:', {
                sourceId: sourceId,
                limit: limit
            });
            
            if (!sourceId) {
                alert(aine_vars.select_source_text || 'لطفا یک منبع خبری انتخاب کنید.');
                return;
            }
            
            // نمایش مودال بارگذاری
            extractionModal.fadeIn(300);
            
            // تنظیم متن پیشرفت
            progressMessage.text(aine_vars.extracting_text || 'در حال استخراج و ترجمه اخبار...');
            
            console.log('Sending AJAX request for extraction');
            
            // ارسال درخواست AJAX با مدیریت بهتر خطا
            $.ajax({
                url: aine_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aine_run_extraction',
                    nonce: aine_vars.nonce,
                    source_id: sourceId,
                    limit: limit
                },
                beforeSend: function(xhr) {
                    console.log('Request is being sent...');
                },
                success: function(response) {
                    console.log('AJAX response received:', response);
                    
                    // پنهان کردن مودال
                    extractionModal.fadeOut(300);
                    
                    // نمایش نتایج
                    resultsSection.show();
                    
                    if (response.success) {
                        console.log('Extraction successful, rendering results');
                        let resultHtml = '';
                        
                        resultHtml += '<div class="aine-result-summary notice notice-success is-dismissible">';
                        resultHtml += '<p>' + response.data.message + '</p>';
                        resultHtml += '</div>';
                        
                        resultHtml += '<div class="aine-results-table">';
                        resultHtml += '<table class="widefat aine-modern-table">';
                        resultHtml += '<thead><tr>';
                        resultHtml += '<th>' + (aine_vars.title_text || 'عنوان') + '</th>';
                        resultHtml += '<th>' + (aine_vars.status_text || 'وضعیت') + '</th>';
                        resultHtml += '<th>' + (aine_vars.message_text || 'پیام') + '</th>';
                        resultHtml += '</tr></thead>';
                        resultHtml += '<tbody>';
                        
                        if (response.data.results && response.data.results.length > 0) {
                            $.each(response.data.results, function(index, item) {
                                let statusClass = '';
                                let statusText = item.status;
                                
                                switch (item.status) {
                                    case 'success':
                                        statusClass = 'aine-status-success';
                                        statusText = aine_vars.success_text || 'موفق';
                                        break;
                                    case 'duplicate':
                                        statusClass = 'aine-status-duplicate';
                                        statusText = aine_vars.duplicate_text || 'تکراری';
                                        break;
                                    case 'error':
                                        statusClass = 'aine-status-error';
                                        statusText = aine_vars.error_text || 'خطا';
                                        break;
                                }
                                
                                resultHtml += '<tr>';
                                resultHtml += '<td class="aine-result-title">' + item.title + '</td>';
                                resultHtml += '<td><span class="aine-result-status ' + statusClass + '">' + statusText + '</span></td>';
                                resultHtml += '<td class="aine-result-message">' + item.message;
                                
                                if (item.post_id && item.edit_url) {
                                    resultHtml += ' <a href="' + item.edit_url + '" class="aine-btn aine-btn-small" target="_blank">' + 
                                                   (aine_vars.edit_post_text || 'ویرایش پست') + '</a>';
                                }
                                
                                resultHtml += '</td>';
                                resultHtml += '</tr>';
                            });
                        } else {
                            console.warn('No results found in the response');
                            resultHtml += '<tr><td colspan="3">هیچ نتیجه‌ای یافت نشد.</td></tr>';
                        }
                        
                        resultHtml += '</tbody></table></div>';
                        
                        resultsContainer.html(resultHtml);
                    } else {
                        // نمایش خطا
                        console.error('Error in response:', response.data ? response.data.message : 'No error message provided');
                        resultsContainer.html(
                            '<div class="notice notice-error is-dismissible">' +
                            '<p>' + (response.data && response.data.message ? response.data.message : 'خطای نامشخص رخ داده است.') + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    console.log('Response:', jqXHR.responseText);
                    
                    // پنهان کردن مودال
                    extractionModal.fadeOut(300);
                    
                    // آماده سازی پیام خطا با جزئیات بیشتر
                    let errorMessage = 'خطا در ارتباط با سرور: ' + textStatus;
                    if (errorThrown) {
                        errorMessage += ' - ' + errorThrown;
                    }
                    
                    // نمایش خطا با اطلاعات بیشتر
                    resultsSection.show();
                    resultsContainer.html(
                        '<div class="notice notice-error is-dismissible">' +
                        '<p>' + errorMessage + '</p>' +
                        (jqXHR.responseText ? '<details><summary>جزئیات بیشتر</summary><pre>' + jqXHR.responseText + '</pre></details>' : '') +
                        '</div>'
                    );
                },
                complete: function() {
                    console.log('AJAX request completed');
                }
            });
        });
        
        // بستن مودال
        modalClose.on('click', function() {
            extractionModal.fadeOut(300);
        });
        
        // کلیک بیرون از مودال
        $(window).on('click', function(e) {
            if ($(e.target).is(extractionModal)) {
                extractionModal.fadeOut(300);
            }
        });
        
        // عملکرد تنظیمات زمانبندی منابع
        $('.aine-schedule-toggle').on('change', function() {
            const sourceId = $(this).data('source-id');
            const enabled = $(this).prop('checked') ? 1 : 0;
            const newsCount = $('#news-count-' + sourceId).val();
            
            // ذخیره تنظیمات با AJAX
            $.ajax({
                url: aine_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aine_save_schedule_settings',
                    nonce: aine_vars.nonce,
                    source_id: sourceId,
                    news_count: newsCount,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        // نمایش پیام موفقیت‌آمیز
                        const messageHtml = '<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>';
                        $('#aine-schedule-message').html(messageHtml).show().delay(3000).fadeOut();
                    } else {
                        // نمایش خطا
                        const messageHtml = '<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>';
                        $('#aine-schedule-message').html(messageHtml).show();
                    }
                },
                error: function() {
                    const messageHtml = '<div class="notice notice-error is-dismissible"><p>خطا در ارتباط با سرور</p></div>';
                    $('#aine-schedule-message').html(messageHtml).show();
                }
            });
        });
        
        // تغییر تعداد اخبار
        $('.aine-news-count').on('change', function() {
            const sourceId = $(this).data('source-id');
            const newsCount = $(this).val();
            const enabled = $('#schedule-enabled-' + sourceId).prop('checked') ? 1 : 0;
            
            // ذخیره تنظیمات با AJAX
            $.ajax({
                url: aine_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aine_save_schedule_settings',
                    nonce: aine_vars.nonce,
                    source_id: sourceId,
                    news_count: newsCount,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        // نمایش پیام موفقیت‌آمیز
                        const messageHtml = '<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>';
                        $('#aine-schedule-message').html(messageHtml).show().delay(3000).fadeOut();
                    } else {
                        // نمایش خطا
                        const messageHtml = '<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>';
                        $('#aine-schedule-message').html(messageHtml).show();
                    }
                },
                error: function() {
                    const messageHtml = '<div class="notice notice-error is-dismissible"><p>خطا در ارتباط با سرور</p></div>';
                    $('#aine-schedule-message').html(messageHtml).show();
                }
            });
        });
        
        // اضافه کردن کلاس به جدول‌ها
        $('.widefat').addClass('aine-modern-table');
    });
})(jQuery);