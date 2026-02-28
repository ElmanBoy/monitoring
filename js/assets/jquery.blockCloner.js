(function($) {
    'use strict';

    /**
     * FormBlockCloner - jQuery плагин для клонирования блоков форм
     */
    $.FormBlockCloner = function(element, options) {
        this.$element = $(element);
        this.settings = $.extend({}, $.FormBlockCloner.defaults, options);
        this.init();
    };

    $.FormBlockCloner.defaults = {
        sourceBlock: '.form-block',
        cloneButton: '.clone-btn',
        removeButton: '.remove-btn',
        titleSelector: '.block-title',
        titleText: 'Нарушение №',
        autoInit: true,
        onBeforeClone: null,
        onAfterClone: null,
        onBeforeRemove: null,
        onAfterRemove: null
    };

    $.FormBlockCloner.prototype = {
        init: function() {
            this.blocksCount = 0;
            this.initializeTitles();

            if (this.settings.autoInit) {
                this.bindEvents();
            }

            return this;
        },

        bindEvents: function() {
            const self = this;

            // Клонирование
            $(document).on('click', this.settings.cloneButton, function(e) {
                e.preventDefault();
                self.cloneBlock($(this));
            });

            // Удаление
            if (this.settings.removeButton) {
                $(document).on('click', this.settings.removeButton, function(e) {
                    e.preventDefault(); console.log("RRRRR")
                    self.removeBlock($(this));
                });
            }
        },

        initializeTitles: function() {
            const self = this;
            $(this.settings.sourceBlock).each(function(index) {
                self.updateTitle($(this), index + 1);
            });
            this.blocksCount = $(this.settings.sourceBlock).length;
        },

        cloneBlock: function($button) {
            const $sourceBlock = $button.closest(this.settings.sourceBlock);

            if (!$sourceBlock.length) {
                console.error('Блок-источник не найден');
                return false;
            }

            // Callback before clone
            if (typeof this.settings.onBeforeClone === 'function') {
                if (this.settings.onBeforeClone($sourceBlock) === false) {
                    return false;
                }
            }

            const newBlockNumber = this.blocksCount + 1;

            // Клонируем блок
            const $newBlock = $sourceBlock.clone(true, true);

            // Очищаем поля
            this.clearFields($newBlock);

            // Генерируем уникальные ID
            this.generateUniqueIds($newBlock);

            // Вставляем новый блок
            $sourceBlock.after($newBlock);

            // Обновляем заголовок
            this.updateTitle($newBlock, newBlockNumber);

            // Переинициализируем плагины
            this.reinitializePlugins($newBlock);

            // Добавляем кнопку удаления
            if (this.settings.removeButton) {
                this.addRemoveButton($newBlock);
            }
            $sourceBlock.find(".new_violation").hide();
            this.blocksCount++;

            // Обновляем все заголовки
            this.updateAllTitles();

            // Callback after clone
            if (typeof this.settings.onAfterClone === 'function') {
                this.settings.onAfterClone($newBlock, $sourceBlock, newBlockNumber);
            }

            return $newBlock;
        },

        removeBlock: function($button) {
            const $block = $button.closest(this.settings.sourceBlock);

            if ($block.index() === 0) {
                console.warn('Нельзя удалить оригинальный блок');
                return false;
            }
console.log("REMOVE")
            // Callback before remove
            if (typeof this.settings.onBeforeRemove === 'function') {
                if (this.settings.onBeforeRemove($block) === false) {
                    return false;
                }
            }

            // Очищаем плагины
            this.cleanupPlugins($block);

            // Удаляем блок
            $block.remove();

            this.blocksCount--;

            // Обновляем заголовки
            this.updateAllTitles();

            // Callback after remove
            if (typeof this.settings.onAfterRemove === 'function') {
                this.settings.onAfterRemove();
            }

            return true;
        },

        clearFields: function($block) {
            $block.find('input, textarea, select').each(function() {
                const $field = $(this);
                const fieldType = $field.attr('type');
                const tagName = $field.prop('tagName').toLowerCase();

                if (tagName === 'select') {
                    $field.val('').find('option:selected').prop('selected', false);
                } else if (fieldType === 'checkbox' || fieldType === 'radio') {
                    $field.prop('checked', false);
                } else if (fieldType === 'file') {
                    $field.val('').next('.file-name').text('');
                } else {
                    $field.val('');
                }
            });
        },

        generateUniqueIds: function($block) {
            $block.find('[id]').each(function() {
                const $element = $(this);
                const originalId = $element.attr('id');
                const newId = originalId + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                $element.attr('id', newId);
                $block.find(`label[for="${originalId}"]`).attr('for', newId);
            });
        },

        updateTitle: function($block, number) {
            const $titleElement = $block.find(this.settings.titleSelector);
            if ($titleElement.length) {
                const originalText = $titleElement.data('original-text') ||
                    this.settings.titleText;
                $titleElement.data('original-text', originalText);
                $titleElement.text(originalText + number);
            }
        },

        updateAllTitles: function() {
            const self = this;
            $(this.settings.sourceBlock).each(function(index) {
                self.updateTitle($(this), index + 1);
            });
        },

        reinitializePlugins: function($block) {
            const self = this;
            $block.find('input, textarea, select').each(function() {
                const $field = $(this);
                const originalClasses = $field.attr('class') || '';
                const newId = $field.attr('id');

                // TinyMCE
                if (originalClasses.includes('tinymce')) {
                    setTimeout(() => {
                        if (typeof tinymce !== 'undefined') {
                            if (tinymce.get(newId)) {
                                tinymce.get(newId).remove();
                            }
                            tinymce.init({ selector: `#${newId}` });
                        }
                    }, 100);
                }

                // Flatpickr
                if (originalClasses.includes('flatpickr')) {
                    if (typeof flatpickr !== 'undefined') {
                        $field.flatpickr().destroy();
                        $field.flatpickr();
                    }
                }

                // Masked Input
                if (originalClasses.includes('masked')) {
                    const mask = $field.data('mask');
                    if (mask && typeof $.fn.mask === 'function') {
                        $field.mask(mask);
                    }
                }

                // Chosen
                if (originalClasses.includes('chosen-select')) {
                    if (typeof $.fn.chosen === 'function') {
                        $field.chosen('destroy');
                        $field.chosen({ width: '100%' });
                    }
                }
            });
        },

        addRemoveButton: function($block) {
            if (!$block.find(this.settings.removeButton).length) {
                const $removeBtn = $('<div>', {
                    type: 'button',
                    class: this.settings.removeButton.replace('.', ''),
                    html: '<span class="material-icons">close</span>',
                    /*css: {
                        marginTop: '10px',
                        padding: '5px 10px',
                        backgroundColor: '#dc3545',
                        color: 'white',
                        border: 'none',
                        borderRadius: '3px',
                        cursor: 'pointer'
                    }*/
                });
                $block.append($removeBtn);
            }
        },

        cleanupPlugins: function($block) {
            // TinyMCE
            $block.find('.tinymce').each(function() {
                const editorId = $(this).attr('id');
                if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                    tinymce.get(editorId).remove();
                }
            });

            // Flatpickr
            $block.find('.flatpickr').each(function() {
                if (typeof flatpickr !== 'undefined' && $(this).data('flatpickr')) {
                    $(this).flatpickr().destroy();
                }
            });

            // Chosen
            $block.find('.chosen-select').each(function() {
                if (typeof $.fn.chosen === 'function') {
                    $(this).chosen('destroy');
                }
            });
        },

        // Публичные методы
        getBlocksCount: function() {
            return this.blocksCount;
        },

        destroy: function() {
            $(document).off('click', this.settings.cloneButton);
            if (this.settings.removeButton) {
                $(document).off('click', this.settings.removeButton);
            }
        }
    };

    // jQuery plugin initialization
    $.fn.formBlockCloner = function(options) {
        return this.each(function() {
            if (!$.data(this, 'formBlockCloner')) {
                $.data(this, 'formBlockCloner', new $.FormBlockCloner(this, options));
            }
        });
    };

})(jQuery);