/**
 * Variable Content Creator — Admin script
 * Faz 3: AJAX cascade, Faz 5: batch progress
 */
(function($) {
    'use strict';

    var $il = $('#vcc-il');
    var $ilce = $('#vcc-ilce');
    var $semt = $('#vcc-semt');
    var $estimate = $('#vcc-estimate');
    var $btnCreate = $('#vcc-btn-create');
    var $btnPreview = $('#vcc-btn-preview');
    var $progressArea = $('#vcc-progress-area');
    var $progressBar = $('#vcc-progress-bar');
    var $progressText = $('#vcc-progress-text');
    var $errors = $('#vcc-errors');

    if (!$il.length) return;

    // İl değişince ilçeleri getir (Faz 3)
    $il.on('change', function() {
        var il = $(this).val();
        $ilce.find('option:not(:first):not([value="ALL"])').remove();
        $semt.find('option:not(:first):not([value="ALL"])').remove();
        $estimate.hide();
        if (!il) return;
        fetchTowns(il);
    });

    // İlçe değişince semtleri getir (Faz 3)
    $ilce.on('change', function() {
        var il = $il.val();
        var ilce = $(this).val();
        $semt.find('option:not(:first):not([value="ALL"])').remove();
        $estimate.hide();
        if (!il || !ilce || ilce === 'ALL') return;
        fetchDistricts(il, ilce);
    });

    // İl/İlçe/Semt değişince tahmini sayı (Faz 5’te backend’den alınacak)
    function updateEstimate() {
        var il = $il.val();
        if (!il) return;
        var ilce = $ilce.val() || 'ALL';
        var semt = $semt.val() || 'ALL';
        if (ilce === 'ALL') semt = 'ALL';
        $.post(vccAdmin.ajaxUrl, {
            action: 'vcc_estimate_count',
            nonce: vccAdmin.nonce,
            il: il,
            ilce: ilce,
            semt: semt,
            include_province_only: $('#vcc-include-province-only').is(':checked') ? 1 : 0,
            include_ilce_no_semt: $('#vcc-include-ilce-no-semt').is(':checked') ? 1 : 0
        }).done(function(res) {
            if (res && res.count !== undefined && res.count > 0) {
                $estimate.show().html((vccAdmin.i18n.estimate || 'Tahmini %d taslak üretilecek.').replace('%d', res.count));
            } else {
                $estimate.hide();
            }
        }).fail(function() {
            $estimate.hide();
        });
    }

    $ilce.add($semt).on('change', function() {
        updateEstimate();
    });
    $('#vcc-include-province-only, #vcc-include-ilce-no-semt').on('change', function() {
        updateEstimate();
    });

    function fetchTowns(ilName) {
        var $opts = $ilce.find('option:first-child').nextUntil('[value="ALL"]');
        if ($opts.length) $opts.remove();
        $ilce.append($('<option>').attr('value', '').text(vccAdmin.i18n.loading || 'Yükleniyor…'));
        $.post(vccAdmin.ajaxUrl, {
            action: 'vcc_get_towns',
            nonce: vccAdmin.nonce,
            il_name: ilName
        }).done(function(data) {
            $ilce.find('option[value=""]').last().remove();
            if (Array.isArray(data)) {
                data.forEach(function(name) {
                    $ilce.append($('<option>').attr('value', name).text(name));
                });
            }
            $ilce.append($('<option>').attr('value', 'ALL').text(vccAdmin.i18n.tumu || 'Tümü'));
            updateEstimate();
        }).fail(function() {
            $ilce.find('option[value=""]').last().remove();
            alert(vccAdmin.i18n.error || 'Bir hata oluştu.');
        });
    }

    function fetchDistricts(ilName, ilceName) {
        var $opts = $semt.find('option:not(:first):not([value="ALL"])');
        if ($opts.length) $opts.remove();
        $semt.append($('<option>').attr('value', '').text(vccAdmin.i18n.loading || 'Yükleniyor…'));
        $.post(vccAdmin.ajaxUrl, {
            action: 'vcc_get_districts',
            nonce: vccAdmin.nonce,
            il_name: ilName,
            ilce_name: ilceName
        }).done(function(data) {
            $semt.find('option[value=""]').last().remove();
            if (Array.isArray(data)) {
                data.forEach(function(name) {
                    $semt.append($('<option>').attr('value', name).text(name));
                });
            }
            $semt.append($('<option>').attr('value', 'ALL').text(vccAdmin.i18n.tumu || 'Tümü'));
            updateEstimate();
        }).fail(function() {
            $semt.find('option[value=""]').last().remove();
            alert(vccAdmin.i18n.error || 'Bir hata oluştu.');
        });
    }

    // Taslakları oluştur — kuyruk doldur + batch (Faz 5)
    $btnCreate.on('click', function() {
        var il = $il.val();
        if (!il) {
            alert('Lütfen bir il seçin.');
            return;
        }
        var ilce = $ilce.val() || 'ALL';
        var semt = $semt.val() || 'ALL';
        if (!ilce) ilce = 'ALL';
        if (!semt) semt = 'ALL';
        if (ilce === 'ALL') semt = 'ALL';
        runBatchGeneration(il, ilce, semt);
    });

    // Öne çıkan görsel seçimi (Media Manager) — wp.media yüklü olmalı (media-editor bağımlılığı)
    var vccMediaFrame;
    $(document).on('click', '#vcc-btn-set-thumbnail', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) {
            alert('Medya kütüphanesi yüklenemedi. Sayfayı yenileyin.');
            return;
        }
        if (vccMediaFrame) {
            vccMediaFrame.open();
            return;
        }
        vccMediaFrame = wp.media({
            title: (typeof vccAdmin !== 'undefined' && vccAdmin.i18n && vccAdmin.i18n.setThumbnail) ? vccAdmin.i18n.setThumbnail : 'Görsel seç',
            button: { text: (typeof vccAdmin !== 'undefined' && vccAdmin.i18n && vccAdmin.i18n.useImage) ? vccAdmin.i18n.useImage : 'Kullan' },
            library: { type: 'image' },
            multiple: false
        });
        vccMediaFrame.on('select', function() {
            var attachment = vccMediaFrame.state().get('selection').first().toJSON();
            $('#vcc_default_thumbnail_id').val(attachment.id);
            var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $('#vcc-thumbnail-preview').empty().append($('<img>').attr('src', thumbUrl).attr('alt', ''));
            $('#vcc-btn-remove-thumbnail').show();
        });
        vccMediaFrame.open();
    });
    $(document).on('click', '#vcc-btn-remove-thumbnail', function(e) {
        e.preventDefault();
        $('#vcc_default_thumbnail_id').val('');
        $('#vcc-thumbnail-preview').empty();
        $('#vcc-btn-remove-thumbnail').hide();
    });

    $('#vcc-btn-load-default').on('click', function() {
        $.post(vccAdmin.ajaxUrl, {
            action: 'vcc_get_default_template',
            nonce: vccAdmin.nonce
        }).done(function(res) {
            if (res && res.success && res.data && res.data.content !== undefined) {
                if (typeof tinymce !== 'undefined' && tinymce.get('vcc_content_template')) {
                    tinymce.get('vcc_content_template').setContent(res.data.content);
                } else {
                    $('#vcc_content_template').val(res.data.content);
                }
            }
        });
    });

    $btnPreview.on('click', function() {
        var il = $il.val();
        var ilce = $ilce.val();
        var semt = $semt.val();
        if (!il) {
            alert('Lütfen il seçin.');
            return;
        }
        if (!ilce || ilce === 'ALL' || !semt || semt === 'ALL') {
            alert('Önizleme için il, ilçe ve semt seçin.');
            return;
        }
        window.open(
            vccAdmin.ajaxUrl + '?action=vcc_preview&nonce=' + encodeURIComponent(vccAdmin.nonce) +
            '&il=' + encodeURIComponent(il) + '&ilce=' + encodeURIComponent(ilce) + '&semt=' + encodeURIComponent(semt),
            'vcc-preview',
            'width=800,height=600'
        );
    });

    function runBatchGeneration(il, ilce, semt) {
        $btnCreate.prop('disabled', true);
        $progressArea.show();
        $progressBar.css('width', '0%');
        $progressText.text('');
        $errors.hide().empty();

        $.post(vccAdmin.ajaxUrl, {
            action: 'vcc_start_queue',
            nonce: vccAdmin.nonce,
            il: il,
            ilce: ilce,
            semt: semt,
            include_province_only: $('#vcc-include-province-only').is(':checked') ? 1 : 0,
            include_ilce_no_semt: $('#vcc-include-ilce-no-semt').is(':checked') ? 1 : 0,
            content_template: getEditorContent(),
            seo_title_tpl: $('#vcc_seo_title_tpl').val(),
            seo_desc_tpl: $('#vcc_seo_desc_tpl').val(),
            focus_keyword_tpl: $('#vcc_focus_keyword_tpl').val(),
            default_thumbnail_id: $('#vcc_default_thumbnail_id').val() || 0
        }).done(function(res) {
            if (res && res.success && res.total !== undefined) {
                if (res.total === 0) {
                    $progressText.text('Üretilecek lokasyon yok.');
                    $btnCreate.prop('disabled', false);
                    return;
                }
                processNextBatch(res.total);
            } else {
                $progressText.text(res && res.message ? res.message : (vccAdmin.i18n.error || 'Hata'));
                $btnCreate.prop('disabled', false);
            }
        }).fail(function(jqXHR) {
            var msg = vccAdmin.i18n.error || 'Bir hata oluştu.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                msg = jqXHR.responseJSON.message;
            } else if (jqXHR.responseText) {
                var m = jqXHR.responseText.match(/<title>([^<]+)<\/title>/);
                if (m) msg = m[1];
            }
            $progressText.text(msg);
            $btnCreate.prop('disabled', false);
        });
    }

    function getEditorContent() {
        if (typeof tinymce !== 'undefined' && tinymce.get('vcc_content_template')) {
            return tinymce.get('vcc_content_template').getContent();
        }
        return $('#vcc_content_template').val() || '';
    }

    function processNextBatch(total) {
        $.post(vccAdmin.ajaxUrl, {
            action: 'vcc_generate_batch',
            nonce: vccAdmin.nonce
        }).done(function(res) {
            if (!res) {
                $progressText.text(vccAdmin.i18n.error || 'Hata');
                $btnCreate.prop('disabled', false);
                return;
            }
            var created = res.created || 0;
            var remaining = res.remaining || 0;
            var cursor = res.cursor || 0;
            var pct = total > 0 ? Math.round((cursor / total) * 100) : 0;
            $progressBar.css('width', pct + '%');
            $progressText.text((vccAdmin.i18n.progress || '%d / %d işlendi…').replace(/%d/g, cursor).replace(/%d/g, total));

            if (res.errors && res.errors.length) {
                $errors.show();
                var $ul = $errors.find('ul').length ? $errors.find('ul') : $('<ul>').appendTo($errors);
                res.errors.slice(0, 20).forEach(function(msg) {
                    $ul.append($('<li>').text(msg));
                });
            }

            if (remaining <= 0) {
                $progressText.text((vccAdmin.i18n.done || 'Tamamlandı. %d taslak oluşturuldu.').replace('%d', res.total_created || cursor));
                $btnCreate.prop('disabled', false);
                return;
            }
            processNextBatch(total);
        }).fail(function() {
            $progressText.text(vccAdmin.i18n.error || 'Bir hata oluştu.');
            $btnCreate.prop('disabled', false);
        });
    }
})(jQuery);
